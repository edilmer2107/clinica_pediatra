<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * Registra operaciones sensibles en la bitácora inmutable.
 * Cada registro se encadena con el hash del anterior (SHA-256), de modo que
 * cualquier alteración directa en la base de datos rompe la cadena.
 */
class AuditService
{
    public function registrar(
        string $accion,
        string $modulo,
        string $descripcion,
        ?Model $entidad = null,
        ?array $antes = null,
        ?array $despues = null,
    ): AuditLog {
        return DB::transaction(function () use ($accion, $modulo, $descripcion, $entidad, $antes, $despues) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('SELECT pg_advisory_xact_lock(424242)');
            }

            $usuario = Auth::user();
            $anterior = AuditLog::query()->latest('id')->value('hash');
            $ahora = now();

            $datos = [
                'user_id' => $usuario?->id,
                'user_name' => $usuario?->name,
                'accion' => $accion,
                'modulo' => $modulo,
                'entidad_tipo' => $entidad ? class_basename($entidad) : null,
                'entidad_id' => $entidad?->getKey(),
                'descripcion' => $descripcion,
                'datos_anteriores' => $antes,
                'datos_nuevos' => $despues,
                'ip' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 255),
                'hash_anterior' => $anterior,
                'created_at' => $ahora,
            ];
            $datos['hash'] = self::calcularHash($datos);

            return AuditLog::create($datos);
        });
    }

    public static function calcularHash(array $datos): string
    {
        $contenido = json_encode([
            $datos['hash_anterior'] ?? '',
            $datos['user_id'] ?? null,
            $datos['accion'],
            $datos['modulo'],
            $datos['entidad_tipo'] ?? null,
            $datos['entidad_id'] ?? null,
            $datos['descripcion'],
            $datos['datos_anteriores'] ?? null,
            $datos['datos_nuevos'] ?? null,
            $datos['created_at'] instanceof \DateTimeInterface
                ? $datos['created_at']->format('Y-m-d H:i:s')
                : (string) $datos['created_at'],
        ], JSON_UNESCAPED_UNICODE);

        return hash('sha256', $contenido);
    }

    /**
     * Verifica la integridad de toda la cadena.
     *
     * @return array{valida: bool, total: int, primer_error_id: int|null}
     */
    public function verificarCadena(): array
    {
        $anterior = null;
        $total = 0;

        foreach (AuditLog::query()->orderBy('id')->cursor() as $log) {
            $total++;
            $datos = $log->getAttributes();
            $datos['datos_anteriores'] = $log->datos_anteriores;
            $datos['datos_nuevos'] = $log->datos_nuevos;
            $datos['created_at'] = $log->created_at;

            if ($log->hash_anterior !== $anterior || self::calcularHash($datos) !== $log->hash) {
                return ['valida' => false, 'total' => $total, 'primer_error_id' => $log->id];
            }
            $anterior = $log->hash;
        }

        return ['valida' => true, 'total' => $total, 'primer_error_id' => null];
    }
}
