<?php

namespace App\Http\Controllers;

use App\Enums\VoucherType;
use App\Exports\GeneralJournalExport;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Services\Accounting\LedgerReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class GeneralJournalController extends Controller
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

        $voucherTypes = VoucherType::cases();

        return view('admin.accounting.reports.journal', [
            'periods'      => $periods,
            'voucherTypes' => $voucherTypes,
            'defaultPeriod' => $periods->firstWhere('status', 'OPEN') ?? $periods->first(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $reportData = $this->ledgerReportService->getGeneralJournal($filters);

        return response()->json([
            'success' => true,
            'data'    => $reportData,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $filters = $this->extractFilters($request);
        unset($filters['per_page']); // Export all matching entries
        $reportData = $this->ledgerReportService->getGeneralJournal($filters);

        $pdf = Pdf::loadView('admin.accounting.reports.journal_pdf', [
            'title'       => 'LIBRO DIARIO - FORMATO 5.1',
            'entries'     => $reportData['entries'],
            'grandDebit'  => $reportData['grand_total_debit'],
            'grandCredit' => $reportData['grand_total_credit'],
            'isBalanced'  => $reportData['is_balanced'],
            'filters'     => $reportData['filters'],
            'business'    => Business::query()->find(1),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('libro-diario-' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->extractFilters($request);
        unset($filters['per_page']);
        $reportData = $this->ledgerReportService->getGeneralJournal($filters);

        return Excel::download(
            new GeneralJournalExport($reportData, 'Libro Diario - Formato 5.1'),
            'libro-diario-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function extractFilters(Request $request): array
    {
        return [
            'period_id'    => $request->filled('period_id') ? (int) $request->input('period_id') : null,
            'date_from'    => $request->input('date_from'),
            'date_to'      => $request->input('date_to'),
            'voucher_type' => $request->input('voucher_type'),
            'status'       => $request->input('status', 'POSTED'),
            'per_page'     => $request->filled('per_page') ? (int) $request->input('per_page') : 0,
            'page'         => $request->filled('page') ? (int) $request->input('page') : 1,
        ];
    }
}
