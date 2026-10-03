<?php

namespace App\Services\Budget;

use App\Enums\JournalStatus;
use App\Models\Budget;
use App\Models\BudgetApproval;
use App\Models\BudgetLine;
use App\Models\BudgetModification;
use App\Models\Buy;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryLine;
use App\Models\ProductiveActivity;
use App\Models\Requisition;
use App\Models\User;
use App\Notifications\BudgetThresholdExceededNotification;
use App\Services\DocumentApprovalService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

class BudgetService
{
    protected DocumentApprovalService $approvalService;

    public function __construct(DocumentApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Create a new budget for a fiscal year.
     */
    public function createBudget(array $data, ?User $user = null): Budget
    {
        $fiscalYear = (int) ($data['fiscal_year'] ?? date('Y'));
        $name       = $data['name'] ?? "Presupuesto Institucional {$fiscalYear}";
        $threshold  = (float) ($data['alert_threshold_percentage'] ?? 90.00);

        return Budget::create([
            'fiscal_year'                => $fiscalYear,
            'name'                       => $name,
            'code'                       => $data['code'] ?? null,
            'status'                     => $data['status'] ?? 'DRAFT',
            'alert_threshold_percentage' => $threshold,
            'notes'                      => $data['notes'] ?? null,
            'created_by_user_id'         => $user?->id ?? auth()->id(),
        ]);
    }

    /**
     * Add a single budget line.
     */
    public function addBudgetLine(Budget $budget, array $lineData): BudgetLine
    {
        $month = (int) ($lineData['period_month'] ?? 1);
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException("El mes presupuestal debe estar entre 1 y 12.");
        }

        $allocated = (float) ($lineData['allocated_amount'] ?? 0.00);
        $modified  = (float) ($lineData['modified_amount'] ?? 0.00);
        $current   = (float) ($lineData['current_amount'] ?? ($allocated + $modified));

        // Resolve account code if chart_of_account_id is provided
        $accountCode = $lineData['account_code'] ?? null;
        if (!empty($lineData['chart_of_account_id']) && empty($accountCode)) {
            $account = ChartOfAccount::find($lineData['chart_of_account_id']);
            $accountCode = $account?->code;
        }

        // Resolve cost center code if productive_activity_id is provided
        $costCenterCode = $lineData['cost_center_code'] ?? null;
        if (!empty($lineData['productive_activity_id']) && empty($costCenterCode)) {
            $activity = ProductiveActivity::find($lineData['productive_activity_id']);
            $costCenterCode = $activity?->cost_center_code ?? $activity?->code;
        }

        $line = BudgetLine::create([
            'budget_id'                  => $budget->id,
            'line_code'                  => $lineData['line_code'] ?? null,
            'chart_of_account_id'        => $lineData['chart_of_account_id'] ?? null,
            'account_code'               => $accountCode,
            'category_name'              => $lineData['category_name'] ?? null,
            'productive_activity_id'     => $lineData['productive_activity_id'] ?? null,
            'cost_center_code'           => $costCenterCode,
            'fund_source_id'             => $lineData['fund_source_id'] ?? null,
            'period_month'               => $month,
            'allocated_amount'           => $allocated,
            'modified_amount'            => $modified,
            'current_amount'             => $current,
            'alert_threshold_percentage' => $lineData['alert_threshold_percentage'] ?? null,
            'notes'                      => $lineData['notes'] ?? null,
        ]);

        $budget->recalculateTotals();

        return $line;
    }

    /**
     * Add multiple budget lines in batch.
     */
    public function addBudgetLinesBatch(Budget $budget, array $linesData): Collection
    {
        $created = collect();
        DB::transaction(function () use ($budget, $linesData, &$created) {
            foreach ($linesData as $data) {
                $created->push($this->addBudgetLine($budget, $data));
            }
            $budget->recalculateTotals();
        });

        return $created;
    }

