<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DocumentAccess;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Tutor;
use App\Services\Pacientes\PercentilOmsService;
use App\Services\Pacientes\VacunacionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Portal de tutores: visibilidad familiar de todos los hijos vinculados.
 * Toda consulta se restringe a los pacientes vinculados al tutor autenticado.
 */
class PortalController extends Controller
{
    public function index(Request $request, VacunacionService $vacunas): View
    {
        $tutor = $this->tutor($request);
        $hijos = $tutor->patients()->with(['appointments' => fn ($q) => $q->whereIn('estado', ['programada', 'confirmada'])->where('fecha_hora', '>=', now())->orderBy('fecha_hora')])->get();

        $resumen = $hijos->mapWithKeys(fn (Patient $p) => [$p->id => [
            'calendario' => $vacunas->calendario($p),
            'ultima' => $p->measurements()->reorder('fecha', 'desc')->first(),
            'proxima_cita' => $p->appointments->first(),
        ]]);

        return view('portal.index', compact('tutor', 'hijos', 'resumen'));
    }

    public function hijo(Request $request, Patient $paciente, VacunacionService $vacunas, PercentilOmsService $oms): View
    {
        $tutor = $this->tutor($request);
        $this->autorizar($tutor, $paciente);

        $paciente->load(['measurements', 'consultations' => fn ($q) => $q->with(['doctor', 'diagnoses.cie10'])->latest('fecha'),
            'prescriptions' => fn ($q) => $q->with('items.medication', 'doctor')->where('estado', '!=', 'anulada')->latest(),
            'labOrders' => fn ($q) => $q->with('items.labTest')->latest()]);

        $graficos = [];
        foreach (['wfa' => ['peso_kg', 'Peso (kg)'], 'lhfa' => ['talla_cm', 'Talla (cm)']] as $ind => [$campo, $etiqueta]) {
            $max = min(60, max(12, (int) ceil($paciente->edadEnMeses() / 6) * 6 + 6));
            $curvas = [];
            foreach (['P3' => -1.881, 'P50' => 0.0, 'P97' => 1.881] as $nombre => $z) {
                $curvas[$nombre] = collect(range(0, $max))->map(fn ($m) => ['x' => $m, 'y' => round($oms->valorParaZ($ind, $paciente->sexo, (int) round($m * PercentilOmsService::DIAS_POR_MES), $z), 2)])->all();
            }
            $puntos = $paciente->measurements->filter(fn ($m) => $m->edad_dias <= PercentilOmsService::EDAD_MAXIMA_DIAS)
                ->map(fn ($m) => ['x' => round($m->edad_dias / PercentilOmsService::DIAS_POR_MES, 2), 'y' => (float) $m->{$campo}])->values()->all();
            $graficos[$ind] = ['titulo' => PercentilOmsService::INDICADORES[$ind], 'etiqueta' => $etiqueta, 'curvas' => $curvas, 'puntos' => $puntos];
        }

        return view('portal.hijo', [
            'paciente' => $paciente,
            'calendario' => $vacunas->calendario($paciente),
            'graficos' => $graficos,
            'enRangoOms' => $paciente->edadEnDias() <= PercentilOmsService::EDAD_MAXIMA_DIAS,
        ]);
    }

    public function receta(Request $request, Prescription $receta): Response
    {
        $tutor = $this->tutor($request);
        $this->autorizar($tutor, $receta->patient);
        abort_if($receta->estado === 'anulada', 404);

        $this->registrarAcceso($request, $tutor, $receta->patient_id, 'receta', $receta->id, 'descarga');
        $receta->load(['patient', 'doctor', 'items.medication', 'consultation.diagnoses.cie10']);

        return Pdf::loadView('clinica.recetas.pdf', compact('receta'))->download("receta_{$receta->codigo}.pdf");
    }

    public function resultado(Request $request, LabOrder $orden): View
    {
        $tutor = $this->tutor($request);
        $this->autorizar($tutor, $orden->patient);
        abort_unless($orden->estado === 'completada', 404);

        $this->registrarAcceso($request, $tutor, $orden->patient_id, 'resultado', $orden->id, 'visualizacion');
        $orden->load(['items.labTest', 'doctor', 'patient']);

        return view('portal.resultado', compact('orden'));
    }

    public function resultadoPdf(Request $request, LabOrder $orden): Response
    {
        $tutor = $this->tutor($request);
        $this->autorizar($tutor, $orden->patient);
        abort_unless($orden->estado === 'completada', 404);

        $this->registrarAcceso($request, $tutor, $orden->patient_id, 'resultado', $orden->id, 'descarga');
        $orden->load(['items.labTest', 'doctor', 'patient']);

        return Pdf::loadView('portal.resultado_pdf', compact('orden'))->download("resultados_{$orden->codigo}.pdf");
    }

    public function archivo(Request $request, LabOrderItem $item): Response
    {
        $tutor = $this->tutor($request);
        $orden = $item->labOrder;
        $this->autorizar($tutor, $orden->patient);
        abort_unless($item->archivo_path && Storage::disk('local')->exists($item->archivo_path), 404);

        $this->registrarAcceso($request, $tutor, $orden->patient_id, 'resultado', $orden->id, 'descarga');

        return Storage::disk('local')->download($item->archivo_path, $item->archivo_nombre);
    }

    private function tutor(Request $request): Tutor
    {
        $tutor = $request->user()->tutor;
        abort_unless($tutor, 403, 'Su cuenta no está vinculada a un tutor.');

        return $tutor;
    }

    /** Protección contra acceso a pacientes ajenos (manipulación de identificadores). */
    private function autorizar(Tutor $tutor, ?Patient $paciente): void
    {
        abort_unless($paciente && $tutor->patients()->whereKey($paciente->id)->exists(), 403, 'No tiene acceso a la información de este paciente.');
    }

    private function registrarAcceso(Request $request, Tutor $tutor, int $pacienteId, string $tipo, int $documentoId, string $accion): void
    {
        DocumentAccess::create([
            'tutor_id' => $tutor->id, 'patient_id' => $pacienteId, 'documento_tipo' => $tipo,
            'documento_id' => $documentoId, 'accion' => $accion, 'ip' => $request->ip(),
        ]);
    }
}
