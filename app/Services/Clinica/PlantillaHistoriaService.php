<?php

namespace App\Services\Clinica;

use App\Models\Patient;
use Carbon\Carbon;

/**
 * Plantillas de historia clínica diferenciadas por grupo etario.
 */
class PlantillaHistoriaService
{
    public const PLANTILLAS = [
        'neonato' => 'Neonato (0-28 días)',
        'lactante' => 'Lactante (29 días - 23 meses)',
        'preescolar' => 'Preescolar (2-5 años)',
        'escolar' => 'Escolar (6-11 años)',
        'adolescente' => 'Adolescente (12-17 años)',
    ];

    public static function determinar(Patient $paciente, ?Carbon $fecha = null): string
    {
        $fecha ??= now();
        $dias = (int) $paciente->fecha_nacimiento->diffInDays($fecha);
        $meses = (int) $paciente->fecha_nacimiento->diffInMonths($fecha);
        $anios = (int) $paciente->fecha_nacimiento->diffInYears($fecha);

        return match (true) {
            $dias <= 28 => 'neonato',
            $meses < 24 => 'lactante',
            $anios < 6 => 'preescolar',
            $anios < 12 => 'escolar',
            default => 'adolescente',
        };
    }

    /**
     * Campos del interrogatorio por plantilla: clave => [etiqueta, obligatorio].
     *
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function interrogatorio(string $plantilla): array
    {
        $comunes = [
            'sueno' => ['Sueño (horas, calidad, despertares)', true],
            'eliminaciones' => ['Deposiciones y micción', true],
        ];

        return match ($plantilla) {
            'neonato' => [
                'alimentacion' => ['Alimentación (lactancia materna, frecuencia, succión)', true],
                'ictericia' => ['Ictericia / coloración de piel', true],
                'cordon_umbilical' => ['Cordón umbilical', true],
                'tamizaje' => ['Tamizaje neonatal y auditivo', false],
            ] + $comunes,
            'lactante' => [
                'alimentacion' => ['Alimentación (lactancia, inicio y tipo de alimentación complementaria)', true],
                'suplementacion' => ['Suplementación de hierro / micronutrientes', true],
                'desarrollo' => ['Desarrollo psicomotor referido por el tutor', true],
            ] + $comunes,
            'preescolar' => [
                'alimentacion' => ['Alimentación (comidas al día, apetito, consumo de frutas y verduras)', true],
                'control_esfinteres' => ['Control de esfínteres', true],
                'conducta' => ['Conducta y juego', false],
                'escolaridad' => ['Inicial / guardería', false],
            ] + $comunes,
            'escolar' => [
                'alimentacion' => ['Alimentación y loncheras', true],
                'escolaridad' => ['Escolaridad (grado, rendimiento, dificultades)', true],
                'actividad_fisica' => ['Actividad física y pantallas', true],
                'conducta' => ['Conducta y relaciones con pares', false],
            ] + $comunes,
            'adolescente' => [
                'alimentacion' => ['Hábitos alimentarios e imagen corporal', true],
                'escolaridad' => ['Escolaridad y proyecto de vida', true],
                'desarrollo_puberal' => ['Desarrollo puberal (Tanner) / menarquia', true],
                'salud_mental' => ['Estado de ánimo, sustancias, riesgos (HEADSS)', true],
                'actividad_fisica' => ['Actividad física y pantallas', false],
            ] + $comunes,
            default => $comunes,
        };
    }

    /**
     * Examen físico segmentado por sistemas.
     *
     * @return array<string, string>
     */
    public static function examenFisico(string $plantilla): array
    {
        $base = [
            'estado_general' => 'Estado general',
            'piel' => 'Piel y faneras',
            'cabeza_cuello' => 'Cabeza y cuello',
            'orl' => 'Ojos, oídos, nariz y garganta',
            'torax_pulmones' => 'Tórax y pulmones',
            'cardiovascular' => 'Cardiovascular',
            'abdomen' => 'Abdomen',
            'genitourinario' => 'Genitourinario',
            'extremidades' => 'Extremidades y columna',
            'neurologico' => 'Neurológico',
        ];

        return match ($plantilla) {
            'neonato', 'lactante' => ['fontanelas' => 'Fontanelas', 'reflejos_primitivos' => 'Reflejos primitivos', 'caderas' => 'Caderas (Ortolani/Barlow)'] + $base,
            'adolescente' => $base + ['tanner' => 'Estadio de Tanner'],
            default => $base,
        };
    }
}
