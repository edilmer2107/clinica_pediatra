<?php

namespace App\Http\Controllers\Farmacia;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Services\AuditService;
use App\Services\Farmacia\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicamentoController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));
        $tipo = $request->query('tipo');

        $medicamentos = Medication::query()
            ->withSum(['lots as stock' => fn ($q) => $q->where('estado', 'activo')->whereDate('fecha_vencimiento', '>=', today())], 'cantidad_actual')
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nombre', 'ilike', "%{$buscar}%")
                ->orWhere('principio_activo', 'ilike', "%{$buscar}%")
                ->orWhere('codigo', 'ilike', "%{$buscar}%")))
            ->when($tipo === 'controlados', fn ($q) => $q->where('es_controlado', true))
            ->when($tipo === 'vacunas', fn ($q) => $q->where('es_vacuna', true))
            ->orderBy('nombre')
            ->paginate(15)->withQueryString();

        return view('farmacia.medicamentos.index', compact('medicamentos', 'buscar', 'tipo'));
    }

    public function create(): View
    {
        return view('farmacia.medicamentos.form', ['medicamento' => new Medication(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $medicamento = Medication::create($this->validar($request));
        $this->audit->registrar('crear_medicamento', 'farmacia', "Registro del medicamento {$medicamento->nombre}.", $medicamento, null, $medicamento->toArray());

        return redirect()->route('medicamentos.show', $medicamento)->with('success', 'Medicamento registrado correctamente.');
    }

    public function show(Medication $medicamento, InventarioService $inventario): View
    {
        $medicamento->load(['lots' => fn ($q) => $q->orderBy('fecha_vencimiento')]);
        $sugerido = $inventario->sugerirLote($medicamento);

        return view('farmacia.medicamentos.show', compact('medicamento', 'sugerido'));
    }

    public function edit(Medication $medicamento): View
    {
        return view('farmacia.medicamentos.form', compact('medicamento'));
    }

    public function update(Request $request, Medication $medicamento): RedirectResponse
    {
        $antes = $medicamento->toArray();
        $medicamento->update($this->validar($request, $medicamento));
        $this->audit->registrar('editar_medicamento', 'farmacia', "Edición del medicamento {$medicamento->nombre}.", $medicamento, $antes, $medicamento->getChanges());

        return redirect()->route('medicamentos.show', $medicamento)->with('success', 'Medicamento actualizado.');
    }

    private function validar(Request $request, ?Medication $medicamento = null): array
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:20', Rule::unique('medications')->ignore($medicamento)],
            'nombre' => ['required', 'string', 'max:120'],
            'principio_activo' => ['required', 'string', 'max:120'],
            'concentracion' => ['nullable', 'string', 'max:60'],
            'forma_farmaceutica' => ['required', 'string', 'max:60'],
            'presentacion' => ['nullable', 'string', 'max:120'],
            'unidad' => ['required', 'string', 'max:20'],
            'mg_por_unidad' => ['nullable', 'numeric', 'min:0'],
            'mg_por_ml' => ['nullable', 'numeric', 'min:0'],
            'dosis_min_mg_kg_dia' => ['nullable', 'numeric', 'min:0'],
            'dosis_max_mg_kg_dia' => ['nullable', 'numeric', 'min:0', 'gte:dosis_min_mg_kg_dia'],
            'dosis_max_diaria_mg' => ['nullable', 'numeric', 'min:0'],
            'dosis_min_mg_m2_dia' => ['nullable', 'numeric', 'min:0'],
            'dosis_max_mg_m2_dia' => ['nullable', 'numeric', 'min:0'],
            'tipo_control' => ['nullable', Rule::in(['estupefaciente', 'psicotropico'])],
            'precio_venta' => ['required', 'numeric', 'min:0', 'max:99999'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
        ]);
        $datos['es_controlado'] = $request->boolean('es_controlado');
        $datos['es_vacuna'] = $request->boolean('es_vacuna');
        $datos['activo'] = $request->boolean('activo');
        if (! $datos['es_controlado']) {
            $datos['tipo_control'] = null;
        }

        return $datos;
    }
}
