<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\PatientAccount;
use App\Services\Facturacion\FacturacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CuentaController extends Controller
{
    public function index(Request $request): View
    {
        $cuentas = PatientAccount::with('patient')
            ->withCount(['cargosPendientes as cargos_pendientes'])
            ->withSum(['cargosPendientes as monto_pendiente'], 'subtotal')
            ->where('estado', $request->query('estado', 'abierta'))
            ->when($request->filled('buscar'), fn ($q) => $q->whereHas('patient', fn ($p) => $p->where('apellidos', 'ilike', '%'.$request->query('buscar').'%')
                ->orWhere('historia_clinica', 'ilike', '%'.$request->query('buscar').'%')))
            ->latest('abierta_at')->paginate(15)->withQueryString();

        return view('facturacion.cuentas.index', compact('cuentas'));
    }

    public function show(PatientAccount $cuenta): View
    {
        $cuenta->load(['patient.tutors', 'charges.invoice', 'invoices']);
        $pendientes = $cuenta->charges->whereNull('invoice_id');

        return view('facturacion.cuentas.show', [
            'cuenta' => $cuenta,
            'pendientes' => $pendientes,
            'totales' => FacturacionService::calcularTotales($pendientes),
        ]);
    }

    public function facturar(Request $request, PatientAccount $cuenta, FacturacionService $servicio): RedirectResponse
    {
        $this->authorize('facturacion.gestionar');
        $datos = $request->validate(['tutor_id' => ['nullable', 'exists:tutors,id']]);
        $factura = $servicio->emitir($cuenta, $request->user(), $datos['tutor_id'] ?? null);

        return redirect()->route('facturas.show', $factura)->with('success', "Comprobante {$factura->numero} emitido por S/ ".number_format($factura->total, 2).'.');
    }
}
