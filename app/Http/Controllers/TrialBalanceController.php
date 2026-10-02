<?php

namespace App\Http\Controllers;

use App\Exports\TrialBalanceExport;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\ProductiveActivity;
use App\Services\Accounting\LedgerReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TrialBalanceController extends Controller
{
    public function __construct(
        protected LedgerReportService $ledgerReportService
    ) {
    }

    public function index(Request $request)
    {
        $periods = AccountingPeriod::orderByDesc('fiscal_year')
            ->orderByDesc('month')
            ->get();

        $costCenters = ProductiveActivity::orderBy('name', 'asc')->get();

        return view('admin.accounting.reports.trial_balance', [
            'periods'       => $periods,
            'costCenters'   => $costCenters,
            'defaultPeriod' => $periods->firstWhere('status', 'OPEN') ?? $periods->first(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $reportData = $this->ledgerReportService->getTrialBalance($filters);

        return response()->json([
            'success' => true,
            'data'    => $reportData,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $filters = $this->extractFilters($request);
        $reportData = $this->ledgerReportService->getTrialBalance($filters);

        $pdf = Pdf::loadView('admin.accounting.reports.trial_balance_pdf', [
            'title'        => 'BALANCE DE COMPROBACIÓN (HOJA DE TRABAJO)',
            'accounts'     => $reportData['accounts'],
            'totals'       => $reportData['totals'],
            'verification' => $reportData['verification'],
            'filters'      => $reportData['filters'],
            'business'     => Business::query()->find(1),
            'generatedAt'  => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('balance-comprobacion-' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->extractFilters($request);
        $reportData = $this->ledgerReportService->getTrialBalance($filters);

        return Excel::download(
            new TrialBalanceExport($reportData, 'Balance de Comprobación'),
            'balance-comprobacion-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function extractFilters(Request $request): array
    {
        return [
            'period_id'      => $request->filled('period_id') ? (int) $request->input('period_id') : null,
            'date_from'      => $request->input('date_from'),
            'date_to'        => $request->input('date_to'),
            'digits'         => $request->filled('digits') ? (int) $request->input('digits') : 2,
            'cost_center_id' => $request->filled('cost_center_id') ? (int) $request->input('cost_center_id') : null,
            'status'         => $request->input('status', 'POSTED'),
        ];
    }
}
