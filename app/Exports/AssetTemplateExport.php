<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetTemplateExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected ?string $areaName;

    public function __construct(?string $areaName = null)
    {
        $this->areaName = $areaName ?? 'ÁREA INSTITUCIONAL';
    }

    public function array(): array
    {
        return [
            // Fila 1: Título Institucional
            ['INSTITUTO DE EDUCACIÓN SUPERIOR TECNOLÓGICO PÚBLICO "FRANCISCO VIGO CABALLERO" - UCHIZA'],
            ['"Formando técnicos líderes en el Alto Huallaga"'],
            [''],
            ['INVENTARIO GENERAL DE BIENES PATRIMONIALES Y ACTIVOS TANGIBLES'],
            ['DEPARTAMENTO / ÁREA: ' . mb_strtoupper($this->areaName)],
            ['RESPONSABLE: FELIX FRANCISCO BETSAIDA', '', '', '', '', '', '', '', '', '', '', '', 'FECHA DE INVENTARIO: ' . date('d/m/Y')],
            ['REALIZADO POR: Tec. OSORIO SANCHEZ, CECILIA ISABEL'],
            [''],
            // Fila 9: Encabezados Nivel 1
            [
                'Nº ORD',
                'CODIGO PRODUCTO',
                'CODIGO',
                'DESCRIPCIÓN',
                'MARCA',
                'MODELO',
                'SERIE',
                'COSTO',
                'CONDICIÓN', '', '', '',
                'TIPO ADQ.', '',
                'AÑO ADQ.',
                'UBICACIÓN',
                'OBSERVACIÓN'
            ],
            // Fila 10: Subencabezados Nivel 2
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'B', 'R', 'M', 'BAJA',
                'C', 'D',
                '',
                '',
                ''
            ],
            // Filas de ejemplo reales basadas en el formato institucional
            [
                '01',
                '',
                '0 7 - 2 2',
                'LAPTOP',
                'CORE 6',
                'SIN MODELO',
                'SIN SERIE',
                '0.00',
                'X', '', '', '',
                'X', '',
                '2022',
                'OFICINA PRINCIPAL',
                'EN OPERACIÓN'
            ],
            [
                '02',
                '74222358',
                '011 - 21',
                'IMPRESORA MULTIFUNCIONAL',
                'EPSON',
                'L3150',
                'SIN SERIE',
                '0.00',
                'X', '', '', '',
                'X', '',
                '2021',
                'OFICINA PRINCIPAL',
                ''
            ],
            [
                '03',
                '74644932',
                '00 5 - 17',
                'MESA DE MADERA',
                'SIN MARCA',
                'SIN MODELO',
                'SIN SERIE',
                '0.00',
                'X', '', '', '',
                'X', '',
                '2017',
                'OFICINA PRINCIPAL',
                ''
            ],
            [
                '04',
                '74648119',
                '047 - 11',
                'SILLA FIJA DE MADERA',
                'SIN MARCA',
                'SIN MODELO',
                'SIN SERIE',
                '0.00',
                'X', '', '', '',
                'X', '',
                '2011',
                'OFICINA PRINCIPAL',
                ''
            ],
            [
                '05',
                '74648119',
                '',
                'SILLA FIJA DE MADERA',
                'SIN MARCA',
                'SIN MODELO',
                'SIN SERIE',
                '0.00',
                'X', '', '', '',
                'X', '',
                '2017',
                'OFICINA PRINCIPAL',
                ''
            ]
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Merge headers for title
        $sheet->mergeCells('A1:Q1');
        $sheet->mergeCells('A2:Q2');
        $sheet->mergeCells('A4:Q4');
        $sheet->mergeCells('A5:Q5');

        // Merge headers in table header row 9
        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:B10');
        $sheet->mergeCells('C9:C10');
        $sheet->mergeCells('D9:D10');
        $sheet->mergeCells('E9:E10');
        $sheet->mergeCells('F9:F10');
        $sheet->mergeCells('G9:G10');
        $sheet->mergeCells('H9:H10');
        $sheet->mergeCells('I9:L9'); // CONDICIÓN
        $sheet->mergeCells('M9:N9'); // TIPO ADQ.
        $sheet->mergeCells('O9:O10');
        $sheet->mergeCells('P9:P10');
        $sheet->mergeCells('Q9:Q10');

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['italic' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            4 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            5 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Encabezados de tabla filas 9 y 10 (amarillo oficial como la captura)
            9 => [
                'font' => ['bold' => true, 'size' => 9],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFFF00'], // Yellow fill
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ],
            10 => [
                'font' => ['bold' => true, 'size' => 9],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFFF00'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Inventario Bienes';
    }
}
