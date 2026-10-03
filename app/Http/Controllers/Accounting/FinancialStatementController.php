<?php

namespace App\Http\Controllers\Accounting;

use App\Exports\FinancialStatementExport;
use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\ProductiveActivity;
use App\Services\Accounting\FinancialStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class FinancialStatementController extends Controller
{
    public function __construct(
        protected FinancialStatementService $statementService
    ) {}

    public function balanceSheet(Request $request): View|JsonResponse
    {
        $filters = [
            'period_id'      => $request->input('period_id'),
            'as_of_date'     => $request->input('as_of_date'),
            'cost_center_id' => $request->input('cost_center_id'),
        ];

        $comparisonFilters = null;
        if ($request->filled('comp_period_id') || $request->filled('comp_as_of_date')) {
            $comparisonFilters = [
                'period_id'      => $request->input('comp_period_id'),
                'as_of_date'     => $request->input('comp_as_of_date'),
                'cost_center_id' => $request->input('cost_center_id'),
            ];
        }

        $report = $this->statementService->getBalanceSheet($filters, $comparisonFilters);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'report'  => $report,
            ]);
        }

        $periods    = AccountingPeriod::orderBy('start_date', 'desc')->get();
        $activities = ProductiveActivity::active()->orderBy('name')->get();

        return view('admin.accounting.statements.balance_sheet', compact('report', 'periods', 'activities', 'filters', 'comparisonFilters'));
    }

    public function incomeStatementNature(Request $request): View|JsonResponse
    {
        $filters = [
            'period_id'      => $request->input('period_id'),
            'date_from'      => $request->input('date_from'),
            'date_to'        => $request->input('date_to'),
            'cost_center_id' => $request->input('cost_center_id'),
        ];

        $comparisonFilters = null;
        if ($request->filled('comp_period_id') || $request->filled('comp_date_from')) {
            $comparisonFilters = [
                'period_id'      => $request->input('comp_period_id'),
                'date_from'      => $request->input('comp_date_from'),
                'date_to'        => $request->input('comp_date_to'),
                'cost_center_id' => $request->input('cost_center_id'),
            ];
        }

        $report = $this->statementService->getIncomeStatementByNature($filters, $comparisonFilters);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'report'  => $report,
            ]);
        }

        $periods    = AccountingPeriod::orderBy('start_date', 'desc')->get();
        $activities = ProductiveActivity::active()->orderBy('name')->get();

        return view('admin.accounting.statements.income_statement_nature', compact('report', 'periods', 'activities', 'filters', 'comparisonFilters'));
    }

    public function incomeStatementFunction(Request $request): View|JsonResponse
    {
        $filters = [
            'period_id'      => $request->input('period_id'),
            'date_from'      => $request->input('date_from'),
            'date_to'        => $request->input('date_to'),
            'cost_center_id' => $request->input('cost_center_id'),
        ];

        $comparisonFilters = null;
        if ($request->filled('comp_period_id') || $request->filled('comp_date_from')) {
            $comparisonFilters = [
                'period_id'      => $request->input('comp_period_id'),
                'date_from'      => $request->input('comp_date_from'),
                'date_to'        => $request->input('comp_date_to'),
                'cost_center_id' => $request->input('cost_center_id'),
            ];
        }

        $report = $this->statementService->getIncomeStatementByFunction($filters, $comparisonFilters);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'report'  => $report,
            ]);
        }

        $periods    = AccountingPeriod::orderBy('start_date', 'desc')->get();
        $activities = ProductiveActivity::active()->orderBy('name')->get();

        return view('admin.accounting.statements.income_statement_function', compact('report', 'periods', 'activities', 'filters', 'comparisonFilters'));
    }

    public function exportPdf(Request $request, string $statement)
    {
        $report = $this->resolveReportByStatement($request, $statement);
        $title = $report['template']['name'] ?? 'Estado Financiero';

        $viewName = match ($statement) {
            'balance-sheet' => 'admin.accounting.statements.balance_sheet_pdf',
            default         => 'admin.accounting.statements.income_statement_pdf',
        };

        $pdf = Pdf::loadView($viewName, [
            'title'       => $title,
            'report'      => $report,
            'business'    => Business::query()->find(1),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download(str_replace(' ', '_', strtolower($statement)) . '-' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request, string $statement)
    {
        $report = $this->resolveReportByStatement($request, $statement);
        $title = $report['template']['name'] ?? 'Estado Financiero';

        return Excel::download(
            new FinancialStatementExport($report, $title),
            str_replace(' ', '_', strtolower($statement)) . '-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    protected function resolveReportByStatement(Request $request, string $statement): array
    {
        $filters = [
            'period_id'      => $request->input('period_id'),
            'as_of_date'     => $request->input('as_of_date'),
            'date_from'      => $request->input('date_from'),
            'date_to'        => $request->input('date_to'),
            'cost_center_id' => $request->input('cost_center_id'),
        ];

        $comparisonFilters = null;
        if ($request->filled('comp_period_id') || $request->filled('comp_as_of_date') || $request->filled('comp_date_from')) {
            $comparisonFilters = [
                'period_id'      => $request->input('comp_period_id'),
                'as_of_date'     => $request->input('comp_as_of_date'),
                'date_from'      => $request->input('comp_date_from'),
                'date_to'        => $request->input('comp_date_to'),
                'cost_center_id' => $request->input('cost_center_id'),
            ];
        }

        return match ($statement) {
            'nature'   => $this->statementService->getIncomeStatementByNature($filters, $comparisonFilters),
            'function' => $this->statementService->getIncomeStatementByFunction($filters, $comparisonFilters),
            default    => $this->statementService->getBalanceSheet($filters, $comparisonFilters),
        };
    }
}
