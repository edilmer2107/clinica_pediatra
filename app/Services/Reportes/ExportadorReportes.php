<?php

namespace App\Services\Reportes;

use App\Reportes\Reporte;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportadorReportes
{
    public function __construct(private readonly AuditService $audit) {}

    public function pdf(Reporte $reporte, array $filtros, array $filas, array $resumen): Response
    {
        $this->audit->registrar('exportar_reporte', 'reportes', "Exportación PDF del reporte {$reporte->titulo()}.");

        return Pdf::loadView('reportes.pdf', compact('reporte', 'filtros', 'filas', 'resumen'))
            ->setPaper('a4', count($reporte->columnas()) > 6 ? 'landscape' : 'portrait')
            ->download($this->nombreArchivo($reporte, 'pdf'));
    }

    public function excel(Reporte $reporte, array $filtros, array $filas, array $resumen): StreamedResponse
    {
        $this->audit->registrar('exportar_reporte', 'reportes', "Exportación Excel del reporte {$reporte->titulo()}.");

        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $reporte->titulo()), 0, 31));

        $hoja->setCellValue('A1', 'Clínica Pediátrica con Farmacia Integrada');
        $hoja->setCellValue('A2', $reporte->titulo());
        $hoja->setCellValue('A3', 'Período: '.$filtros['desde'].' a '.$filtros['hasta'].' · Generado: '.now()->format('d/m/Y H:i'));
        $hoja->getStyle('A1:A2')->getFont()->setBold(true);

        $fila = 5;
        foreach (array_values($reporte->columnas()) as $i => $col) {
            $hoja->setCellValue([$i + 1, $fila], $col);
        }
        $ultimaCol = count($reporte->columnas());
        $rango = 'A'.$fila.':'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ultimaCol).$fila;
        $hoja->getStyle($rango)->getFont()->setBold(true);
        $hoja->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EDFF');

        foreach ($filas as $datos) {
            $fila++;
            foreach (array_values($datos) as $i => $valor) {
                $hoja->setCellValueExplicit([$i + 1, $fila], $this->texto($valor),
                    is_numeric($valor) ? \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC : \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $fila += 2;
        foreach ($resumen as $etiqueta => $valor) {
            $hoja->setCellValue([1, $fila], $etiqueta);
            $hoja->setCellValueExplicit([2, $fila], $this->texto($valor), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $hoja->getStyle([1, $fila])->getFont()->setBold(true);
            $fila++;
        }

        for ($c = 1; $c <= $ultimaCol; $c++) {
            $hoja->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($libro) {
            (new Xlsx($libro))->save('php://output');
        }, $this->nombreArchivo($reporte, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Evita inyección de fórmulas en Excel (CSV/formula injection). */
    private function texto(mixed $valor): string
    {
        $texto = (string) $valor;
        if ($texto !== '' && in_array($texto[0], ['=', '+', '-', '@'], true) && ! is_numeric($texto)) {
            return "'".$texto;
        }

        return $texto;
    }

    private function nombreArchivo(Reporte $reporte, string $ext): string
    {
        return $reporte->clave().'_'.now()->format('Ymd_His').'.'.$ext;
    }
}
