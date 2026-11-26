<?php

namespace App\Reportes\Facturacion;

use App\Models\AuditLog;
use App\Reportes\Reporte;

class BitacoraAuditoria extends Reporte
{
    public function clave(): string { return 'bitacora-auditoria'; }

    public function titulo(): string { return 'Bitácora de auditoría'; }

    public function modulo(): string { return 'Facturación y Administración'; }

    public function permiso(): string { return 'auditoria.ver'; }

    public function descripcion(): string
    {
        return 'Listado inmutable de acciones críticas con usuario, fecha y hora.';
    }

    public function filtros(): array
    {
        return parent::filtros() + [
            'modulo' => ['select', 'Módulo', AuditLog::distinct()->orderBy('modulo')->pluck('modulo', 'modulo')->all()],
            'accion' => ['select', 'Acción', AuditLog::distinct()->orderBy('accion')->pluck('accion', 'accion')->all()],
        ];
    }

    protected function desdePorDefecto(): string
    {
        return today()->subMonth()->toDateString();
    }

    public function columnas(): array
    {
        return ['#', 'Fecha', 'Hora', 'Usuario', 'Módulo', 'Acción', 'Detalle', 'IP', 'Hash (SHA-256)'];
    }

    public function filas(array $f): array
    {
        return AuditLog::whereBetween('created_at', [$this->desde($f), $this->hasta($f)])
            ->when($f['modulo'] ?? null, fn ($q, $m) => $q->where('modulo', $m))
            ->when($f['accion'] ?? null, fn ($q, $a) => $q->where('accion', $a))
            ->orderByDesc('id')->limit(2000)->get()
            ->map(fn ($r) => [$r->id, $r->created_at->format('d/m/Y'), $r->created_at->format('H:i:s'), $r->user_name ?? 'Anónimo', $r->modulo, $r->accion, $r->descripcion, $r->ip, substr($r->hash, 0, 16)])
            ->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);

        return ['Registros' => $c->count(), 'Usuarios distintos' => $c->pluck(3)->unique()->count(), 'Accesos fallidos' => $c->where(5, 'login_fallido')->count()];
    }
}
