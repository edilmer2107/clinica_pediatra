<?php

namespace App\Http\Controllers\Farmacia;

use App\Http\Controllers\Controller;
use App\Models\InventoryCount;
use App\Services\Farmacia\ConciliacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConciliacionController extends Controller
{
    public function index(): View
    {
        $conteos = InventoryCount::with('user')->latest()->paginate(15);

        return view('farmacia.conciliaciones.index', compact('conteos'));
    }

    public function store(ConciliacionService $servicio): RedirectResponse
    {
        $conteo = $servicio->iniciar();

        return redirect()->route('conciliaciones.show', $conteo)->with('success', 'Conciliación iniciada: registre el conteo físico de cada lote.');
    }

    public function show(InventoryCount $conciliacion): View
    {
        $conciliacion->load(['items.lot.medication', 'user']);

        return view('farmacia.conciliaciones.show', ['conteo' => $conciliacion, 'meta' => ConciliacionService::META_CONSISTENCIA]);
    }

    public function update(Request $request, InventoryCount $conciliacion, ConciliacionService $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'fisico' => ['array'],
            'fisico.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        $cerrar = $request->input('accion') === 'cerrar';
        $conteo = $servicio->registrarConteo($conciliacion, $datos['fisico'] ?? [], $cerrar, $request->boolean('ajustar_stock'));

        return redirect()->route('conciliaciones.show', $conteo)
            ->with('success', $cerrar ? "Conciliación cerrada con {$conteo->consistencia}% de consistencia." : 'Conteo guardado.');
    }
}
