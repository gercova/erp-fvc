<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Services\Accounting\PeriodClosingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeriodClosingController extends Controller
{
    public function __construct(
        protected PeriodClosingService $closingService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $fiscalYear = $request->input('fiscal_year', now()->year);

        $periods = AccountingPeriod::forYear((int) $fiscalYear)
            ->with(['closures.approvals', 'closingEntry', 'openingEntry', 'closedBy', 'reopenedBy'])
            ->orderBy('month')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'periods' => $periods,
            ]);
        }

        $years = AccountingPeriod::select('fiscal_year')->distinct()->orderBy('fiscal_year', 'desc')->pluck('fiscal_year');

        return view('admin.accounting.statements.closing.index', compact('periods', 'fiscalYear', 'years'));
    }

    public function close(Request $request, int $periodId): RedirectResponse|JsonResponse
    {
        $period = AccountingPeriod::findOrFail($periodId);
        $notes = $request->input('notes');

        $closed = $this->closingService->closeMonthlyPeriod($period, auth()->id() ?? 1, $notes);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "El período {$closed->period_code} ha sido cerrado correctamente.",
                'period'  => $closed,
            ]);
        }

        return redirect()->back()->with('success', "El período {$closed->period_code} ha sido cerrado correctamente.");
    }

    public function annualClose(Request $request, int $periodId): RedirectResponse|JsonResponse
    {
        $period = AccountingPeriod::findOrFail($periodId);
        $notes = $request->input('notes');

        $result = $this->closingService->closeAnnualPeriod($period, auth()->id() ?? 1, $notes);

        if ($request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'message'    => "Cierre anual del ejercicio {$period->fiscal_year} ejecutado con éxito.",
                'net_result' => $result['net_result'],
                'period'     => $result['period'],
            ]);
        }

        return redirect()->back()->with('success', "Cierre anual del ejercicio {$period->fiscal_year} ejecutado con éxito.");
    }

    public function reopen(Request $request, int $periodId): RedirectResponse|JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $period = AccountingPeriod::findOrFail($periodId);
        $reopened = $this->closingService->reopenPeriod($period, auth()->id() ?? 1, $request->input('reason'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "El período {$reopened->period_code} ha sido reabierto con éxito.",
                'period'  => $reopened,
            ]);
        }

        return redirect()->back()->with('success', "El período {$reopened->period_code} ha sido reabierto con éxito.");
    }

    public function submitApproval(Request $request, int $periodId): RedirectResponse|JsonResponse
    {
        $period = AccountingPeriod::findOrFail($periodId);
        $closureType = $request->input('closure_type', 'MONTHLY');
        $notes = $request->input('notes');

        $closure = $this->closingService->submitClosureForApproval($period, $closureType, auth()->user(), $notes);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Expediente de cierre de período {$period->period_code} enviado a cadena de aprobación.",
                'closure' => $closure,
            ]);
        }

        return redirect()->back()->with('success', "Expediente de cierre de período {$period->period_code} enviado a cadena de firmas.");
    }
}