    /**
     * Apply budget modifications (Ampliaciones, Reducciones, Transferencias).
     */
    public function applyModification(Budget $budget, array $modData, ?User $user = null): BudgetModification
    {
        $type = strtoupper($modData['type'] ?? 'ALLOCATION');
        $amount = (float) ($modData['amount'] ?? 0.00);
        $justification = $modData['justification'] ?? 'Modificación presupuestal';

        if ($amount <= 0) {
            throw new InvalidArgumentException("El monto de la modificación debe ser mayor a 0.");
        }

        return DB::transaction(function () use ($budget, $modData, $type, $amount, $justification, $user) {
            $sourceLineId = $modData['source_budget_line_id'] ?? null;
            $destLineId   = $modData['destination_budget_line_id'] ?? null;

            $sourceLine = $sourceLineId ? BudgetLine::findOrFail($sourceLineId) : null;
            $destLine   = $destLineId ? BudgetLine::findOrFail($destLineId) : null;

            switch ($type) {
                case 'ALLOCATION': // Ampliación presupuestal / suplemento
                    if (!$destLine) {
                        throw new InvalidArgumentException("La ampliación requiere una partida de destino.");
                    }
                    $destLine->modified_amount += $amount;
                    $destLine->current_amount  += $amount;
                    $destLine->save();

                    $budget->current_total_amount += $amount;
                    $budget->save();
                    break;

                case 'CANCELLATION': // Reducción / anulación
                    if (!$sourceLine) {
                        throw new InvalidArgumentException("La anulación requiere una partida de origen.");
                    }
                    if ($sourceLine->current_amount < $amount) {
                        throw new DomainException("El monto a anular supera el presupuesto actual de la partida.");
                    }
                    $sourceLine->modified_amount -= $amount;
                    $sourceLine->current_amount  -= $amount;
                    $sourceLine->save();

                    $budget->current_total_amount -= $amount;
                    $budget->save();
                    break;

                case 'TRANSFER': // Transferencia entre partidas
                    if (!$sourceLine || !$destLine) {
                        throw new InvalidArgumentException("La transferencia requiere partida de origen y partida de destino.");
                    }
                    if ($sourceLine->current_amount < $amount) {
                        throw new DomainException("Saldo insuficiente en la partida de origen para transferir.");
                    }
                    $sourceLine->modified_amount -= $amount;
                    $sourceLine->current_amount  -= $amount;
                    $sourceLine->save();

                    $destLine->modified_amount += $amount;
                    $destLine->current_amount  += $amount;
                    $destLine->save();
                    // Total budget remains unchanged
                    break;

                default:
                    throw new InvalidArgumentException("Tipo de modificación no soportado: {$type}");
            }

            return BudgetModification::create([
                'budget_id'                  => $budget->id,
                'type'                       => $type,
                'source_budget_line_id'      => $sourceLineId,
                'destination_budget_line_id' => $destLineId,
                'amount'                     => $amount,
                'justification'              => $justification,
                'status'                     => 'APPLIED',
                'created_by_user_id'         => $user?->id ?? auth()->id(),
                'applied_at'                 => now(),
            ]);
        });
    }

