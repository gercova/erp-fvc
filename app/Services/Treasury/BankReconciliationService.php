<?php

namespace App\Services\Treasury;

use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AccountingPeriod;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\BankReconciliationItem;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\RdrBankReconciliation;
use App\Services\Accounting\JournalPostingService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BankReconciliationService
{
    public function __construct(
        protected JournalPostingService $journalPostingService
    ) {}

    /**
     * Initiate or update a monthly bank reconciliation for a BankAccount.
     */
    public function initiateReconciliation(
        BankAccount $bankAccount,
        int $year,
        int $month,
        float $bankStatementBalance,
        ?string $statementClosingDate = null,
        ?int $userId = null,
        ?string $notes = null
    ): RdrBankReconciliation {
        $closingDate = $statementClosingDate
            ? Carbon::parse($statementClosingDate)->format('Y-m-d')
            : Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

        // Resolve accounting period
        $period = AccountingPeriod::where('fiscal_year', $year)
            ->where('month', $month)
            ->first();

        // Calculate accounting book balance of the 104x account up to statement closing date
        $bookBalance = $this->calculateBookBalance($bankAccount, $closingDate);

        // System calculated balance from RDR or movements
        $systemBalance = $bookBalance;

        $reconciliation = RdrBankReconciliation::updateOrCreate(
            [
                'fund_source_id' => $bankAccount->fund_source_id,
                'period_year'    => $year,
                'period_month'   => $month,
            ],
            [
                'bank_account_id'           => $bankAccount->id,
                'accounting_period_id'      => $period?->id,
                'statement_closing_date'    => $closingDate,
                'bank_statement_balance'    => round($bankStatementBalance, 2),
                'system_calculated_balance' => round($systemBalance, 2),
                'book_calculated_balance'   => round($bookBalance, 2),
                'reconciled_by_user_id'     => $userId ?? 1,
                'notes'                     => $notes,
                'status'                    => 'IN_REVIEW',
            ]
        );

        $this->recalculate($reconciliation);

        return $reconciliation->fresh(['items', 'bankAccount', 'fundSource']);
    }

    /**
     * Calculate current posted accounting balance for the bank account (cta 104x) up to a given date.
     * Balance = Sum(Debit) - Sum(Credit).
     */
    public function calculateBookBalance(BankAccount $bankAccount, ?string $asOfDate = null): float
    {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', JournalStatus::POSTED->value)
            ->where('journal_entry_lines.account_id', $bankAccount->accounting_account_id);

        if ($asOfDate) {
            $query->where('journal_entries.entry_date', '<=', $asOfDate);
        }

        $sums = $query->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        $initial = (float) $bankAccount->initial_balance;
        $net = ((float) $sums->total_debit) - ((float) $sums->total_credit);

        return round($initial + $net, 2);
    }

    /**
     * Find match suggestions between un-reconciled bank statement movements and accounting journal entries.
     */
    public function getMatchSuggestions(RdrBankReconciliation $reconciliation, int $dayTolerance = 3): Collection
    {
        $bankAccount = $reconciliation->bankAccount;
        if (!$bankAccount) {
            return collect();
        }

        // Pending bank movements
        $pendingMovements = BankMovement::query()
            ->where('bank_account_id', $bankAccount->id)
            ->where('reconciliation_status', 'PENDING')
            ->orderBy('movement_date')
            ->get();

        if ($pendingMovements->isEmpty()) {
            return collect();
        }

        // Unmatched journal entry lines on 104x account
        $alreadyMatchedEntryIds = BankReconciliationItem::query()
            ->whereNotNull('journal_entry_id')
            ->pluck('journal_entry_id')
            ->toArray();

        $candidates = JournalEntryLine::query()
            ->with('journalEntry')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', JournalStatus::POSTED->value)
            ->where('journal_entry_lines.account_id', $bankAccount->accounting_account_id)
            ->whereNotIn('journal_entries.id', $alreadyMatchedEntryIds)
            ->select('journal_entry_lines.*')
            ->get();

        $suggestions = collect();

        foreach ($pendingMovements as $mov) {
            $movDate = Carbon::parse($mov->movement_date);
            $movAmount = (float) $mov->amount;
            $isDeposit = $mov->movement_type === 'INFLOW';

            $bestMatch = null;
            $bestConfidence = null;

            foreach ($candidates as $cand) {
                $candEntry = $cand->journalEntry;
                if (!$candEntry) {
                    continue;
                }

                $candAmount = $isDeposit ? (float) $cand->debit : (float) $cand->credit;
                if (abs($candAmount - $movAmount) > 0.001) {
                    continue; // Amount must match
                }

                $entryDate = Carbon::parse($candEntry->entry_date);
                $daysDiff = abs($movDate->diffInDays($entryDate, false));

                if ($daysDiff > $dayTolerance) {
                    continue;
                }

                // Reference score check
                $refMatch = false;
                if (!empty($mov->operation_number)) {
                    $op = strtolower($mov->operation_number);
                    if (
                        str_contains(strtolower((string) $cand->document_reference), $op) ||
                        str_contains(strtolower((string) $cand->glosa), $op) ||
                        str_contains(strtolower((string) $candEntry->concept), $op)
                    ) {
                        $refMatch = true;
                    }
                }

                $confidence = ($refMatch || $daysDiff === 0) ? 'HIGH' : 'MEDIUM';

                if (!$bestMatch || $confidence === 'HIGH') {
                    $bestMatch = [
                        'movement'        => $mov,
                        'journal_entry'   => $candEntry,
                        'confidence'      => $confidence,
                        'days_difference' => $daysDiff,
                        'reference_match' => $refMatch,
                    ];
                    if ($confidence === 'HIGH') {
                        break;
                    }
                }
            }

            if ($bestMatch) {
                $suggestions->push($bestMatch);
            }
        }

        return $suggestions;
    }

    /**
     * Perform manual or automated match between a bank movement and a journal entry.
     */
    public function matchMovement(
        RdrBankReconciliation $reconciliation,
        BankMovement $bankMovement,
        JournalEntry $journalEntry
    ): BankReconciliationItem {
        return DB::transaction(function () use ($reconciliation, $bankMovement, $journalEntry) {
            $bankMovement->update([
                'reconciliation_status'    => 'MATCHED',
                'matched_journal_entry_id' => $journalEntry->id,
            ]);

            $item = BankReconciliationItem::create([
                'reconciliation_id' => $reconciliation->id,
                'bank_movement_id'  => $bankMovement->id,
                'journal_entry_id'  => $journalEntry->id,
                'item_type'         => 'MATCHED',
                'amount'            => $bankMovement->amount,
                'reference'         => $bankMovement->operation_number,
                'concept'           => $bankMovement->concept,
                'difference'        => 0.00,
            ]);

            $this->recalculate($reconciliation);

            return $item;
        });
    }

    /**
     * Add a reconciling item (DEPOSIT_IN_TRANSIT, OUTSTANDING_CHECK, BANK_CHARGE, BANK_CREDIT).
     */
    public function addReconcilingItem(
        RdrBankReconciliation $reconciliation,
        string $itemType,
        float $amount,
        ?string $reference = null,
        ?string $concept = null,
        ?int $bankMovementId = null,
        ?int $journalEntryId = null,
        ?string $notes = null
    ): BankReconciliationItem {
        $allowedTypes = ['MATCHED', 'DEPOSIT_IN_TRANSIT', 'OUTSTANDING_CHECK', 'BANK_CHARGE', 'BANK_CREDIT', 'ERROR'];
        if (!in_array($itemType, $allowedTypes)) {
            throw new DomainException("Tipo de partida conciliatoria no válido: {$itemType}");
        }

        return DB::transaction(function () use ($reconciliation, $itemType, $amount, $reference, $concept, $bankMovementId, $journalEntryId, $notes) {
            $item = BankReconciliationItem::create([
                'reconciliation_id' => $reconciliation->id,
                'bank_movement_id'  => $bankMovementId,
                'journal_entry_id'  => $journalEntryId,
                'item_type'         => $itemType,
                'amount'            => round($amount, 2),
                'reference'         => $reference,
                'concept'           => $concept,
                'difference'        => 0.00,
                'notes'             => $notes,
            ]);

            $this->recalculate($reconciliation);

            return $item;
        });
    }

    /**
     * Generate adjustment journal entry for unrecorded bank charges (e.g. ITF, commissions, maintenance fees).
     * Adjustments generate journal entries via JournalPostingService.
     *
     * Debit: Expense Account (e.g. 6391 / 676 Gastos Financieros / Comisiones Bancarias)
     * Credit: Bank Account 104x
     */
    public function postBankChargeAdjustment(
        RdrBankReconciliation $reconciliation,
        float $amount,
        string $concept,
        string $expenseAccountCode = '6391',
        ?string $reference = null,
        ?BankReconciliationItem $reconcilingItem = null
    ): JournalEntry {
        $bankAccount = $reconciliation->bankAccount;
        if (!$bankAccount) {
            throw new DomainException("La conciliación no tiene una cuenta bancaria asociada.");
        }

        // Resolve or fallback expense account
        $expenseAccount = ChartOfAccount::where('code', $expenseAccountCode)->first()
            ?? ChartOfAccount::where('code', '676')->first()
            ?? ChartOfAccount::where('code', '659')->first();

        if (!$expenseAccount) {
            throw new DomainException("No se encontró cuenta de gastos bancarios (6391 / 676).");
        }

        $entryDate = $reconciliation->statement_closing_date
            ? $reconciliation->statement_closing_date->format('Y-m-d')
            : now()->format('Y-m-d');

        $header = [
            'entry_date'     => $entryDate,
            'entry_type'     => VoucherType::ADJUSTMENT,
            'concept'        => "Ajuste por cargo bancario: {$concept}",
            'source_type'    => RdrBankReconciliation::class,
            'source_id'      => $reconciliation->id,
            'event_key'      => 'bank_charge_' . ($reference ? md5($reference . $amount) : Str::random(8)),
            'currency'       => $bankAccount->currency ?? 'PEN',
            'idempotency_key'=> md5("bank_charge_{$reconciliation->id}_{$amount}_{$reference}"),
        ];

        $lines = [
            // Debit: Gastos bancarios
            [
                'account_id'         => $expenseAccount->id,
                'debit'              => round($amount, 2),
                'credit'             => 0.00,
                'glosa'              => $concept,
                'document_reference' => $reference,
            ],
            // Credit: Banco 104x
            [
                'account_id'         => $bankAccount->accounting_account_id,
                'debit'              => 0.00,
                'credit'             => round($amount, 2),
                'bank_account_id'    => $bankAccount->id,
                'fund_source_id'     => $bankAccount->fund_source_id,
                'glosa'              => $concept,
                'document_reference' => $reference,
            ],
        ];

        return DB::transaction(function () use ($reconciliation, $header, $lines, $amount, $reference, $concept, $reconcilingItem) {
            $entry = $this->journalPostingService->postRaw($header, $lines);

            if ($reconcilingItem) {
                $reconcilingItem->update([
                    'journal_entry_id' => $entry->id,
                    'item_type'        => 'MATCHED',
                    'notes'            => 'Ajustado con asiento ' . $entry->entry_number,
                ]);
            }

            // Recalculate book balance and reconciliation
            $this->recalculate($reconciliation);

            return $entry;
        });
    }

    /**
     * Recalculate reconciliation totals according to the mathematical formula:
     * Adjusted Bank Balance = bank_statement_balance + uncredited_deposits - outstanding_checks
     * Adjusted Book Balance = book_calculated_balance - unrecorded_bank_charges + unrecorded_bank_credits
     * Reconciled Difference = Adjusted Bank Balance - Adjusted Book Balance
     * Must equal 0.00 for balanced closing.
     */
    public function recalculate(RdrBankReconciliation $reconciliation): void
    {
        $bankAccount = $reconciliation->bankAccount;
        if ($bankAccount) {
            $closingDate = $reconciliation->statement_closing_date
                ? $reconciliation->statement_closing_date->format('Y-m-d')
                : null;
            $reconciliation->book_calculated_balance = $this->calculateBookBalance($bankAccount, $closingDate);
        }

        $reconciliation->unsetRelation('items');
        $items = $reconciliation->items()->get();

        $uncreditedDeposits = $items->where('item_type', 'DEPOSIT_IN_TRANSIT')->sum('amount');
        $outstandingChecks  = $items->where('item_type', 'OUTSTANDING_CHECK')->sum('amount');
        $unrecordedCharges  = $items->where('item_type', 'BANK_CHARGE')->sum('amount');
        $unrecordedCredits  = $items->where('item_type', 'BANK_CREDIT')->sum('amount');

        $adjustedBankBalance = round((float) $reconciliation->bank_statement_balance + $uncreditedDeposits - $outstandingChecks, 2);
        $adjustedBookBalance = round((float) $reconciliation->book_calculated_balance - $unrecordedCharges + $unrecordedCredits, 2);

        $diff = round($adjustedBankBalance - $adjustedBookBalance, 2);

        $reconciliation->uncredited_deposits    = round($uncreditedDeposits, 2);
        $reconciliation->outstanding_checks     = round($outstandingChecks, 2);
        $reconciliation->unrecorded_bank_charges= round($unrecordedCharges, 2);
        $reconciliation->unrecorded_bank_credits= round($unrecordedCredits, 2);
        $reconciliation->reconciled_difference  = $diff;

        if (abs($diff) < 0.01 && $reconciliation->status !== 'APPROVED') {
            $reconciliation->status = 'BALANCED';
        } elseif (abs($diff) >= 0.01 && $reconciliation->status === 'BALANCED') {
            $reconciliation->status = 'DISCREPANCY';
        }

        $reconciliation->save();
    }

    /**
     * Close reconciliation with zero difference validation.
     * Acceptance: Reconciled bank balance = accounting balance of the 104x account after reconciling items.
     */
    public function closeReconciliation(RdrBankReconciliation $reconciliation, int $userId): RdrBankReconciliation
    {
        $this->recalculate($reconciliation);

        if (abs((float) $reconciliation->reconciled_difference) > 0.01) {
            throw new DomainException(
                "No se puede cerrar la conciliación bancaria con diferencia distinta de cero. " .
                "Diferencia actual: S/ " . number_format($reconciliation->reconciled_difference, 2) . ". " .
                "Saldo extracto ajustado: S/ " . number_format((float)$reconciliation->bank_statement_balance + (float)$reconciliation->uncredited_deposits - (float)$reconciliation->outstanding_checks, 2) . " vs " .
                "Saldo libro mayor ajustado: S/ " . number_format((float)$reconciliation->book_calculated_balance - (float)$reconciliation->unrecorded_bank_charges + (float)$reconciliation->unrecorded_bank_credits, 2)
            );
        }

        $reconciliation->update([
            'status'              => 'BALANCED',
            'reconciled_at'       => now(),
            'approved_by_user_id' => $userId,
        ]);

        return $reconciliation;
    }
}
