<?php

namespace App\Reportes\Portal;

use App\Models\NotificationLog;
use App\Reportes\Reporte;

class EfectividadNotificaciones extends Reporte
{
    public const TIPOS = ['recordatorio_cita' => 'Recordatorio de cita', 'vacuna_vencida' => 'Vacuna vencida', 'receta_lista' => 'Receta lista', 'resultado_listo' => 'Resultado listo'];

    public function clave(): string { return 'efectividad-notificaciones'; }

    public function titulo(): string { return 'Efectividad de notificaciones'; }

    public function modulo(): string { return 'Comunicación y Portal de Tutores'; }

    public function permiso(): string { return 'reportes.comunicacion'; }

    public function descripcion(): string
    {
        return 'Envío y apertura de recordatorios y alertas, para medir la efectividad de cada canal (correo/SMS).';
    }

    public function filtros(): array
    {
        return parent::filtros() + ['canal' => ['select', 'Canal', ['email' => 'Correo', 'sms' => 'SMS']]];
    }

    public function columnas(): array
    {
        return ['Canal', 'Tipo', 'Generadas', 'Enviadas', 'Fallidas', 'Abiertas', '% entrega', '% apertura (sobre enviadas)'];
    }

    public function filas(array $f): array
    {
        return NotificationLog::whereBetween('created_at', [$this->desde($f), $this->hasta($f)])
            ->when($f['canal'] ?? null, fn ($q, $c) => $q->where('canal', $c))
            ->get()->groupBy(fn ($n) => $n->canal.'|'.$n->tipo)
            ->map(function ($g) {
                $total = $g->count();
                $abiertas = $g->where('estado', 'abierto')->count();
                $enviadas = $g->whereIn('estado', ['enviado', 'abierto'])->count();

                return [
                    $g->first()->canal === 'sms' ? 'SMS' : 'Correo', self::TIPOS[$g->first()->tipo] ?? $g->first()->tipo,
                    $total, $enviadas, $g->where('estado', 'fallido')->count(), $abiertas,
                    round($enviadas / $total * 100, 1), $enviadas ? round($abiertas / $enviadas * 100, 1) : 0,
                ];
            })->sortBy(fn ($r) => $r[0].$r[1])->values()->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);
        $env = max(1, $c->sum(3));
        $res = ['Generadas' => $c->sum(2), 'Enviadas' => $c->sum(3), 'Apertura global' => round($c->sum(5) / $env * 100, 1).' %'];
        foreach (['Correo', 'SMS'] as $canal) {
            $x = $c->where(0, $canal);
            $res["Apertura {$canal}"] = round($x->sum(5) / max(1, $x->sum(3)) * 100, 1).' %';
        }

        return $res;
    }

    public function grafico(array $f, array $filas): ?array
    {
        return ['tipo' => 'bar', 'labels' => array_map(fn ($r) => $r[0].' · '.$r[1], $filas), 'series' => ['Enviadas' => array_column($filas, 3), 'Abiertas' => array_column($filas, 5)]];
    }
}
