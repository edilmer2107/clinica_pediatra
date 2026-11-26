<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Services\AuditService;
use App\Services\Portal\SmsGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificacionController extends Controller
{
    public function index(Request $request): View
    {
        $notificaciones = NotificationLog::with(['tutor', 'patient'])
            ->when($request->filled('canal'), fn ($q) => $q->where('canal', $request->query('canal')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->query('estado')))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->query('tipo')))
            ->latest('id')->paginate(25)->withQueryString();

        return view('portal.notificaciones', ['notificaciones' => $notificaciones, 'driver' => config('clinica.sms.driver')]);
    }

    /** Envío de un SMS de prueba para verificar la pasarela (solo administrador). */
    public function probarSms(Request $request, SmsGateway $sms, AuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->hasRole('administrador'), 403);
        $datos = $request->validate(['telefono' => ['required', 'regex:/^9\d{8}$/']], ['telefono.regex' => 'Ingrese un celular de 9 dígitos que empiece con 9.']);

        $resultado = $sms->enviar($datos['telefono'], 'Clínica Pediátrica: mensaje de prueba de la pasarela SMS.');
        $audit->registrar('prueba_sms', 'portal', "SMS de prueba a {$sms->normalizar($datos['telefono'])} vía {$resultado['proveedor']}: ".($resultado['ok'] ? 'enviado' : 'falló'));

        return back()->with($resultado['ok'] ? 'success' : 'error', $resultado['ok']
            ? "SMS de prueba enviado (proveedor: {$resultado['proveedor']})."
            : "No se pudo enviar el SMS: {$resultado['error']}");
    }
}
