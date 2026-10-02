<?php

namespace App\Http\Controllers;

use App\Exports\GeneralLedgerExport;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\ChartOfAccount;
use App\Models\ProductiveActivity;
use App\Services\Accounting\LedgerReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class GeneralLedgerController extends Controller
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

        $accounts = ChartOfAccount::movement()
            ->orderBy('code', 'asc')
            ->get();

        $costCenters = ProductiveActivity::orderBy('name', 'asc')->get();

        return view('admin.accounting.reports.ledger', [
            'periods'       => $periods,
            'accounts'      => $accounts,
            'costCenters'   => $costCenters,
            'defaultPeriod' => $periods->firstWhere('status', 'OPEN') ?? $periods->first(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $reportData = $this->ledgerReportService->getGeneralLedger($filters);

        return response()->json([
            'success' => true,
            'data'    => $reportData,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $filters = $this->extractFilters($request);
        $reportData = $this->ledgerReportService->getGeneralLedger($filters);

        $pdf = Pdf::loadView('admin.accounting.reports.ledger_pdf', [
            'title'       => 'LIBRO MAYOR - FORMATO 6.1',
            'accounts'    => $reportData['accounts'],
            'summary'     => $reportData['summary'],
            'filters'     => $reportData['filters'],
            'business'    => Business::query()->find(1),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('libro-mayor-' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->extractFilters($request);
        $reportData = $this->ledgerReportService->getGeneralLedger($filters);

        return Excel::download(
            new GeneralLedgerExport($reportData, 'Libro Mayor - Formato 6.1'),
            'libro-mayor-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function extractFilters(Request $request): array
    {
        return [
            'account_id'           => $request->filled('account_id') ? (int) $request->input('account_id') : null,
            'account_code'         => $request->input('account_code'),
            'account_from'         => $request->input('account_from'),
            'account_to'           => $request->input('account_to'),
            'period_id'            => $request->filled('period_id') ? (int) $request->input('period_id') : null,
            'date_from'            => $request->input('date_from'),
            'date_to'              => $request->input('date_to'),
            'cost_center_id'       => $request->filled('cost_center_id') ? (int) $request->input('cost_center_id') : null,
            'third_party_document' => $request->input('third_party_document'),
            'third_party_id'       => $request->filled('third_party_id') ? (int) $request->input('third_party_id') : null,
            'status'               => $request->input('status', 'POSTED'),
        ];
    }
}
