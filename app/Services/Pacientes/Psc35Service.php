<?php

namespace App\Services\Pacientes;

use InvalidArgumentException;

/**
 * Lista de Síntomas Pediátricos (Pediatric Symptom Checklist, PSC-35).
 * Respuestas: 0 = Nunca, 1 = Algunas veces, 2 = Frecuentemente.
 * Punto de corte: ≥ 28 (6 a 16 años) o ≥ 24 (4 a 5 años).
 * Subescalas: Atención ≥ 7, Internalización ≥ 5, Externalización ≥ 7.
 */
class Psc35Service
{
    public const ITEMS = [
        1 => 'Se queja de dolores y malestares',
        2 => 'Pasa más tiempo solo(a)',
        3 => 'Se cansa fácilmente, tiene poca energía',
        4 => 'Es inquieto(a), incapaz de estar sentado(a) tranquilo(a)',
        5 => 'Tiene problemas con su profesor(a)',
        6 => 'Tiene menos interés en la escuela',
        7 => 'Actúa como si fuera manejado(a) por un motor',
        8 => 'Sueña despierto(a) demasiado',
        9 => 'Se distrae fácilmente',
        10 => 'Tiene miedo a situaciones nuevas',
        11 => 'Se siente triste, infeliz',
        12 => 'Es irritable, enojón(a)',
        13 => 'Se siente sin esperanza',
        14 => 'Tiene problemas para concentrarse',
        15 => 'Tiene menos interés en los amigos',
        16 => 'Pelea con otros niños',
        17 => 'Falta a la escuela',
        18 => 'Sus calificaciones escolares están bajando',
        19 => 'Se critica a sí mismo(a)',
        20 => 'Visita al médico y el médico no encuentra nada',
        21 => 'Tiene problemas para dormir',
        22 => 'Se preocupa mucho',
        23 => 'Quiere estar con usted más que antes',
        24 => 'Siente que es malo(a)',
        25 => 'Toma riesgos innecesarios',
        26 => 'Se lastima frecuentemente',
        27 => 'Parece divertirse menos',
        28 => 'Actúa como si fuera menor que los niños de su edad',
        29 => 'No obedece las reglas',
        30 => 'No demuestra sus sentimientos',
        31 => 'No comprende los sentimientos de otras personas',
        32 => 'Molesta o se burla de otros',
        33 => 'Culpa a otros por sus problemas',
        34 => 'Toma cosas que no le pertenecen',
        35 => 'Se niega a compartir',
    ];

    public const SUBESCALAS = [
        'atencion' => ['items' => [4, 7, 8, 9, 14], 'corte' => 7, 'nombre' => 'Atención'],
        'internalizacion' => ['items' => [11, 13, 19, 22, 27], 'corte' => 5, 'nombre' => 'Internalización'],
        'externalizacion' => ['items' => [16, 29, 31, 32, 33, 34, 35], 'corte' => 7, 'nombre' => 'Externalización'],
    ];

    public const EDAD_MINIMA_ANIOS = 4;

    public const EDAD_MAXIMA_ANIOS = 16;

    /**
     * @param  array<int|string, int|string>  $respuestas  [item => 0|1|2]
     * @return array{puntaje_total:int, puntaje_atencion:int, puntaje_internalizacion:int, puntaje_externalizacion:int, positivo:bool, interpretacion:string, respuestas:array}
     */
    public function evaluar(array $respuestas, int $edadAnios): array
    {
        if ($edadAnios < self::EDAD_MINIMA_ANIOS || $edadAnios > self::EDAD_MAXIMA_ANIOS) {
            throw new InvalidArgumentException('El PSC-35 se aplica a niños de 4 a 16 años.');
        }

        $limpias = [];
        foreach (array_keys(self::ITEMS) as $item) {
            $valor = $respuestas[$item] ?? $respuestas[(string) $item] ?? null;
            if (! in_array((string) $valor, ['0', '1', '2'], true)) {
                throw new InvalidArgumentException("Respuesta inválida o faltante en el ítem {$item}.");
            }
            $limpias[$item] = (int) $valor;
        }

        $total = array_sum($limpias);
        $sub = [];
        foreach (self::SUBESCALAS as $clave => $def) {
            $sub[$clave] = array_sum(array_intersect_key($limpias, array_flip($def['items'])));
        }

        $corte = self::puntoCorte($edadAnios);
        $positivo = $total >= $corte;

        $hallazgos = [];
        foreach (self::SUBESCALAS as $clave => $def) {
            if ($sub[$clave] >= $def['corte']) {
                $hallazgos[] = "{$def['nombre']} ({$sub[$clave]} ≥ {$def['corte']})";
            }
        }

        $interpretacion = $positivo
            ? "Cribado POSITIVO: puntaje {$total} ≥ {$corte}. Se recomienda evaluación por salud mental."
            : "Cribado negativo: puntaje {$total} < {$corte}.";
        if ($hallazgos) {
            $interpretacion .= ' Subescalas alteradas: '.implode(', ', $hallazgos).'.';
        }

        return [
            'respuestas' => $limpias,
            'puntaje_total' => $total,
            'puntaje_atencion' => $sub['atencion'],
            'puntaje_internalizacion' => $sub['internalizacion'],
            'puntaje_externalizacion' => $sub['externalizacion'],
            'positivo' => $positivo || $hallazgos !== [],
            'interpretacion' => $interpretacion,
        ];
    }

    public static function puntoCorte(int $edadAnios): int
    {
        return $edadAnios <= 5 ? 24 : 28;
    }
}
