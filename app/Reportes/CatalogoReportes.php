<?php

namespace App\Reportes;

use Illuminate\Support\Collection;

class CatalogoReportes
{
    /** @var array<int, class-string<Reporte>> */
    public const REPORTES = [
        // Módulo 1
        Pacientes\CoberturaVacunacion::class,
        Pacientes\VacunasPendientes::class,
        Pacientes\CrecimientoAlertas::class,
        Pacientes\Esavi::class,
        // Módulo 2
        Clinica\ProduccionMedica::class,
        Clinica\Morbilidad::class,
        Clinica\Ausentismo::class,
        // Módulo 3
        Farmacia\Vencimientos::class,
        Farmacia\Rotacion::class,
        Farmacia\Conciliacion::class,
        Farmacia\ErroresMedicacion::class,
        Farmacia\LibroControlados::class,
        // Módulo 4
        Facturacion\IngresosConsolidados::class,
        Facturacion\TicketPromedio::class,
        Facturacion\CuentasPorCobrar::class,
        Facturacion\BitacoraAuditoria::class,
        // Módulo 5
        Portal\EntregaDocumentos::class,
        Portal\EfectividadNotificaciones::class,
    ];

    /** @return Collection<string, Reporte> */
    public static function todos(): Collection
    {
        return collect(self::REPORTES)
            ->filter(fn ($clase) => class_exists($clase))
            ->map(fn ($clase) => app($clase))
            ->keyBy(fn (Reporte $r) => $r->clave());
    }

    public static function buscar(string $clave): ?Reporte
    {
        return self::todos()->get($clave);
    }
}
