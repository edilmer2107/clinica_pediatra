<?php

namespace App\Services\Portal;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envío de SMS.
 * - driver "log": simulado, el mensaje queda en el log y se considera enviado.
 * - driver "android": usa la app "SMS Gateway for Android" (modo nube) instalada
 *   en el celular de la clínica; el SMS sale con el plan de ese celular.
 */
class SmsGateway
{
    /**
     * @return array{ok: bool, proveedor: string, id: ?string, error: ?string}
     */
    public function enviar(string $telefono, string $mensaje): array
    {
        $driver = config('clinica.sms.driver');
        $numero = $this->normalizar($telefono);

        if ($driver !== 'android') {
            Log::info("[SMS simulado] a {$numero}: {$mensaje}");

            return ['ok' => true, 'proveedor' => 'log', 'id' => null, 'error' => null];
        }

        try {
            $respuesta = Http::withBasicAuth((string) config('clinica.sms.usuario'), (string) config('clinica.sms.password'))
                ->timeout(15)
                ->acceptJson()
                ->post(config('clinica.sms.url'), [
                    'message' => $mensaje,
                    'phoneNumbers' => [$numero],
                ]);

            if ($respuesta->successful()) {
                return ['ok' => true, 'proveedor' => 'android', 'id' => (string) $respuesta->json('id'), 'error' => null];
            }

            return ['ok' => false, 'proveedor' => 'android', 'id' => null, 'error' => 'HTTP '.$respuesta->status().': '.mb_substr($respuesta->body(), 0, 200)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'proveedor' => 'android', 'id' => null, 'error' => mb_substr($e->getMessage(), 0, 255)];
        }
    }

    /** Convierte un celular peruano de 9 dígitos al formato internacional +51. */
    public function normalizar(string $telefono): string
    {
        $digitos = preg_replace('/\D/', '', $telefono);
        if (strlen($digitos) === 9) {
            return '+51'.$digitos;
        }

        return '+'.ltrim($digitos, '+');
    }
}
