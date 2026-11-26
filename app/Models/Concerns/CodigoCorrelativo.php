<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Genera un código correlativo legible (p. ej. RX-000031) a partir del id
 * asignado por la base de datos, evitando duplicados en registros concurrentes.
 *
 * El modelo debe definir las constantes PREFIJO_CODIGO y COLUMNA_CODIGO.
 */
trait CodigoCorrelativo
{
    protected static function bootCodigoCorrelativo(): void
    {
        static::creating(function ($modelo) {
            $columna = static::COLUMNA_CODIGO;
            if (empty($modelo->{$columna})) {
                $modelo->{$columna} = 'TMP-'.Str::upper(Str::random(8));
            }
        });

        static::created(function ($modelo) {
            $columna = static::COLUMNA_CODIGO;
            if (str_starts_with((string) $modelo->{$columna}, 'TMP-')) {
                $modelo->{$columna} = static::PREFIJO_CODIGO.'-'.str_pad((string) $modelo->getKey(), 6, '0', STR_PAD_LEFT);
                $modelo->saveQuietly();
            }
        });
    }
}
