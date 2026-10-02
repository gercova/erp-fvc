<?php

namespace App\Http\Controllers\Treasury;

use App\Http\Controllers\Controller;
use App\Models\FundSource;
use App\Models\ProductiveActivity;
use App\Services\Treasury\CashFlowReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CashFlowController extends Controller
{
    public function __construct(
        protected CashFlowReportService $cashFlowReportService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $fundSourceId = $request->input('fund_source_id') ? (int) $request->input('fund_source_id') : null;
        $activityId = $request->input('activity_id') ? (int) $request->input('activity_id') : null;

        $report = $this->cashFlowReportService->generateDirectCashFlow($dateFrom, $dateTo, $fundSourceId, $activityId);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'report'  => $report,
            ]);
        }

        $fundSources = FundSource::where('is_active', true)->get();
        $activities = ProductiveActivity::where('status', 'ACTIVA')->get();

        return view('admin.treasury.cash_flow.index', compact('report', 'fundSources', 'activities', 'dateFrom', 'dateTo', 'fundSourceId', 'activityId'));
    }

    public function data(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $fundSourceId = $request->input('fund_source_id') ? (int) $request->input('fund_source_id') : null;
        $activityId = $request->input('activity_id') ? (int) $request->input('activity_id') : null;

        $report = $this->cashFlowReportService->generateDirectCashFlow($dateFrom, $dateTo, $fundSourceId, $activityId);

        return response()->json([
            'success' => true,
            'report'  => $report,
        ]);
    }

    public function exportExcel(Request $request): Response
    {
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $fundSourceId = $request->input('fund_source_id') ? (int) $request->input('fund_source_id') : null;
        $activityId = $request->input('activity_id') ? (int) $request->input('activity_id') : null;

        $report = $this->cashFlowReportService->generateDirectCashFlow($dateFrom, $dateTo, $fundSourceId, $activityId);
        $csv = $this->cashFlowReportService->exportToCsv($report);

        $filename = "flujo_caja_directo_{$dateFrom}_{$dateTo}.csv";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