    /**
     * Calculate execution stages (committed, accrued, paid) for a specific line.
     * Accrued is calculated strictly from posted journal entries.
     */
    public function calculateLineExecution(BudgetLine $line): array
    {
        $budget = $line->budget;
        $fiscalYear = $budget?->fiscal_year ?? date('Y');
        $month = $line->period_month;

        // 1. Accrued (Devengado): Net debits from posted journal entry lines
        $journalQuery = JournalEntryLine::query()
            ->whereHas('journalEntry', function ($q) use ($fiscalYear, $month) {
                $q->where('status', JournalStatus::POSTED)
                  ->whereYear('entry_date', $fiscalYear)
                  ->whereMonth('entry_date', $month);
            });

        // Filter by Account (Account code or prefix or specific account)
        if ($line->chart_of_account_id) {
            $account = $line->account;
            if ($account) {
                $journalQuery->whereHas('account', function ($q) use ($account) {
                    $q->where('code', 'like', $account->code . '%');
                });
            }
        } elseif (!empty($line->account_code)) {
            $journalQuery->whereHas('account', function ($q) use ($line) {
                $q->where('code', 'like', $line->account_code . '%');
            });
        }

        // Filter by Cost Center (Productive Activity)
        if ($line->productive_activity_id) {
            $journalQuery->where('cost_center_id', $line->productive_activity_id);
        }

        // Filter by Funding Source
        if ($line->fund_source_id) {
            $journalQuery->where('fund_source_id', $line->fund_source_id);
        }

        $debits = (float) $journalQuery->sum('debit');
        $credits = (float) $journalQuery->sum('credit');
        // In accounting, expense accounts (6, 9) increase with debits:
        $accruedAmount = round(max(0, $debits - $credits), 2);

        // 2. Committed (Comprometido): Approved requisitions or recorded purchases
        $committedAmount = $this->calculateCommittedAmount($fiscalYear, $month, $line, $accruedAmount);

        // 3. Paid (Girado / Pagado): Treasury disbursements
        $paidAmount = $this->calculatePaidAmount($fiscalYear, $month, $line, $accruedAmount);

        // Current modified budget
        $currentAmount = (float) $line->current_amount;
        $availableAmount = round($currentAmount - $accruedAmount, 2);

        $executionPercentage = $currentAmount > 0
            ? round(($accruedAmount / $currentAmount) * 100, 2)
            : 0.00;

        // Traffic Light calculation
        $threshold = (float) ($line->alert_threshold_percentage ?? $budget->alert_threshold_percentage ?? 90.00);
        $trafficLight = $this->resolveTrafficLight($executionPercentage, $threshold);

        return [
            'budget_line_id'       => $line->id,
            'period_month'         => $month,
            'account_code'         => $line->account?->code ?? $line->account_code,
            'category_name'        => $line->category_name,
            'cost_center_code'     => $line->cost_center_code,
            'activity_id'          => $line->productive_activity_id,
            'fund_source_id'       => $line->fund_source_id,
            'allocated_amount'     => (float) $line->allocated_amount,
            'modified_amount'      => (float) $line->modified_amount,
            'current_amount'       => $currentAmount,
            'committed_amount'     => $committedAmount,
            'accrued_amount'       => $accruedAmount,
            'paid_amount'          => $paidAmount,
            'available_amount'     => $availableAmount,
            'execution_percentage' => $executionPercentage,
            'threshold_percentage' => $threshold,
            'traffic_light'        => $trafficLight,
            'is_exceeded'          => $executionPercentage >= $threshold,
        ];
    }

