<?php

namespace App\Http\Controllers\Farmacia;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Models\Medication;
use App\Services\AuditService;
use App\Services\Farmacia\ControladosService;
use App\Services\Farmacia\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoteController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventario,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $filtro = $request->query('filtro', 'todos');
        $medicamentoId = $request->integer('medication_id') ?: null;
        $dias = config('clinica.dias_alerta_vencimiento');

        $lotes = Lot::with('medication')
            ->when($medicamentoId, fn ($q) => $q->where('medication_id', $medicamentoId))
            ->when($filtro === 'por_vencer', fn ($q) => $q->where('cantidad_actual', '>', 0)
                ->whereBetween('fecha_vencimiento', [today(), today()->addDays($dias)]))
            ->when($filtro === 'vencidos', fn ($q) => $q->where('cantidad_actual', '>', 0)->whereDate('fecha_vencimiento', '<', today()))
            ->when(in_array($filtro, array_keys(Lot::ESTADOS), true), fn ($q) => $q->where('estado', $filtro))
            ->orderBy('fecha_vencimiento')
            ->paginate(20)->withQueryString();

        $medicamentos = Medication::orderBy('nombre')->get(['id', 'nombre', 'concentracion']);

        return view('farmacia.lotes.index', compact('lotes', 'filtro', 'medicamentos', 'medicamentoId'));
    }

    public function create(Request $request): View
    {
        $medicamentos = Medication::where('activo', true)->orderBy('nombre')->get();

        return view('farmacia.lotes.create', ['medicamentos' => $medicamentos, 'medicamentoId' => $request->integer('medication_id')]);
    }

    public function store(Request $request, ControladosService $controlados): RedirectResponse
    {
        $datos = $request->validate([
            'medication_id' => ['required', 'exists:medications,id'],
            'numero_lote' => ['required', 'string', 'max:40', Rule::unique('lots')->where('medication_id', $request->input('medication_id'))],
            'fecha_vencimiento' => ['required', 'date', 'after:today'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'costo_unitario' => ['required', 'numeric', 'min:0'],
            'almacen' => ['required', 'string', 'max:60'],
            'ubicacion' => ['required', 'string', 'max:60'],
            'proveedor' => ['nullable', 'string', 'max:120'],
            'documento' => ['nullable', 'string', 'max:60'],
        ], [
            'numero_lote.unique' => 'Ya existe un lote con ese número para el medicamento.',
            'fecha_vencimiento.after' => 'No se puede ingresar un lote vencido o que vence hoy.',
        ]);

        $lote = DB::transaction(function () use ($datos, $controlados, $request) {
            $lote = $this->inventario->registrarEntrada($datos);
            $medicamento = $lote->medication;
            if ($medicamento->es_controlado) {
                $controlados->registrar($medicamento, $lote, 'entrada', (int) $datos['cantidad'], $request->user()->id, [
                    'documento' => $datos['documento'] ?? 'Ingreso de lote',
                ]);
            }

            return $lote;
        });

        $this->audit->registrar('ingreso_lote', 'farmacia', "Ingreso del lote {$lote->numero_lote} de {$lote->medication->nombre} ({$lote->cantidad_inicial} u.).", $lote);

        return redirect()->route('medicamentos.show', $lote->medication_id)->with('success', 'Lote ingresado correctamente.');
    }

    public function updateEstado(Request $request, Lot $lote): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(['activo', 'cuarentena'])],
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        $anterior = $lote->estado;
        $this->inventario->cambiarEstado($lote, $datos['estado']);
        $this->audit->registrar('estado_lote', 'farmacia',
            "Lote {$lote->numero_lote}: {$anterior} → {$datos['estado']}. Motivo: {$datos['motivo']}", $lote,
            ['estado' => $anterior], ['estado' => $datos['estado']]);

        return back()->with('success', "El lote {$lote->numero_lote} ahora está en estado {$datos['estado']}.");
    }
}
