<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function array(): array
    {
        return [
            [
                'JUAN CARLOS PEREZ LOPEZ',
                'jperez',
                'jperez@empresa.com',
                '987654321',
                '12345678',
                'VENDEDOR',
                'CAJA PRINCIPAL',
                'ALMACEN PRINCIPAL',
                'ADM',
                'Asistente de Ventas',
                'CONTRATADO',
                '1',
            ],
            [
                'MARIA ELENA ROJAS TORRES',
                'mrojas',
                'mrojas@empresa.com',
                '987654322',
                '12345678',
                'ADMIN',
                'CAJA PRINCIPAL',
                'ALMACEN PRINCIPAL',
                'DG',
                'Directora',
                'NOMBRADO',
                '1',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Nombres',
            'Usuario',
            'Correo',
            'Telefono',
            'Contrasena',
            'Rol',
            'Caja',
            'Almacen',
            'Codigo_Area',
            'Cargo',
            'Condicion_Laboral',
            'Estado',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F2937'], // Neutral dark solid
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Plantilla Usuarios';
    }
}
