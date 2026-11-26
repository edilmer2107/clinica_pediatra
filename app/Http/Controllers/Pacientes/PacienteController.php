<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\PacienteRequest;
use App\Http\Requests\TutorRequest;
use App\Models\Patient;
use App\Services\AuditService;
use App\Services\Pacientes\DesarrolloService;
use App\Services\Pacientes\PacienteService;
use App\Services\Pacientes\PercentilOmsService;
use App\Services\Pacientes\VacunacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PacienteController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));

        $pacientes = Patient::with(['tutors' => fn ($q) => $q->wherePivot('es_principal', true)])
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nombres', 'ilike', "%{$buscar}%")
                ->orWhere('apellidos', 'ilike', "%{$buscar}%")
                ->orWhere('historia_clinica', 'ilike', "%{$buscar}%")
                ->orWhere('dni', 'like', "%{$buscar}%")
                ->orWhereHas('tutors', fn ($t) => $t->where('dni', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'ilike', "%{$buscar}%"))))
            ->orderBy('apellidos')->orderBy('nombres')
            ->paginate(15)->withQueryString();

        return view('pacientes.index', compact('pacientes', 'buscar'));
    }

    public function create(): View
    {
        $this->authorize('pacientes.gestionar');

        return view('pacientes.create');
    }

    public function store(PacienteRequest $request, PacienteService $servicio): RedirectResponse
    {
        $paciente = $servicio->registrar(
            collect($request->validated())->except(['tutor', 'parentesco', 'acepta_tratamiento_datos'])->all(),
            $request->validated()['tutor'],
            ['parentesco' => $request->validated()['parentesco'], 'puede_retirar_medicamentos' => true],
            $request->only(['acepta_tratamiento_datos', 'acepta_atencion_medica', 'acepta_notificaciones']),
        );

        return redirect()->route('pacientes.show', $paciente)->with('success', "Paciente registrado con historia clínica {$paciente->historia_clinica}.");
    }

    public function show(Patient $paciente, VacunacionService $vacunas, DesarrolloService $desarrollo, PercentilOmsService $oms): View
    {
        $paciente->load(['tutors', 'consents.tutor', 'pediatricRecord', 'measurements.registradoPor', 'psc35Screenings.aplicadoPor',
            'consultations' => fn ($q) => $q->with('doctor', 'diagnoses.cie10')->latest('fecha')->limit(10),
            'appointments' => fn ($q) => $q->with('doctor')->latest('fecha_hora')->limit(10)]);

        $calendario = $vacunas->calendario($paciente);
        $hitos = $desarrollo->hitosAplicables($paciente);
        $graficos = $this->datosGraficos($paciente, $oms);
        $consentimiento = $paciente->consentimientoVigente();

        return view('pacientes.show', compact('paciente', 'calendario', 'hitos', 'graficos', 'consentimiento'));
    }

    public function edit(Patient $paciente): View
    {
        $this->authorize('pacientes.gestionar');

        return view('pacientes.edit', compact('paciente'));
    }

    public function update(PacienteRequest $request, Patient $paciente, AuditService $audit): RedirectResponse
    {
        $antes = $paciente->only(array_keys($request->validated()));
        $paciente->update($request->validated());
        $audit->registrar('editar_paciente', 'pacientes', "Edición de datos del paciente {$paciente->historia_clinica}.", $paciente, $antes, $paciente->getChanges());

        return redirect()->route('pacientes.show', $paciente)->with('success', 'Datos del paciente actualizados.');
    }

    public const PARENTESCOS = ['Madre', 'Padre', 'Abuelo(a)', 'Tío(a)', 'Hermano(a)', 'Tutor legal', 'Otro'];

    /**
     * Curvas OMS (P3, P15, P50, P85, P97) y puntos del paciente para Chart.js.
     */
    private function datosGraficos(Patient $paciente, PercentilOmsService $oms): array
    {
        $graficos = [];
        $indicadores = ['wfa' => ['peso_kg', 'Peso (kg)'], 'lhfa' => ['talla_cm', 'Talla (cm)'], 'hcfa' => ['perimetro_cefalico_cm', 'Perímetro cefálico (cm)'], 'bmifa' => ['imc', 'IMC (kg/m²)']];
        $edadMaxMeses = min(60, max(12, (int) ceil($paciente->edadEnMeses() / 6) * 6 + 6));

        foreach ($indicadores as $ind => [$campo, $etiqueta]) {
            $curvas = [];
            foreach (['P3' => -1.881, 'P15' => -1.036, 'P50' => 0.0, 'P85' => 1.036, 'P97' => 1.881] as $nombre => $z) {
                $curvas[$nombre] = collect(range(0, $edadMaxMeses))
                    ->map(fn ($m) => ['x' => $m, 'y' => round($oms->valorParaZ($ind, $paciente->sexo, (int) round($m * PercentilOmsService::DIAS_POR_MES), $z), 2)])->all();
            }
            $puntos = $paciente->measurements
                ->filter(fn ($m) => $m->edad_dias <= PercentilOmsService::EDAD_MAXIMA_DIAS && $m->{$campo})
                ->map(fn ($m) => ['x' => round($m->edad_dias / PercentilOmsService::DIAS_POR_MES, 2), 'y' => (float) $m->{$campo}])->values()->all();

            $graficos[$ind] = ['titulo' => PercentilOmsService::INDICADORES[$ind], 'etiqueta' => $etiqueta, 'curvas' => $curvas, 'puntos' => $puntos];
        }

        return $graficos;
    }
}