    /**
     * Calculate overall budget execution and return line details.
     */
    public function calculateBudgetExecution(Budget $budget, ?int $month = null, ?int $activityId = null): array
    {
        $linesQuery = $budget->lines()->with(['account', 'activity', 'fundSource']);

        if ($month) {
            $linesQuery->where('period_month', $month);
        }
        if ($activityId) {
            $linesQuery->where('productive_activity_id', $activityId);
        }

        $lines = $linesQuery->orderBy('period_month')->get();

        $lineResults = [];
        $totalAllocated = 0.0;
        $totalModified  = 0.0;
        $totalCurrent   = 0.0;
        $totalCommitted = 0.0;
        $totalAccrued   = 0.0;
        $totalPaid      = 0.0;

        $trafficLightCounts = [
            'GREEN'  => 0,
            'YELLOW' => 0,
            'RED'    => 0,
        ];

        foreach ($lines as $line) {
            $exec = $this->calculateLineExecution($line);
            $lineResults[] = $exec;

            $totalAllocated += $exec['allocated_amount'];
            $totalModified  += $exec['modified_amount'];
            $totalCurrent   += $exec['current_amount'];
            $totalCommitted += $exec['committed_amount'];
            $totalAccrued   += $exec['accrued_amount'];
            $totalPaid      += $exec['paid_amount'];

            $trafficLightCounts[$exec['traffic_light']]++;
        }

        $totalAvailable = round($totalCurrent - $totalAccrued, 2);
        $overallPercentage = $totalCurrent > 0 ? round(($totalAccrued / $totalCurrent) * 100, 2) : 0.00;
        $threshold = (float) $budget->alert_threshold_percentage;

        return [
            'budget_id'             => $budget->id,
            'budget_code'           => $budget->code,
            'budget_name'           => $budget->name,
            'fiscal_year'           => $budget->fiscal_year,
            'status'                => $budget->status,
            'total_allocated'       => round($totalAllocated, 2),
            'total_modified'        => round($totalModified, 2),
            'total_current'         => round($totalCurrent, 2),
            'total_committed'       => round($totalCommitted, 2),
            'total_accrued'         => round($totalAccrued, 2),
            'total_paid'            => round($totalPaid, 2),
            'total_available'       => $totalAvailable,
            'overall_percentage'    => $overallPercentage,
            'traffic_light'         => $this->resolveTrafficLight($overallPercentage, $threshold),
            'traffic_light_counts'  => $trafficLightCounts,
            'lines'                 => $lineResults,
        ];
    }

    /**
     * Check budget lines against configured threshold and trigger notifications once.
     */
    public function checkThresholdAlerts(Budget $budget): array
    {
        $lines = $budget->lines()->with(['account', 'activity'])->get();
        $alertsGenerated = [];

        foreach ($lines as $line) {
            $exec = $this->calculateLineExecution($line);

            if ($exec['is_exceeded']) {
                // Check if notification has already been triggered for this line
                $alreadySent = $line->alert_sent_at !== null ||
                    DB::table('notifications')
                        ->where('type', BudgetThresholdExceededNotification::class)
                        ->where('data', 'like', '%"budget_line_id":' . $line->id . '%')
                        ->exists();

                if (!$alreadySent) {
                    $this->dispatchThresholdNotification($budget, $line, $exec);

                    $line->update(['alert_sent_at' => now()]);

                    $alertsGenerated[] = [
                        'budget_line_id'       => $line->id,
                        'account_code'         => $exec['account_code'],
                        'period_month'         => $line->period_month,
                        'execution_percentage' => $exec['execution_percentage'],
                        'threshold'            => $exec['threshold_percentage'],
                    ];
                }
            }
        }

        return $alertsGenerated;
    }

