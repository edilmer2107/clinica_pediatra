<?php

namespace App\Http\Controllers\Farmacia;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Models\LotRecall;
use App\Services\Farmacia\RetiroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RetiroController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('lote'));
        $lotesEncontrados = $buscar !== ''
            ? Lot::with('medication')->where('numero_lote', 'ilike', "%{$buscar}%")->limit(20)->get()
            : collect();

        $retiros = LotRecall::with(['lot.medication', 'registradoPor'])->latest()->paginate(15);

        return view('farmacia.retiros.index', compact('retiros', 'lotesEncontrados', 'buscar'));
    }

    public function show(Lot $lote, RetiroService $servicio): View
    {
        $lote->load('medication');
        $receptores = $servicio->trazabilidad($lote);

        return view('farmacia.retiros.show', compact('lote', 'receptores'));
    }

    public function store(Request $request, Lot $lote, RetiroService $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'alerta_sanitaria' => ['required', 'string', 'max:60'],
            'motivo' => ['required', 'string', 'max:1000'],
            'disposicion_final' => ['required', Rule::in(['devolucion_proveedor', 'destruccion', 'cuarentena'])],
            'detalle_disposicion' => ['nullable', 'string', 'max:1000'],
        ]);

        $servicio->retirar($lote, $datos);

        return redirect()->route('retiros.show', $lote)->with('success', "Lote {$lote->numero_lote} retirado del inventario.");
    }
}
