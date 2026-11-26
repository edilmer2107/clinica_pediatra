<?php

namespace App\Http\Controllers;

use App\Services\Portal\NotificacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Enlaces públicos de seguimiento: registran la apertura de correos (pixel) y SMS (enlace).
 * El token es aleatorio de 40 caracteres y no expone datos del paciente.
 */
class SeguimientoNotificacionController extends Controller
{
    public function __construct(private readonly NotificacionService $servicio) {}

    public function pixel(string $token): Response
    {
        $this->servicio->registrarApertura($token);
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, ['Content-Type' => 'image/gif', 'Cache-Control' => 'no-store']);
    }

    public function abrir(string $token): RedirectResponse
    {
        $this->servicio->registrarApertura($token);

        return redirect()->route('portal.index');
    }
}
