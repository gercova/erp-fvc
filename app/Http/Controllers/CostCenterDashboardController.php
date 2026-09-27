<?php

namespace App\Http\Controllers;

use App\Exports\ActivityDetailedReportExport;
use App\Exports\ActivityIncomeExpenseExport;
use App\Imports\EconomicReportHistoricalImport;
use App\Models\ActivityTransaction;
use App\Models\FundSource;
use App\Models\ProductiveActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CostCenterDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $year = (int) $request->input('year', date('Y'));
        $selectedType = $request->input('type', 'ALL');
        $selectedFundSourceId = $request->input('fund_source_id');

        $types = [
            'ALL' => 'Todas las Actividades',
            'AGRICULTURAL' => 'Agrícola',
            'FORESTRY' => 'Forestal',
            'AQUACULTURE' => 'Piscícola',
            'LIVESTOCK' => 'Pecuario',
            'INSTITUTIONAL' => 'Institucional',
            'SERVICES' => 'Servicios / Idiomas'
        ];

        $fundSources = FundSource::where('is_active', true)->orderBy('name')->get();

        // Obtener la matriz analítica Actividad × Mes
        $matrixData = $this->buildCrossTabulation($year, $selectedType, $selectedFundSourceId);

        return view('admin.productive_activities.dashboard.cost_center', compact(
            'year',
            'selectedType',
            'selectedFundSourceId',
            'types',
            'fundSources',
            'matrixData'
        ));
    }

    public function getData(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', date('Y'));
        $type = $request->input('type', 'ALL');
        $fundSourceId = $request->input('fund_source_id');

        $data = $this->buildCrossTabulation($year, $type, $fundSourceId);

        return response()->json($data);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $year = (int) $request->input('year', date('Y'));
        $type = $request->input('type', 'ALL');
        $fundSourceId = $request->input('fund_source_id') ? (int) $request->input('fund_source_id') : null;

        $fileName = sprintf('Reporte_Economico_Resumen_APE_%d_%s.xlsx', $year, date('Ymd_His'));

        return Excel::download(new ActivityIncomeExpenseExport($year, $type, $fundSourceId), $fileName);
    }

    public function exportDetailedExcel(Request $request): BinaryFileResponse
    {
        $year = (int) $request->input('year', date('Y'));
        $type = $request->input('type', 'ALL');
        $fundSourceId = $request->input('fund_source_id') ? (int) $request->input('fund_source_id') : null;

        $fileName = sprintf('Informe_Economico_MultiHoja_APE_%d_%s.xlsx', $year, date('Ymd_His'));

        return Excel::download(new ActivityDetailedReportExport($year, $type, $fundSourceId), $fileName);
    }

    public function importExcel(Request $request): JsonResponse
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:20480',
            'year'       => 'nullable|integer|min:2020|max:2030',
        ]);

        $year = (int) $request->input('year', date('Y'));
        $file = $request->file('excel_file');

        try {
            $importer = new EconomicReportHistoricalImport($year);
            $summary = $importer->import($file->getRealPath());

            return response()->json([
                'status'  => 'success',
                'message' => 'Migración de archivo histórico completada.',
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error durante el procesamiento del archivo Excel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Construye la matriz de tabulación cruzada (Actividad × Mes)
     * replicando fielmente la estructura de la hoja RESUMEN de Germán Cotrina
     */
    protected function buildCrossTabulation(int $year, string $type = 'ALL', ?int $fundSourceId = null): array
    {
        $query = ProductiveActivity::query()->with('head');
        if ($type !== 'ALL') {
            $query->where('type', $type);
        }
        $activities = $query->orderBy('order_index')->orderBy('name')->get();

        $trxQuery = ActivityTransaction::where('period_year', $year)
            ->where('status', '!=', 'ANULLED');

        if ($fundSourceId) {
            $trxQuery->where('fund_source_id', $fundSourceId);
        }

        $transactions = $trxQuery->get();

        $rows = [];
        $monthTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthTotals[$m] = ['income' => 0.0, 'expense' => 0.0, 'balance' => 0.0];
        }

        $grandTotalIncome = 0.0;
        $grandTotalExpense = 0.0;

        foreach ($activities as $act) {
            $actTrx = $transactions->where('productive_activity_id', $act->id);

            $monthsData = [];
            $actTotalIncome = 0.0;
            $actTotalExpense = 0.0;

            for ($m = 1; $m <= 12; $m++) {
                $mIncome = (float) $actTrx->where('period_month', $m)->where('transaction_type', 'INCOME')->sum('amount');
                $mExpense = (float) $actTrx->where('period_month', $m)->where('transaction_type', 'EXPENSE')->sum('amount');
                $mBalance = $mIncome - $mExpense;

                $monthsData[$m] = [
                    'income' => $mIncome,
                    'expense' => $mExpense,
                    'balance' => $mBalance,
                ];

                $monthTotals[$m]['income'] += $mIncome;
                $monthTotals[$m]['expense'] += $mExpense;
                $monthTotals[$m]['balance'] += $mBalance;

                $actTotalIncome += $mIncome;
                $actTotalExpense += $mExpense;
            }

            $actFinalBalance = $actTotalIncome - $actTotalExpense;
            $grandTotalIncome += $actTotalIncome;
            $grandTotalExpense += $actTotalExpense;

            $rows[] = [
                'id' => $act->id,
                'code' => $act->code,
                'name' => $act->name,
                'type' => $act->type,
                'cost_center' => $act->cost_center_code ?? 'S/C',
                'head_name' => $act->head?->nombres ?? 'Sin Asignar',
                'months' => $monthsData,
                'total_income' => $actTotalIncome,
                'total_expense' => $actTotalExpense,
                'final_balance' => $actFinalBalance,
            ];
        }

        $grandFinalBalance = $grandTotalIncome - $grandTotalExpense;

        return [
            'year' => $year,
            'activities' => $rows,
            'month_totals' => $monthTotals,
            'grand_totals' => [
                'income' => $grandTotalIncome,
                'expense' => $grandTotalExpense,
                'balance' => $grandFinalBalance,
            ],
            'chart_data' => [
                'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic'],
                'incomes' => array_column($monthTotals, 'income'),
                'expenses' => array_column($monthTotals, 'expense'),
                'balances' => array_column($monthTotals, 'balance'),
            ],
            'activity_chart_data' => [
                'labels'   => array_column($rows, 'name'),
                'codes'    => array_column($rows, 'code'),
                'incomes'  => array_column($rows, 'total_income'),
                'expenses' => array_column($rows, 'total_expense'),
                'balances' => array_column($rows, 'final_balance'),
            ],
        ];
    }
}
