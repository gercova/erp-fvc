<?php

namespace App\Exports;

use App\Models\ActivityTransaction;
use App\Models\ActivityTransactionCategory;
use App\Models\ProductiveActivity;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivityDetailedReportExport implements WithMultipleSheets
{
    protected int $year;
    protected ?string $type;
    protected ?int $fundSourceId;

    public function __construct(int $year = 2025, ?string $type = null, ?int $fundSourceId = null)
    {
        $this->year = $year;
        $this->type = $type;
        $this->fundSourceId = $fundSourceId;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Hoja 1: Resumen General Consolidado (Matriz Actividad × Mes)
        $sheets[] = new ActivityIncomeExpenseExport($this->year, $this->type, $this->fundSourceId);

        // Hojas 2+: Una hoja detallada por cada Actividad Productiva
        $query = ProductiveActivity::query()->with(['head', 'defaultFundSource']);
        if ($this->type && $this->type !== 'ALL') {
            $query->where('type', $this->type);
        }
        $activities = $query->orderBy('order_index')->get();

        foreach ($activities as $activity) {
            $sheets[] = new ActivitySingleSheetExport($activity, $this->year, $this->fundSourceId);
        }

        return $sheets;
    }
}

class ActivitySingleSheetExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected ProductiveActivity $activity;
    protected int $year;
    protected ?int $fundSourceId;
    protected int $dataRowCount = 0;

    public function __construct(ProductiveActivity $activity, int $year, ?int $fundSourceId = null)
    {
        $this->activity = $activity;
        $this->year = $year;
        $this->fundSourceId = $fundSourceId;
    }

    public function title(): string
    {
        // Máximo 30 caracteres válidos para nombre de hoja en Excel
        $clean = preg_replace('/[\\\\\/*\?:\[\]]/', '', $this->activity->name);
        return mb_substr($clean, 0, 28);
    }

    public function array(): array
    {
        $rows = [];

        // Encabezado institucional
        $rows[] = ['INSTITUTO DE EDUCACIÓN SUPERIOR TECNOLÓGICO PÚBLICO "FRANCISCO VIGO CABALLERO" - UCHIZA'];
        $rows[] = ['ACTIVIDAD PRODUCTIVA Y EMPRESARIAL: ' . mb_strtoupper($this->activity->name) . ' (' . $this->activity->code . ')'];
        $rows[] = ['CENTRO DE COSTOS: ' . ($this->activity->cost_center_code ?? 'S/C') . ' | EJERCICIO FISCAL: ' . $this->year];
        $rows[] = ['RESPONSABLE: ' . ($this->activity->head?->nombres ?? 'No asignado') . ' | FUENTE: ' . ($this->activity->defaultFundSource?->name ?? 'Banco de la Nación')];
        $rows[] = [''];

        // Meses
        $months = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SET', 'OCT', 'NOV', 'DIC'];
        $header = array_merge(['RUBRO / CONCEPTO'], $months, ['TOTAL ANUAL']);

        // Bloque 1: INGRESOS
        $rows[] = ['I. INGRESOS OPERACIONALES'];
        $rows[] = $header;

        $incomeTrx = ActivityTransaction::where('productive_activity_id', $this->activity->id)
            ->where('period_year', $this->year)
            ->where('transaction_type', 'INCOME')
            ->where('status', '!=', 'ANULLED')
            ->when($this->fundSourceId, fn($q) => $q->where('fund_source_id', $this->fundSourceId))
            ->with('category')
            ->get();

        $incomeCategories = $incomeTrx->pluck('category')->unique('id')->filter();
        $monthlyIncomeTotals = array_fill(1, 12, 0.0);
        $totalAnnualIncome = 0.0;

        if ($incomeCategories->isEmpty()) {
            $rows[] = array_merge(['Sin ingresos registrados'], array_fill(0, 13, 0.0));
        } else {
            foreach ($incomeCategories as $cat) {
                $row = [$cat->name];
                $catTotal = 0.0;
                for ($m = 1; $m <= 12; $m++) {
                    $amt = (float) $incomeTrx->where('category_id', $cat->id)->where('period_month', $m)->sum('amount');
                    $row[] = $amt;
                    $monthlyIncomeTotals[$m] += $amt;
                    $catTotal += $amt;
                }
                $row[] = $catTotal;
                $totalAnnualIncome += $catTotal;
                $rows[] = $row;
            }
        }
        // Fila Total Ingresos
        $rows[] = array_merge(['TOTAL INGRESOS (I)'], array_values($monthlyIncomeTotals), [$totalAnnualIncome]);
        $rows[] = [''];

        // Bloque 2: EGRESOS
        $rows[] = ['II. EGRESOS Y COSTOS OPERACIONALES'];
        $rows[] = $header;

        $expenseTrx = ActivityTransaction::where('productive_activity_id', $this->activity->id)
            ->where('period_year', $this->year)
            ->where('transaction_type', 'EXPENSE')
            ->where('status', '!=', 'ANULLED')
            ->when($this->fundSourceId, fn($q) => $q->where('fund_source_id', $this->fundSourceId))
            ->with('category')
            ->get();

        $expenseCategories = $expenseTrx->pluck('category')->unique('id')->filter();
        $monthlyExpenseTotals = array_fill(1, 12, 0.0);
        $totalAnnualExpense = 0.0;

        if ($expenseCategories->isEmpty()) {
            $rows[] = array_merge(['Sin egresos registrados'], array_fill(0, 13, 0.0));
        } else {
            foreach ($expenseCategories as $cat) {
                $row = [$cat->name];
                $catTotal = 0.0;
                for ($m = 1; $m <= 12; $m++) {
                    $amt = (float) $expenseTrx->where('category_id', $cat->id)->where('period_month', $m)->sum('amount');
                    $row[] = $amt;
                    $monthlyExpenseTotals[$m] += $amt;
                    $catTotal += $amt;
                }
                $row[] = $catTotal;
                $totalAnnualExpense += $catTotal;
                $rows[] = $row;
            }
        }
        // Fila Total Egresos
        $rows[] = array_merge(['TOTAL EGRESOS (II)'], array_values($monthlyExpenseTotals), [$totalAnnualExpense]);
        $rows[] = [''];

        // Bloque 3: SALDO NETO MENSUAL
        $monthlyBalances = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyBalances[] = $monthlyIncomeTotals[$m] - $monthlyExpenseTotals[$m];
        }
        $finalAnnualBalance = $totalAnnualIncome - $totalAnnualExpense;
        $rows[] = array_merge(['SALDO NETO MENSUAL (I - II)'], $monthlyBalances, [$finalAnnualBalance]);

        $this->dataRowCount = count($rows);
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');
        $sheet->mergeCells('A3:N3');
        $sheet->mergeCells('A4:N4');

        $sheet->getStyle('A1:N4')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(13);
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        return [
            1 => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECEE']]],
        ];
    }
}
