<?php

namespace App\Exports;

use App\Models\ActivityTransaction;
use App\Models\ProductiveActivity;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivityIncomeExpenseExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected int $year;
    protected ?string $type;
    protected ?int $fundSourceId;
    protected int $dataRowCount = 0;

    public function __construct(int $year, ?string $type = null, ?int $fundSourceId = null)
    {
        $this->year = $year;
        $this->type = $type;
        $this->fundSourceId = $fundSourceId;
    }

    public function title(): string
    {
        return 'RESUMEN ECONÓMICO ' . $this->year;
    }

    public function array(): array
    {
        $rows = [];

        // Encabezado institucional
        $rows[] = ['INSTITUTO DE EDUCACIÓN SUPERIOR TECNOLÓGICO PÚBLICO "FRANCISCO VIGO CABALLERO" - UCHIZA'];
        $rows[] = ['"Formando profesionales técnicos líderes en el Alto Huallaga"'];
        $rows[] = ['ESTADO CONSOLIDADO DE INGRESOS Y EGRESOS POR ACTIVIDADES PRODUCTIVAS Y EMPRESARIALES (APE)'];
        $rows[] = ['EJERCICIO FISCAL: ' . $this->year, '', '', '', '', '', '', '', '', '', '', 'FECHA DE EMISIÓN: ' . date('d/m/Y H:i')];
        $rows[] = [''];

        // Fila de encabezados
        $header = [
            'N°',
            'CÓDIGO',
            'ACTIVIDAD PRODUCTIVA',
            'TIPO',
            'C. COSTO',
            'RESPONSABLE',
        ];

        $months = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SET', 'OCT', 'NOV', 'DIC'];
        foreach ($months as $m) {
            $header[] = $m . ' ING';
            $header[] = $m . ' EGR';
            $header[] = $m . ' SALDO';
        }

        $header[] = 'TOTAL INGRESOS';
        $header[] = 'TOTAL EGRESOS';
        $header[] = 'SALDO FINAL';

        $rows[] = $header;

        // Obtener actividades
        $query = ProductiveActivity::query()->with('head');
        if ($this->type && $this->type !== 'ALL') {
            $query->where('type', $this->type);
        }
        $activities = $query->orderBy('order_index')->orderBy('name')->get();

        // Obtener transacciones del año
        $trxQuery = ActivityTransaction::where('period_year', $this->year)
            ->where('status', '!=', 'ANULLED');

        if ($this->fundSourceId) {
            $trxQuery->where('fund_source_id', $this->fundSourceId);
        }

        $transactions = $trxQuery->get();

        $monthTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthTotals[$m] = ['income' => 0, 'expense' => 0];
        }
        $grandTotalIncome = 0;
        $grandTotalExpense = 0;

        $index = 1;
        foreach ($activities as $act) {
            $actTrx = $transactions->where('productive_activity_id', $act->id);
            $row = [
                $index++,
                $act->code,
                $act->name,
                $act->type,
                $act->cost_center_code ?? 'S/C',
                $act->head?->nombres ?? 'No asignado',
            ];

            $actIncomeTotal = 0;
            $actExpenseTotal = 0;

            for ($m = 1; $m <= 12; $m++) {
                $mIncome = (float) $actTrx->where('period_month', $m)->where('transaction_type', 'INCOME')->sum('amount');
                $mExpense = (float) $actTrx->where('period_month', $m)->where('transaction_type', 'EXPENSE')->sum('amount');
                $mBalance = $mIncome - $mExpense;

                $row[] = number_format($mIncome, 2, '.', '');
                $row[] = number_format($mExpense, 2, '.', '');
                $row[] = number_format($mBalance, 2, '.', '');

                $monthTotals[$m]['income'] += $mIncome;
                $monthTotals[$m]['expense'] += $mExpense;

                $actIncomeTotal += $mIncome;
                $actExpenseTotal += $mExpense;
            }

            $actFinalBalance = $actIncomeTotal - $actExpenseTotal;
            $row[] = number_format($actIncomeTotal, 2, '.', '');
            $row[] = number_format($actExpenseTotal, 2, '.', '');
            $row[] = number_format($actFinalBalance, 2, '.', '');

            $grandTotalIncome += $actIncomeTotal;
            $grandTotalExpense += $actExpenseTotal;

            $rows[] = $row;
        }

        $this->dataRowCount = count($activities);

        // Fila de totales consolidados
        $totalRow = [
            'TOTALES',
            '',
            'CONSOLIDADO INSTITUCIONAL APE',
            '',
            '',
            '',
        ];

        for ($m = 1; $m <= 12; $m++) {
            $totInc = $monthTotals[$m]['income'];
            $totExp = $monthTotals[$m]['expense'];
            $totBal = $totInc - $totExp;

            $totalRow[] = number_format($totInc, 2, '.', '');
            $totalRow[] = number_format($totExp, 2, '.', '');
            $totalRow[] = number_format($totBal, 2, '.', '');
        }

        $grandFinalBalance = $grandTotalIncome - $grandTotalExpense;
        $totalRow[] = number_format($grandTotalIncome, 2, '.', '');
        $totalRow[] = number_format($grandTotalExpense, 2, '.', '');
        $totalRow[] = number_format($grandFinalBalance, 2, '.', '');

        $rows[] = $totalRow;

        // Espacio para firmas
        $rows[] = [''];
        $rows[] = [''];
        $rows[] = [''];
        $rows[] = [
            '',
            '_____________________________',
            '',
            '',
            '_____________________________',
            '',
            '',
            '_____________________________'
        ];
        $rows[] = [
            '',
            'RESPONSABLE DE ACTIVIDAD',
            '',
            '',
            'ADMINISTRACIÓN IESTP "FVC"',
            '',
            '',
            'DIRECCIÓN GENERAL'
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = 6 + $this->dataRowCount + 1; // Encabezado en fila 6, luego datos + total

        // Combinar títulos institucionales
        $sheet->mergeCells('A1:AO1');
        $sheet->mergeCells('A2:AO2');
        $sheet->mergeCells('A3:AO3');
        $sheet->mergeCells('A4:F4');

        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            2 => [
                'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            3 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '111111']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            4 => [
                'font' => ['bold' => true, 'size' => 10],
            ],
            6 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '004085']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            $lastRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => '002752']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1ECF1']
                ],
            ],
        ];

        // Bordes a toda la tabla
        $sheet->getStyle("A6:AO{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D7DE'],
                ],
            ],
        ]);

        return $styles;
    }
}