    /**
     * Generate Cost Center Execution Matrix (Activity x Month).
     * Calculated strictly from posted journal entries with zero data duplication.
     */
    public function getCostCenterExecutionMatrix(int $fiscalYear): array
    {
        $activities = ProductiveActivity::orderBy('order_index')->get();
        $budgetId = Budget::where('fiscal_year', $fiscalYear)->whereIn('status', ['APPROVED', 'ACTIVE', 'APROBADO'])->value('id')
            ?? Budget::where('fiscal_year', $fiscalYear)->value('id');

        $rows = [];
        $monthlyGrandBudget = array_fill(1, 12, 0.0);
        $monthlyGrandExecuted = array_fill(1, 12, 0.0);
        $annualGrandBudget = 0.0;
        $annualGrandExecuted = 0.0;

        foreach ($activities as $activity) {
            $months = [];
            $activityAnnualBudget = 0.0;
            $activityAnnualExecuted = 0.0;

            for ($m = 1; $m <= 12; $m++) {
                // 1. Budgeted amount from budget_lines
                $budgeted = 0.0;
                if ($budgetId) {
                    $budgeted = (float) BudgetLine::where('budget_id', $budgetId)
                        ->where('productive_activity_id', $activity->id)
                        ->where('period_month', $m)
                        ->sum('current_amount');
                }

                // 2. Executed amount from posted JournalEntryLine (debit - credit for expenses/costs)
                $executed = (float) JournalEntryLine::where('cost_center_id', $activity->id)
                    ->whereHas('journalEntry', function ($q) use ($fiscalYear, $m) {
                        $q->where('status', JournalStatus::POSTED)
                          ->whereYear('entry_date', $fiscalYear)
                          ->whereMonth('entry_date', $m);
                    })
                    ->selectRaw('COALESCE(SUM(debit - credit), 0) as net_accrued')
                    ->value('net_accrued');

                $executed = round(max(0, $executed), 2);
                $variance = round($budgeted - $executed, 2);
                $pct = $budgeted > 0 ? round(($executed / $budgeted) * 100, 2) : 0.00;

                $months[$m] = [
                    'month'                => $m,
                    'budgeted'             => $budgeted,
                    'executed'             => $executed,
                    'variance'             => $variance,
                    'execution_percentage' => $pct,
                    'traffic_light'        => $this->resolveTrafficLight($pct, 90.00),
                ];

                $activityAnnualBudget   += $budgeted;
                $activityAnnualExecuted += $executed;

                $monthlyGrandBudget[$m]   += $budgeted;
                $monthlyGrandExecuted[$m] += $executed;
            }

            $activityAnnualVariance = round($activityAnnualBudget - $activityAnnualExecuted, 2);
            $activityAnnualPct = $activityAnnualBudget > 0
                ? round(($activityAnnualExecuted / $activityAnnualBudget) * 100, 2)
                : 0.00;

            $rows[] = [
                'activity_id'          => $activity->id,
                'activity_code'        => $activity->code,
                'activity_name'        => $activity->name,
                'cost_center_code'     => $activity->cost_center_code,
                'months'               => $months,
                'annual_budgeted'      => round($activityAnnualBudget, 2),
                'annual_executed'      => round($activityAnnualExecuted, 2),
                'annual_variance'      => $activityAnnualVariance,
                'annual_percentage'    => $activityAnnualPct,
                'traffic_light'        => $this->resolveTrafficLight($activityAnnualPct, 90.00),
            ];

            $annualGrandBudget   += $activityAnnualBudget;
            $annualGrandExecuted += $activityAnnualExecuted;
        }

        $monthlyTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $b = round($monthlyGrandBudget[$m], 2);
            $e = round($monthlyGrandExecuted[$m], 2);
            $v = round($b - $e, 2);
            $p = $b > 0 ? round(($e / $b) * 100, 2) : 0.00;

            $monthlyTotals[$m] = [
                'month'                => $m,
                'budgeted'             => $b,
                'executed'             => $e,
                'variance'             => $v,
                'execution_percentage' => $p,
                'traffic_light'        => $this->resolveTrafficLight($p, 90.00),
            ];
        }

        $annualGrandVariance = round($annualGrandBudget - $annualGrandExecuted, 2);
        $annualGrandPct = $annualGrandBudget > 0 ? round(($annualGrandExecuted / $annualGrandBudget) * 100, 2) : 0.00;

