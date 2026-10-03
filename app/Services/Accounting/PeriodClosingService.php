<?php

namespace App\Services\Accounting;

use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AccountingPeriod;
use App\Models\AccountingPeriodAuditLog;
use App\Models\AccountingPeriodClosure;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\DocumentApprovalService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeriodClosingService
{
    public function __construct(
        protected JournalPostingService $journalPostingService,
        protected FinancialStatementService $statementService,
        protected DocumentApprovalService $approvalService
    ) {}

    /**
     * Perform monthly period closing.
     * Sets accounting_periods.status = CLOSED, blocks direct entry posting, and logs audit trail.
     */
    public function closeMonthlyPeriod(AccountingPeriod $period, int $userId, ?string $notes = null): AccountingPeriod
    {
        if ($period->isClosed()) {
            throw new DomainException("El período {$period->period_code} ya se encuentra cerrado o bloqueado.");
        }

        return DB::transaction(function () use ($period, $userId, $notes) {
            $fromStatus = $period->status;

            $period->update([
                'status'             => 'CLOSED',
                'closed_by_user_id'  => $userId,
                'closed_at'          => now(),
                'notes'              => $notes ? ($period->notes . "\n" . $notes) : $period->notes,
            ]);

            AccountingPeriodAuditLog::create([
                'accounting_period_id' => $period->id,
                'user_id'              => $userId,
                'action'               => 'CLOSE',
                'from_status'          => $fromStatus,
                'to_status'            => 'CLOSED',
                'reason'               => $notes ?? 'Cierre mensual regular de operaciones contables',
                'ip_address'           => request()->ip() ?? '127.0.0.1',
                'user_agent'           => request()->userAgent() ?? 'CLI/System',
                'metadata'             => [
                    'period_code' => $period->period_code,
                    'fiscal_year' => $period->fiscal_year,
                    'month'       => $period->month,
                ],
            ]);

            AccountingAuditService::log(
                'CLOSE_PERIOD',
                $period,
                User::find($userId),
                $notes ?? 'Cierre mensual regular de operaciones contables',
                ['from_status' => $fromStatus, 'to_status' => 'CLOSED']
            );

            return $period;
        });
    }

    /**
     * Perform annual period closing.
     * 1. Generates closing entry for result accounts (Class 6, 7, 8, 9 closed to 59/89).
     * 2. Generates closing entry for balance sheet accounts (Class 1, 2, 3 against 4, 5).
     * 3. Generates opening entry for the next fiscal year.
     * 4. Sets period status to LOCKED.
     */
    public function closeAnnualPeriod(AccountingPeriod $period, int $userId, ?string $notes = null): array
    {
        return DB::transaction(function () use ($period, $userId, $notes) {
            $fiscalYear = $period->fiscal_year;
            $yearEndDate = Carbon::parse("{$fiscalYear}-12-31")->toDateString();

            // 1. Calculate cumulative balances for all accounts for the fiscal year
            $accountSums = JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
                ->where('journal_entries.status', JournalStatus::POSTED->value)
                ->whereYear('journal_entries.entry_date', $fiscalYear)
                ->where('journal_entries.entry_type', '!=', VoucherType::CLOSING->value)
                ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.element')
                ->select(
                    'chart_of_accounts.id',
                    'chart_of_accounts.code',
                    'chart_of_accounts.name',
                    'chart_of_accounts.element',
                    DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                    DB::raw('SUM(journal_entry_lines.credit) as total_credit')
                )
                ->get();

            // 2. Separate Income/Expense accounts (Elements 6, 7, 8, 9) and Balance Sheet accounts (Elements 1, 2, 3, 4, 5)
            $incomeExpenseLines = [];
            $balanceSheetLines = [];

            $totalRevenues = 0.00;
            $totalExpenses = 0.00;

            foreach ($accountSums as $acc) {
                $netDebit = (float) $acc->total_debit - (float) $acc->total_credit;
                $netCredit = (float) $acc->total_credit - (float) $acc->total_debit;

                if (in_array($acc->element, [6, 7, 8, 9])) {
                    if (abs($netDebit) > 0.001 || abs($netCredit) > 0.001) {
                        if ($acc->element == 7 || $netCredit > 0) {
                            // Revenue: credit balance. To close, debit it.
                            $incomeExpenseLines[] = [
                                'account_id' => $acc->id,
                                'debit'      => round(abs($netCredit), 2),
                                'credit'     => 0.00,
                                'glosa'      => "Cierre ejercicio {$fiscalYear}: Cancelación cta {$acc->code}",
                            ];
                            $totalRevenues += round(abs($netCredit), 2);
                        } else {
                            // Expense / Cost: debit balance. To close, credit it.
                            $incomeExpenseLines[] = [
                                'account_id' => $acc->id,
                                'debit'      => 0.00,
                                'credit'     => round(abs($netDebit), 2),
                                'glosa'      => "Cierre ejercicio {$fiscalYear}: Cancelación cta {$acc->code}",
                            ];
                            $totalExpenses += round(abs($netDebit), 2);
                        }
                    }
                } elseif (in_array($acc->element, [1, 2, 3, 4, 5])) {
                    $balanceSheetLines[$acc->id] = [
                        'account' => $acc,
                        'debit'   => (float) $acc->total_debit,
                        'credit'  => (float) $acc->total_credit,
                        'net'     => $netDebit, // > 0 => net debit balance
                    ];
                }
            }

            // 3. Resolve Account 59 (Resultados Acumulados) or 89 (Resultado del Ejercicio)
            $netResult = round($totalRevenues - $totalExpenses, 2);

            $account59 = ChartOfAccount::where('code', '5911')->first()
                ?? ChartOfAccount::where('code', '591')->first()
                ?? ChartOfAccount::where('code', 'like', '59%')->first();

            if (!$account59) {
                throw new DomainException("No se encontró cuenta 59 (Resultados Acumulados) en el Plan de Cuentas.");
            }

            if ($netResult >= 0) {
                // Profit: Credit 5911
                $incomeExpenseLines[] = [
                    'account_id' => $account59->id,
                    'debit'      => 0.00,
                    'credit'     => $netResult,
                    'glosa'      => "Utilidad neta del ejercicio {$fiscalYear} transferida a resultados acumulados",
                ];
            } else {
                // Loss: Debit 59
                $incomeExpenseLines[] = [
                    'account_id' => $account59->id,
                    'debit'      => abs($netResult),
                    'credit'     => 0.00,
                    'glosa'      => "Pérdida neta del ejercicio {$fiscalYear} transferida a resultados acumulados",
                ];
            }

            // Post Entry 1: Cierre de Cuentas de Gestión
            $closingResultsEntry = null;
            if (!empty($incomeExpenseLines)) {
                $closingResultsEntry = $this->journalPostingService->postRaw(
                    [
                        'entry_date'          => $yearEndDate,
                        'entry_type'          => VoucherType::CLOSING,
                        'concept'             => "Asiento de cierre de cuentas de gestión - Ejercicio {$fiscalYear}",
                        'created_by_user_id'  => $userId,
                        'source_type'         => AccountingPeriod::class,
                        'source_id'           => $period->id,
                        'event_key'           => "annual_results_close_{$fiscalYear}",
                        'idempotency_key'     => md5("annual_results_close_{$fiscalYear}_{$period->id}"),
                    ],
                    $incomeExpenseLines
                );
            }

            // 4. Create Entry 2: Asiento de Cierre Patrimonial (Reversal of Balance Sheet accounts)
            if (!isset($balanceSheetLines[$account59->id])) {
                $balanceSheetLines[$account59->id] = [
                    'account' => $account59,
                    'debit'   => 0.00,
                    'credit'  => 0.00,
                    'net'     => 0.00,
                ];
            }

            $patrimonialCloseLines = [];
            foreach ($balanceSheetLines as $accId => $data) {

                $acc = $data['account'];
                $net = $data['net'];

                // Include net result effect on account 59
                if ($acc->id == $account59->id) {
                    $net -= $netResult; // adjusting for the profit/loss transferred
                }

                if (abs($net) > 0.001) {
                    if ($net > 0) {
                        // Net Debit (e.g. Asset) -> to close, credit it
                        $patrimonialCloseLines[] = [
                            'account_id' => $acc->id,
                            'debit'      => 0.00,
                            'credit'     => round($net, 2),
                            'glosa'      => "Cierre patrimonial {$fiscalYear}: Cancelación cta {$acc->code}",
                        ];
                    } else {
                        // Net Credit (e.g. Liability / Equity) -> to close, debit it
                        $patrimonialCloseLines[] = [
                            'account_id' => $acc->id,
                            'debit'      => round(abs($net), 2),
                            'credit'     => 0.00,
                            'glosa'      => "Cierre patrimonial {$fiscalYear}: Cancelación cta {$acc->code}",
                        ];
                    }
                }
            }

            $closingBalanceEntry = null;
            if (!empty($patrimonialCloseLines)) {
                $closingBalanceEntry = $this->journalPostingService->postRaw(
                    [
                        'entry_date'          => $yearEndDate,
                        'entry_type'          => VoucherType::CLOSING,
                        'concept'             => "Asiento de cierre patrimonial (Cierre de Balance) - Ejercicio {$fiscalYear}",
                        'created_by_user_id'  => $userId,
                        'source_type'         => AccountingPeriod::class,
                        'source_id'           => $period->id,
                        'event_key'           => "annual_balance_close_{$fiscalYear}",
                        'idempotency_key'     => md5("annual_balance_close_{$fiscalYear}_{$period->id}"),
                    ],
                    $patrimonialCloseLines
                );
            }

            // 5. Create Entry 3: Opening Entry for next fiscal year (Year + 1)
            $nextYear = $fiscalYear + 1;
            $nextYearOpeningDate = Carbon::parse("{$nextYear}-01-01")->toDateString();

            // Resolve or create next period (Month 1 of next year)
            $nextPeriod = AccountingPeriod::firstOrCreate(
                ['period_code' => "{$nextYear}-01"],
                [
                    'fiscal_year' => $nextYear,
                    'month'       => 1,
                    'start_date'  => "{$nextYear}-01-01",
                    'end_date'    => "{$nextYear}-01-31",
                    'status'      => 'OPEN',
                ]
            );

            $openingLines = [];
            foreach ($balanceSheetLines as $accId => $data) {
                $acc = $data['account'];
                $net = $data['net'];

                if ($acc->id == $account59->id) {
                    $net -= $netResult;
                }

                if (abs($net) > 0.001) {
                    if ($net > 0) {
                        // Asset: Debit
                        $openingLines[] = [
                            'account_id' => $acc->id,
                            'debit'      => round($net, 2),
                            'credit'     => 0.00,
                            'glosa'      => "Asiento de Apertura {$nextYear}: Saldo inicial cta {$acc->code}",
                        ];
                    } else {
                        // Liability / Equity: Credit
                        $openingLines[] = [
                            'account_id' => $acc->id,
                            'debit'      => 0.00,
                            'credit'     => round(abs($net), 2),
                            'glosa'      => "Asiento de Apertura {$nextYear}: Saldo inicial cta {$acc->code}",
                        ];
                    }
                }
            }

            $openingEntry = null;
            if (!empty($openingLines)) {
                $openingEntry = $this->journalPostingService->postRaw(
                    [
                        'entry_date'          => $nextYearOpeningDate,
                        'entry_type'          => VoucherType::OPENING,
                        'concept'             => "Asiento de Apertura - Ejercicio Fiscal {$nextYear}",
                        'created_by_user_id'  => $userId,
                        'source_type'         => AccountingPeriod::class,
                        'source_id'           => $nextPeriod->id,
                        'event_key'           => "annual_opening_{$nextYear}",
                        'idempotency_key'     => md5("annual_opening_{$nextYear}_{$nextPeriod->id}"),
                    ],
                    $openingLines
                );
            }

            // 6. Update period status to LOCKED and link closing/opening entries
            $period->update([
                'status'           => 'LOCKED',
                'closing_entry_id' => $closingResultsEntry?->id,
                'opening_entry_id' => $openingEntry?->id,
                'closed_by_user_id'=> $userId,
                'closed_at'        => now(),
                'notes'            => ($period->notes ? $period->notes . "\n" : '') . "Cierre Anual {$fiscalYear} ejecutado con éxito.",
            ]);

            // Audit Log
            AccountingPeriodAuditLog::create([
                'accounting_period_id' => $period->id,
                'user_id'              => $userId,
                'action'               => 'LOCK',
                'from_status'          => 'OPEN',
                'to_status'            => 'LOCKED',
                'reason'               => $notes ?? "Cierre anual definitivo y apertura de ejercicio {$nextYear}",
                'ip_address'           => request()->ip() ?? '127.0.0.1',
                'user_agent'           => request()->userAgent() ?? 'CLI/System',
                'metadata'             => [
                    'fiscal_year'                => $fiscalYear,
                    'net_result'                 => $netResult,
                    'closing_results_entry_id'   => $closingResultsEntry?->id,
                    'closing_balance_entry_id'   => $closingBalanceEntry?->id,
                    'opening_entry_id'           => $openingEntry?->id,
                ],
            ]);

            AccountingAuditService::log(
                'ANNUAL_CLOSE',
                $period,
                User::find($userId),
                $notes ?? "Cierre anual definitivo y apertura de ejercicio {$nextYear}",
                ['fiscal_year' => $fiscalYear, 'net_result' => $netResult]
            );

            return [
                'period'                 => $period,
                'net_result'             => $netResult,
                'closing_results_entry'  => $closingResultsEntry,
                'closing_balance_entry'  => $closingBalanceEntry,
                'opening_entry'          => $openingEntry,
            ];
        });
    }

    /**
     * Audited period reopening.
     * Requires valid justification reason and logs into accounting_period_audit_logs.
     */
    public function reopenPeriod(AccountingPeriod $period, int $userId, string $reason): AccountingPeriod
    {
        $trimmedReason = trim($reason);
        if (empty($trimmedReason)) {
            throw new DomainException("La reapertura de un período contable requiere un motivo o justificación obligatoria.");
        }

        return DB::transaction(function () use ($period, $userId, $trimmedReason) {
            $fromStatus = $period->status;

            // Reopen period
            $period->update([
                'status'              => 'OPEN',
                'reopened_by_user_id' => $userId,
                'reopened_at'         => now(),
                'notes'               => ($period->notes ? $period->notes . "\n" : '') . "[REAPERTURA " . now()->format('Y-m-d H:i') . "] Motivo: {$trimmedReason}",
            ]);

            AccountingPeriodAuditLog::create([
                'accounting_period_id' => $period->id,
                'user_id'              => $userId,
                'action'               => 'REOPEN',
                'from_status'          => $fromStatus,
                'to_status'            => 'OPEN',
                'reason'               => $trimmedReason,
                'ip_address'           => request()->ip() ?? '127.0.0.1',
                'user_agent'           => request()->userAgent() ?? 'CLI/System',
                'metadata'             => [
                    'period_code' => $period->period_code,
                    'fiscal_year' => $period->fiscal_year,
                    'month'       => $period->month,
                ],
            ]);

            AccountingAuditService::log(
                'REOPEN_PERIOD',
                $period,
                User::find($userId),
                $trimmedReason,
                ['from_status' => $fromStatus, 'to_status' => 'OPEN']
            );

            return $period;
        });
    }

    /**
     * Submit an accounting period closure to the DocumentApprovalService workflow (Type 10).
     * Workflow: Contabilidad -> Administración -> Dirección General.
     */
    public function submitClosureForApproval(
        AccountingPeriod $period,
        string $closureType,
        User $creator,
        ?string $notes = null
    ): AccountingPeriodClosure {
        return DB::transaction(function () use ($period, $closureType, $creator, $notes) {
            // Snapshot financial statements
            $balanceSheet = $this->statementService->getBalanceSheet(['period_id' => $period->id]);
            $totals = $balanceSheet['totals'];

            $closure = AccountingPeriodClosure::create([
                'uuid'                 => (string) Str::uuid(),
                'accounting_period_id' => $period->id,
                'closure_type'         => $closureType,
                'status'               => 'PENDIENTE',
                'net_result'           => $balanceSheet['net_result'],
                'total_assets'         => $totals['total_assets'],
                'total_liabilities'    => $totals['total_liabilities'],
                'total_equity'         => $totals['total_equity'],
                'closed_by_user_id'    => $creator->id,
                'notes'                => $notes,
            ]);

            // Set period to SOFT_CLOSED while under review
            $period->update(['status' => 'SOFT_CLOSED']);

            // Generate hierarchical approval chain (Document Type 10)
            $this->approvalService->generateWorkflow($closure, $creator);

            return $closure->load('approvals');
        });
    }

    /**
     * Finalize an approved closure once all steps are signed.
     */
    public function finalizeApprovedClosure(AccountingPeriodClosure $closure): void
    {
        $closure->update([
            'status'      => 'APPROVED',
            'approved_at' => now(),
        ]);

        $period = $closure->period;
        if ($closure->closure_type === 'ANNUAL') {
            $this->closeAnnualPeriod($period, $closure->closed_by_user_id, 'Cierre anual aprobado por Dirección General');
        } else {
            $this->closeMonthlyPeriod($period, $closure->closed_by_user_id, 'Cierre mensual aprobado por Dirección General');
        }
    }
}
