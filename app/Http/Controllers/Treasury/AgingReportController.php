<?php

namespace App\Http\Controllers\Treasury;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Provider;
use App\Services\Treasury\AgingReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgingReportController extends Controller
{
    public function __construct(
        protected AgingReportService $agingReportService
    ) {}

    public function receivables(Request $request): View|JsonResponse
    {
        $clientId = $request->input('client_id') ? (int) $request->input('client_id') : null;
        $asOfDate = $request->input('as_of_date');

        $report = $this->agingReportService->getReceivablesAging($clientId, $asOfDate);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'report'  => $report,
            ]);
        }

        $clients = Client::orderBy('nombres')->get();

        return view('admin.treasury.aging.receivables', compact('report', 'clients', 'clientId', 'asOfDate'));
    }

    public function payables(Request $request): View|JsonResponse
    {
        $providerId = $request->input('provider_id') ? (int) $request->input('provider_id') : null;
        $asOfDate = $request->input('as_of_date');

        $report = $this->agingReportService->getPayablesAging($providerId, $asOfDate);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'report'  => $report,
            ]);
        }

        $providers = Provider::orderBy('nombres')->get();

        return view('admin.treasury.aging.payables', compact('report', 'providers', 'providerId', 'asOfDate'));
    }
}
