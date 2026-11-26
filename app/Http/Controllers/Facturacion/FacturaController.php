<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Facturacion\FacturacionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class FacturaController extends Controller
{
    public function index(Request $request): View
    {
        $facturas = Invoice::with('patient')
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->query('estado')))
            ->when($request->filled('buscar'), fn ($q) => $q->where('numero', 'ilike', '%'.$request->query('buscar').'%')
                ->orWhereHas('patient', fn ($p) => $p->where('apellidos', 'ilike', '%'.$request->query('buscar').'%')))
            ->latest('id')->paginate(15)->withQueryString();

        return view('facturacion.facturas.index', compact('facturas'));
    }

    public function show(Invoice $factura): View
    {
        $factura->load(['patient', 'tutor', 'charges', 'payments.user']);

        return view('facturacion.facturas.show', compact('factura'));
    }

    public function pdf(Invoice $factura): Response
    {
        $factura->load(['patient', 'tutor', 'charges', 'payments']);

        return Pdf::loadView('facturacion.facturas.pdf', compact('factura'))->download("{$factura->numero}.pdf");
    }

    public function pagar(Request $request, Invoice $factura, FacturacionService $servicio): RedirectResponse
    {
        $this->authorize('facturacion.gestionar');
        $datos = $request->validate([
            'monto' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'metodo' => ['required', Rule::in(['efectivo', 'tarjeta', 'yape', 'plin', 'transferencia'])],
            'referencia' => ['nullable', 'string', 'max:60'],
        ]);
        $servicio->registrarPago($factura, (float) $datos['monto'], $datos['metodo'], $datos['referencia'] ?? null, $request->user());

        return back()->with('success', 'Pago registrado.');
    }

    public function anular(Request $request, Invoice $factura, FacturacionService $servicio): RedirectResponse
    {
        $this->authorize('facturacion.anular');
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']]);
        $servicio->anular($factura, $datos['motivo'], $request->user());

        return back()->with('success', "Comprobante {$factura->numero} anulado. Los cargos volvieron a la cuenta del paciente.");
    }
}