        return [
            'fiscal_year'          => $fiscalYear,
            'budget_id'            => $budgetId,
            'activities'           => $rows,
            'monthly_totals'       => $monthlyTotals,
            'annual_grand_budget'  => round($annualGrandBudget, 2),
            'annual_grand_executed'=> round($annualGrandExecuted, 2),
            'annual_grand_variance'=> $annualGrandVariance,
            'annual_grand_pct'     => $annualGrandPct,
        ];
    }

    /**
     * Submit budget for approval via DocumentApprovalService (Type 11).
     */
    public function submitForApproval(Budget $budget, User $user): BudgetApproval
    {
        $budget->update(['status' => 'EN_REVISION']);

        $approval = BudgetApproval::firstOrCreate(
            ['budget_id' => $budget->id],
            [
                'status'             => 'PENDIENTE',
                'created_by_user_id' => $user->id,
                'notes'              => "Propuesta presupuestal para el ejercicio fiscal {$budget->fiscal_year}",
            ]
        );

        $this->approvalService->generateWorkflow($approval, $user);

        return $approval;
    }

    /**
     * Activate an approved budget.
     */
    public function activateBudget(Budget $budget): void
    {
        $budget->update([
            'status'       => 'ACTIVE',
            'activated_at' => now(),
        ]);
    }

    /**
     * Resolve traffic light status string based on percentage and threshold.
     */
    public function resolveTrafficLight(float $percentage, float $threshold): string
    {
        if ($percentage >= $threshold) {
            return 'RED';
        }
        if ($percentage >= max(0, $threshold - 20.00)) {
            return 'YELLOW';
        }
        return 'GREEN';
    }

    /**
     * Dispatch notification to appropriate stakeholders.
     */
    protected function dispatchThresholdNotification(Budget $budget, BudgetLine $line, array $exec): void
    {
        $notification = new BudgetThresholdExceededNotification(
            $budget,
            $line,
            $exec['accrued_amount'],
            $exec['execution_percentage'],
            $exec['threshold_percentage']
        );

        $recipients = collect();

        if ($budget->createdByUser) {
            $recipients->push($budget->createdByUser);
        }

        // Roles in charge of budget
        $roleUsers = User::role(['CONTABILIDAD', 'ADMINISTRACION'])->get();
        foreach ($roleUsers as $u) {
            $recipients->push($u);
        }

        // Activity head if specified
        if ($line->activity?->head) {
            $recipients->push($line->activity->head);
        }

        $uniqueRecipients = $recipients->unique('id');

        if ($uniqueRecipients->isNotEmpty()) {
            Notification::send($uniqueRecipients, $notification);
        }
    }

    /**
     * Calculate committed amount from approved requisitions / recorded purchases.
     */
    protected function calculateCommittedAmount(int $fiscalYear, int $month, BudgetLine $line, float $accruedAmount): float
    {
        $committed = 0.0;

        // Approved requisitions in that month and year
        $reqQuery = Requisition::where('status', 'APROBADO')
            ->whereYear('fecha', $fiscalYear)
            ->whereMonth('fecha', $month);

        if ($line->productive_activity_id && !empty($line->activity?->area_id)) {
            $reqQuery->where('area_id', $line->activity->area_id);
        }

        $committed += (float) $reqQuery->sum('total');

        // Recorded purchases (buys) in that month and year
        $buyQuery = Buy::whereNotIn('estado', ['ANULADO', 'CANCELADO'])
            ->whereYear('fecha_emision', $fiscalYear)
            ->whereMonth('fecha_emision', $month);

        $committed += (float) $buyQuery->sum('total');

        // Committed is legally at least equal to accrued
        return round(max($committed, $accruedAmount), 2);
    }

    /**
     * Calculate paid amount from treasury.
     */
    protected function calculatePaidAmount(int $fiscalYear, int $month, BudgetLine $line, float $accruedAmount): float
    {
        // Treasury disbursements: journal entries with credit on account 10 (Cash/Banks)
        $paidQuery = JournalEntryLine::query()
            ->whereHas('journalEntry', function ($q) use ($fiscalYear, $month) {
                $q->where('status', JournalStatus::POSTED)
                  ->whereYear('entry_date', $fiscalYear)
                  ->whereMonth('entry_date', $month);
            })
            ->whereHas('account', function ($q) {
                $q->where('code', 'like', '10%');
            })
            ->where('credit', '>', 0);

        if ($line->productive_activity_id) {
            $paidQuery->where('cost_center_id', $line->productive_activity_id);
        }

        if ($line->fund_source_id) {
            $paidQuery->where('fund_source_id', $line->fund_source_id);
        }

        $paid = (float) $paidQuery->sum('credit');

        // If no separate paid entry, it defaults to what's paid or capped at accrued
        return round(min($paid > 0 ? $paid : $accruedAmount, $accruedAmount), 2);
    }
}
