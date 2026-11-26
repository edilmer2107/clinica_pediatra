<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\Clinica\RecetaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class RecetaController extends Controller
{
    public function index(Request $request): View
    {
        $recetas = Prescription::with(['patient', 'doctor', 'items.medication'])
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->query('estado')))
            ->when($request->filled('buscar'), fn ($q) => $q->where('codigo', 'ilike', '%'.$request->query('buscar').'%')
                ->orWhereHas('patient', fn ($p) => $p->where('apellidos', 'ilike', '%'.$request->query('buscar').'%')))
            ->latest()->paginate(15)->withQueryString();

        return view('clinica.recetas.index', compact('recetas'));
    }

    public function store(Request $request, Consultation $consulta, RecetaService $servicio): RedirectResponse
    {
        abort_unless($consulta->doctor_id === $request->user()->id || $request->user()->hasRole('administrador'), 403, 'Solo el médico tratante puede prescribir en esta consulta.');
        $datos = $request->validate([
            'peso_kg' => ['required', 'numeric', 'min:0.4', 'max:150'],
            'talla_cm' => ['nullable', 'numeric', 'min:25', 'max:220'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'justificacion_interaccion' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_id' => ['required', 'exists:medications,id'],
            'items.*.metodo_calculo' => ['required', Rule::in(['peso', 'sc'])],
            'items.*.dosis_indicada' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'items.*.frecuencia_horas' => ['required', 'integer', Rule::in([4, 6, 8, 12, 24])],
            'items.*.duracion_dias' => ['required', 'integer', 'min:1', 'max:90'],
            'items.*.via' => ['required', Rule::in(['oral', 'IM', 'IV', 'SC', 'inhalatoria', 'tópica', 'rectal'])],
            'items.*.indicaciones' => ['nullable', 'string', 'max:255'],
            'items.*.justificacion' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $receta = $servicio->emitir($consulta->patient, $request->user(), $datos, $consulta);
        } catch (\InvalidArgumentException $e) {
            throw new \App\Exceptions\ReglaNegocioException($e->getMessage());
        }

        return redirect()->route('recetas.show', $receta)->with($receta->items->contains('alerta_dosis', true) || $receta->items->contains('alerta_interaccion', true) ? 'warning' : 'success',
            "Receta {$receta->codigo} emitida".($receta->items->contains(fn ($i) => $i->alertas) ? ' con alertas justificadas.' : '.'));
    }

    /** Previsualiza el cálculo de dosis (AJAX) antes de emitir. */
    public function calcular(Request $request, Consultation $consulta, RecetaService $servicio): \Illuminate\Http\JsonResponse
    {
        $datos = $request->validate([
            'peso_kg' => ['required', 'numeric', 'min:0.4', 'max:150'],
            'talla_cm' => ['nullable', 'numeric', 'min:25', 'max:220'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_id' => ['required', 'exists:medications,id'],
            'items.*.metodo_calculo' => ['required', Rule::in(['peso', 'sc'])],
            'items.*.dosis_indicada' => ['required', 'numeric', 'gt:0'],
            'items.*.frecuencia_horas' => ['required', 'integer', Rule::in([4, 6, 8, 12, 24])],
            'items.*.duracion_dias' => ['required', 'integer', 'min:1', 'max:90'],
        ]);

        try {
            $ev = $servicio->evaluar($consulta->patient, (float) $datos['peso_kg'], isset($datos['talla_cm']) ? (float) $datos['talla_cm'] : null, $datos['items']);
        } catch (\InvalidArgumentException|\App\Exceptions\ReglaNegocioException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'lineas' => collect($ev['lineas'])->map(fn ($l) => ['medicamento' => $l['medicamento']->nombre_completo] + $l['calculo'])->values(),
            'interacciones' => $ev['interacciones'],
        ]);
    }

    public function show(Prescription $receta): View
    {
        $receta->load(['patient', 'doctor', 'items.medication', 'consultation.diagnoses.cie10', 'dispensations']);

        return view('clinica.recetas.show', compact('receta'));
    }

    public function pdf(Prescription $receta): Response
    {
        $receta->load(['patient', 'doctor', 'items.medication', 'consultation.diagnoses.cie10']);

        return Pdf::loadView('clinica.recetas.pdf', compact('receta'))->download("receta_{$receta->codigo}.pdf");
    }

    public function updateItem(Request $request, PrescriptionItem $item, RecetaService $servicio): RedirectResponse
    {
        abort_unless($item->prescription->doctor_id === $request->user()->id || $request->user()->hasRole('administrador'), 403);
        $datos = $request->validate([
            'dosis_indicada' => ['required', 'numeric', 'gt:0'],
            'frecuencia_horas' => ['required', 'integer', Rule::in([4, 6, 8, 12, 24])],
            'duracion_dias' => ['required', 'integer', 'min:1', 'max:90'],
            'justificacion' => ['nullable', 'string', 'max:500'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);
        $servicio->modificarItem($item, $datos, $datos['motivo']);

        return back()->with('success', 'Posología modificada (registrada en auditoría).');
    }

    public function anular(Request $request, Prescription $receta, RecetaService $servicio): RedirectResponse
    {
        abort_unless($receta->doctor_id === $request->user()->id || $request->user()->hasRole('administrador'), 403);
        $datos = $request->validate(['motivo' => ['required', 'string', 'max:255']]);
        $servicio->anular($receta, $datos['motivo']);

        return back()->with('success', "Receta {$receta->codigo} anulada.");
    }
}
