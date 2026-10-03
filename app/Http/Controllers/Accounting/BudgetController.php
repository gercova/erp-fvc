<?php

namespace App\Http\Controllers\Accounting;

use App\Exports\BudgetExecutionExport;
use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Business;
use App\Models\ChartOfAccount;
use App\Models\FundSource;
use App\Models\ProductiveActivity;
use App\Services\Budget\BudgetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class BudgetController extends Controller
{
    protected BudgetService $budgetService;

    public function __construct(BudgetService $budgetService)
    {
        $this->budgetService = $budgetService;
    }

    /**
     * List all budgets by fiscal year.
     */
    public function index(Request $request): View|JsonResponse
    {
        $year = (int) ($request->get('year') ?? date('Y'));

        $budgets = Budget::query()
            ->with(['createdByUser', 'approvedByUser'])
            ->when($year, fn($q) => $q->where('fiscal_year', $year))
            ->orderByDesc('fiscal_year')
            ->orderByDesc('id')
            ->get();

        $summaries = [];
        foreach ($budgets as $b) {
            $summaries[$b->id] = $this->budgetService->calculateBudgetExecution($b);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'budgets' => $budgets,
                'summaries' => $summaries,
            ]);
        }

        $years = Budget::select('fiscal_year')->distinct()->orderByDesc('fiscal_year')->pluck('fiscal_year')->toArray();
        if (!in_array((int) date('Y'), $years, true)) {
            array_unshift($years, (int) date('Y'));
        }

        return view('admin.accounting.budget.index', [
            'budgets'   => $budgets,
            'summaries' => $summaries,
            'year'      => $year,
            'years'     => $years,
        ]);
    }

    /**
     * Show form to create new budget.
     */
    public function create(): View
    {
        return view('admin.accounting.budget.create', [
            'currentYear' => (int) date('Y'),
        ]);
    }

    /**
     * Store new budget.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'fiscal_year'                => 'required|integer|min:2020|max:2050',
            'name'                       => 'required|string|max:255',
            'code'                       => 'nullable|string|max:60|unique:budgets,code',
            'alert_threshold_percentage' => 'nullable|numeric|min:1|max:100',
            'notes'                      => 'nullable|string',
        ]);

        $budget = $this->budgetService->createBudget($validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Presupuesto creado con éxito.',
                'budget'  => $budget,
            ]);
        }

        return redirect()->route('accounting.budgets.show', $budget->id)
            ->with('success', 'Presupuesto creado con éxito.');
    }

    /**
     * Display budget details and line execution status.
     */
    public function show(Request $request, int $id): View|JsonResponse
    {
        $budget = Budget::with(['lines.account', 'lines.activity', 'lines.fundSource', 'modifications.createdByUser', 'createdByUser', 'approvedByUser'])
            ->findOrFail($id);

        $month = $request->filled('month') ? (int) $request->month : null;
        $activityId = $request->filled('activity_id') ? (int) $request->activity_id : null;

        $execution = $this->budgetService->calculateBudgetExecution($budget, $month, $activityId);

        if ($request->wantsJson()) {
            return response()->json([
                'success'   => true,
                'budget'    => $budget,
                'execution' => $execution,
            ]);
        }

        $accounts = ChartOfAccount::orderBy('code')->get();
        $activities = ProductiveActivity::orderBy('order_index')->get();
        $fundSources = FundSource::orderBy('name')->get();

        return view('admin.accounting.budget.show', [
            'budget'      => $budget,
            'execution'   => $execution,
            'accounts'    => $accounts,
            'activities'  => $activities,
            'fundSources' => $fundSources,
            'month'       => $month,
            'activityId'  => $activityId,
        ]);
    }

    /**
     * Store new budget line.
     */
    public function storeLine(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $budget = Budget::findOrFail($id);

        $validated = $request->validate([
            'chart_of_account_id'        => 'nullable|exists:chart_of_accounts,id',
            'account_code'               => 'nullable|string|max:30',
            'category_name'              => 'nullable|string|max:150',
            'productive_activity_id'     => 'nullable|exists:productive_activities,id',
            'cost_center_code'           => 'nullable|string|max:60',
            'fund_source_id'             => 'nullable|exists:fund_sources,id',
            'period_month'               => 'required|integer|min:1|max:12',
            'allocated_amount'           => 'required|numeric|min:0',
            'alert_threshold_percentage' => 'nullable|numeric|min:1|max:100',
            'notes'                      => 'nullable|string',
        ]);

        $line = $this->budgetService->addBudgetLine($budget, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Partida presupuestal agregada con éxito.',
                'line'    => $line,
            ]);
        }

        return redirect()->route('accounting.budgets.show', $budget->id)
            ->with('success', 'Partida presupuestal agregada con éxito.');
    }

    /**
     * Store modification (allocation, cancellation, transfer).
     */
    public function storeModification(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $budget = Budget::findOrFail($id);

        $validated = $request->validate([
            'type'                       => 'required|string|in:ALLOCATION,CANCELLATION,TRANSFER',
            'amount'                     => 'required|numeric|min:0.01',
            'source_budget_line_id'      => 'nullable|exists:budget_lines,id',
            'destination_budget_line_id' => 'nullable|exists:budget_lines,id',
            'justification'              => 'required|string|min:5|max:1000',
        ]);

        $mod = $this->budgetService->applyModification($budget, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success'      => true,
                'message'      => 'Modificación presupuestal aplicada con éxito.',
                'modification' => $mod,
            ]);
        }

        return redirect()->route('accounting.budgets.show', $budget->id)
            ->with('success', 'Modificación presupuestal aplicada con éxito.');
    }

    /**
     * Submit budget for approval via DocumentApprovalService (Type 11).
     */
    public function submitApproval(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $budget = Budget::findOrFail($id);
        $approval = $this->budgetService->submitForApproval($budget, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Presupuesto enviado a flujo de aprobación oficial.',
                'approval' => $approval,
            ]);
        }

        return redirect()->route('accounting.budgets.show', $budget->id)
            ->with('success', 'Presupuesto enviado a flujo de aprobación oficial.');
    }

    /**
     * Activate an approved budget.
     */
    public function activate(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $budget = Budget::findOrFail($id);
        $this->budgetService->activateBudget($budget);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Presupuesto activado correctamente.',
                'budget'  => $budget->fresh(),
            ]);
        }

        return redirect()->route('accounting.budgets.show', $budget->id)
            ->with('success', 'Presupuesto activado correctamente.');
    }

    /**
     * Visual KPI Dashboard and Cost Center Matrix.
     */
    public function kpiDashboard(Request $request, int $id): View|JsonResponse
    {
        $budget = Budget::findOrFail($id);
        $execution = $this->budgetService->calculateBudgetExecution($budget);
        $matrix = $this->budgetService->getCostCenterExecutionMatrix($budget->fiscal_year);

        if ($request->wantsJson()) {
            return response()->json([
                'success'   => true,
                'budget'    => $budget,
                'execution' => $execution,
                'matrix'    => $matrix,
            ]);
        }

        return view('admin.accounting.budget.kpi_dashboard', [
            'budget'    => $budget,
            'execution' => $execution,
            'matrix'    => $matrix,
        ]);
    }

    /**
     * Check alerts and trigger notifications once.
     */
    public function checkAlerts(Request $request, int $id): JsonResponse
    {
        $budget = Budget::findOrFail($id);
        $alerts = $this->budgetService->checkThresholdAlerts($budget);

        return response()->json([
            'success'        => true,
            'alerts_created' => count($alerts),
            'alerts'         => $alerts,
        ]);
    }

    /**
     * Export budget execution to Excel.
     */
    public function exportExcel(Request $request, int $id)
    {
        $budget = Budget::findOrFail($id);
        $month = $request->filled('month') ? (int) $request->month : null;
        $execution = $this->budgetService->calculateBudgetExecution($budget, $month);
        $matrix = $this->budgetService->getCostCenterExecutionMatrix($budget->fiscal_year);

        $filename = "Presupuesto_{$budget->code}_Ejecucion_" . date('Ymd_His') . ".xlsx";

        return Excel::download(
            new BudgetExecutionExport($execution, $matrix, "Presupuesto {$budget->code} - {$budget->fiscal_year}"),
            $filename
        );
    }

    /**
     * Export budget execution to PDF.
     */
    public function exportPdf(Request $request, int $id)
    {
        $budget = Budget::findOrFail($id);
        $month = $request->filled('month') ? (int) $request->month : null;
        $execution = $this->budgetService->calculateBudgetExecution($budget, $month);
        $matrix = $this->budgetService->getCostCenterExecutionMatrix($budget->fiscal_year);
        $business = Business::find(1);

        $pdf = Pdf::loadView('admin.accounting.budget.budget_pdf', [
            'budget'    => $execution,
            'rawBudget' => $budget,
            'matrix'    => $matrix,
            'business'  => $business,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download("Presupuesto_{$budget->code}_Ejecucion_" . date('Ymd_His') . ".pdf");
    }
}
