<?php

namespace App\Http\Controllers\Farmacia;

use App\Http\Controllers\Controller;
use App\Models\ControlledDrugEntry;
use App\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControladoController extends Controller
{
    public function index(Request $request): View
    {
        $controlados = Medication::where('es_controlado', true)->orderBy('nombre')->get();
        $medicamentoId = $request->integer('medication_id') ?: $controlados->first()?->id;

        $movimientos = ControlledDrugEntry::with(['lot', 'pharmacist', 'tutor', 'patient'])
            ->where('medication_id', $medicamentoId)
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha', '<=', $request->date('hasta')))
            ->orderBy('id')
            ->get();

        $medicamento = $controlados->firstWhere('id', $medicamentoId);

        return view('farmacia.controlados.index', compact('controlados', 'movimientos', 'medicamento'));
    }
}
