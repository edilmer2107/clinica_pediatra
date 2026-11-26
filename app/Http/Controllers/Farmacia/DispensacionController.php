<?php

namespace App\Http\Controllers\Farmacia;

use App\Http\Controllers\Controller;
use App\Models\Dispensation;
use App\Models\Prescription;
use App\Services\Farmacia\DispensacionService;
use App\Services\Farmacia\InventarioService;
use App\Services\Portal\NotificacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DispensacionController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));

        $pendientes = Prescription::with(['patient', 'doctor', 'items.medication'])
            ->whereIn('estado', ['emitida', 'parcial'])
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w->where('codigo', 'ilike', "%{$buscar}%")
                ->orWhereHas('patient', fn ($p) => $p->where('nombres', 'ilike', "%{$buscar}%")
                    ->orWhere('apellidos', 'ilike', "%{$buscar}%")
                    ->orWhere('historia_clinica', 'ilike', "%{$buscar}%"))))
            ->latest()->get();

        $recientes = Dispensation::with(['patient', 'pharmacist', 'prescription'])->latest('fecha')->limit(15)->get();

        return view('farmacia.dispensaciones.index', compact('pendientes', 'recientes', 'buscar'));
    }

    public function create(Prescription $receta, InventarioService $inventario): View
    {
        $receta->load(['patient.tutors', 'doctor', 'items.medication']);

        $lotes = [];
        foreach ($receta->items as $item) {
            $lotes[$item->id] = $inventario->lotesFefo($item->medication);
        }

        return view('farmacia.dispensaciones.create', compact('receta', 'lotes'));
    }

    public function store(Request $request, Prescription $receta, DispensacionService $servicio, NotificacionService $notificaciones): RedirectResponse
    {
        $datos = $request->validate([
            'tutor_id' => ['nullable', 'integer', 'exists:tutors,id'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.prescription_item_id' => ['required', 'integer'],
            'lineas.*.medication_id' => ['required', 'integer'],
            'lineas.*.lot_id' => ['nullable', 'integer'],
            'lineas.*.cantidad' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        $lineas = collect($datos['lineas'])
            ->filter(fn ($l) => (int) ($l['cantidad'] ?? 0) > 0)
            ->map(function ($l) {
                if (empty($l['lot_id'])) {
                    throw new \App\Exceptions\ReglaNegocioException('Seleccione un lote para cada medicamento a dispensar.');
                }

                return $l;
            })->all();

        $dispensacion = $servicio->dispensar($receta, $lineas, $request->user(), $datos['tutor_id'] ?? null, $datos['observaciones'] ?? null);

        return redirect()->route('dispensaciones.show', $dispensacion)->with('success', "Dispensación {$dispensacion->codigo} registrada.");
    }

    public function show(Dispensation $dispensacion): View
    {
        $dispensacion->load(['patient', 'pharmacist', 'tutor', 'prescription.doctor', 'items.medication', 'items.lot']);

        return view('farmacia.dispensaciones.show', compact('dispensacion'));
    }

    /** Marca una receta como lista para retiro y avisa al tutor (correo/SMS). */
    public function listaParaRetiro(Prescription $receta, NotificacionService $notificaciones): RedirectResponse
    {
        $receta->update(['lista_para_retiro_at' => now()]);
        $enviadas = $notificaciones->recetaLista($receta);

        return back()->with('success', "Receta {$receta->codigo} marcada como lista para retiro. Notificaciones enviadas: {$enviadas}.");
    }
}
