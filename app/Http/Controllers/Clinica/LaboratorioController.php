<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Services\Clinica\LaboratorioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaboratorioController extends Controller
{
    public function index(Request $request): View
    {
        $ordenes = LabOrder::with(['patient', 'doctor', 'items.labTest'])
            ->when($request->query('estado', 'solicitada') !== 'todas', fn ($q) => $q->where('estado', $request->query('estado', 'solicitada')))
            ->latest()->paginate(15)->withQueryString();

        return view('clinica.laboratorio.index', compact('ordenes'));
    }

    public function store(Request $request, Consultation $consulta, LaboratorioService $servicio): RedirectResponse
    {
        abort_unless($consulta->doctor_id === $request->user()->id || $request->user()->hasRole('administrador'), 403);
        $datos = $request->validate([
            'examenes' => ['required', 'array', 'min:1'], 'examenes.*' => ['integer', 'exists:lab_tests,id'],
            'prioridad' => ['required', Rule::in(['rutina', 'urgente'])],
            'indicaciones' => ['nullable', 'string', 'max:500'],
        ]);
        $orden = $servicio->solicitar($consulta, $request->user(), $datos['examenes'], $datos['prioridad'], $datos['indicaciones'] ?? null);

        return back()->with('success', "Orden {$orden->codigo} generada.");
    }

    public function show(LabOrder $orden): View
    {
        $orden->load(['patient', 'doctor', 'items.labTest', 'consultation']);

        return view('clinica.laboratorio.show', compact('orden'));
    }

    public function resultados(Request $request, LabOrder $orden, LaboratorioService $servicio): RedirectResponse
    {
        $request->validate([
            'resultados' => ['required', 'array'],
            'resultados.*.valor' => ['nullable', 'numeric', 'between:-100000,1000000'],
            'resultados.*.resultado_texto' => ['nullable', 'string', 'max:2000'],
            'resultados.*.anormal' => ['nullable', 'boolean'],
            // Solo PDF o imágenes; se valida el tipo real del contenido, máximo 5 MB
            'resultados.*.archivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:5120'],
        ]);

        $resultados = [];
        foreach ($orden->items as $item) {
            $resultados[$item->id] = [
                'valor' => $request->input("resultados.{$item->id}.valor"),
                'resultado_texto' => $request->input("resultados.{$item->id}.resultado_texto"),
                'anormal' => $request->boolean("resultados.{$item->id}.anormal"),
                'archivo' => $request->file("resultados.{$item->id}.archivo"),
            ];
        }
        $orden = $servicio->registrarResultados($orden, $resultados, $request->user());

        return back()->with($orden->items->contains('es_anormal', true) ? 'warning' : 'success',
            'Resultados registrados.'.($orden->items->contains('es_anormal', true) ? ' Hay VALORES ANORMALES.' : ''));
    }

    public function archivo(LabOrderItem $item): StreamedResponse
    {
        abort_unless($item->archivo_path && Storage::disk('local')->exists($item->archivo_path), 404);

        return Storage::disk('local')->download($item->archivo_path, $item->archivo_nombre);
    }
}
