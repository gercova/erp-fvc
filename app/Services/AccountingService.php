<?php

namespace App\Services;

use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\Accounting\AccountingAuditService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountingService
{
    /**
     * Create and post a balanced journal entry with idempotency and period validation.
     *
     * @param array $header
     * @param array $lines
     * @return JournalEntry
     * @throws DomainException
     */
    public function createJournalEntry(array $header, array $lines): JournalEntry
    {
        // 1. Resolve or Validate Accounting Period
        $entryDate = isset($header['entry_date']) ? Carbon::parse($header['entry_date']) : now();
        $period = null;

        if (!empty($header['accounting_period_id'])) {
            $period = AccountingPeriod::find($header['accounting_period_id']);
        } else {
            $period = $this->resolveOpenPeriod($entryDate);
        }

        if (!$period) {
            throw new DomainException("No open accounting period found for date {$entryDate->toDateString()}.");
        }

        if (!$period->isOpen()) {
            throw new DomainException("Accounting period {$period->period_code} is {$period->status}. Postings are strictly prohibited.");
        }

        // 2. Check Idempotency: (source_type, source_id, event_key)
        $sourceType = $header['source_type'] ?? null;
        $sourceId   = $header['source_id'] ?? null;
        $eventKey   = $header['event_key'] ?? 'default';

        if ($sourceType && $sourceId) {
            $existing = JournalEntry::where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('event_key', $eventKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        // 3. Validate Lines & Double-Entry Equality
        if (count($lines) < 2) {
            throw new DomainException('A journal entry must contain at least two lines for double-entry bookkeeping.');
        }

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $preparedLines = [];

        foreach ($lines as $index => $line) {
            $account = null;
            if (!empty($line['account_id'])) {
                $account = ChartOfAccount::find($line['account_id']);
            } elseif (!empty($line['account_code'])) {
                $account = ChartOfAccount::where('code', $line['account_code'])->first();
            }

            if (!$account) {
                throw new DomainException("Accounting account specified in line " . ($index + 1) . " does not exist.");
            }

            if (!$account->accepts_movements && !$account->allows_movement) {
                throw new DomainException("Account {$account->code} - {$account->name} is a summary account and does not accept direct movements.");
            }

            if ($account->requires_third_party && empty($line['third_party_id']) && empty($line['third_party_document'])) {
                throw new DomainException("Account {$account->code} requires a third party (customer/supplier/employee).");
            }

            if ($account->requires_cost_center && empty($line['cost_center_id'])) {
                throw new DomainException("Account {$account->code} requires a cost center (productive activity).");
            }

            if ($account->requires_fund_source && empty($line['fund_source_id'])) {
                throw new DomainException("Account {$account->code} requires a fund source / bank.");
            }

            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit < 0 || $credit < 0) {
                throw new DomainException("Negative debit or credit amounts are not permitted. Use reversal lines instead.");
            }

            if ($debit === 0.0 && $credit === 0.0) {
                throw new DomainException("Line " . ($index + 1) . " must have either a debit or credit amount.");
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $exchangeRate = (float) ($header['exchange_rate'] ?? 1.0);
            $debitUsd = round($debit / ($exchangeRate > 0 ? $exchangeRate : 1.0), 4);
            $creditUsd = round($credit / ($exchangeRate > 0 ? $exchangeRate : 1.0), 4);

            $preparedLines[] = [
                'line_number'          => $index + 1,
                'account_id'           => $account->id,
                'debit'                => $debit,
                'credit'               => $credit,
                'debit_usd'            => $line['debit_usd'] ?? $debitUsd,
                'credit_usd'           => $line['credit_usd'] ?? $creditUsd,
                'glosa'                => $line['glosa'] ?? ($header['concept'] ?? null),
                'third_party_type'     => $line['third_party_type'] ?? null,
                'third_party_id'       => $line['third_party_id'] ?? null,
                'third_party_document' => $line['third_party_document'] ?? null,
                'cost_center_id'       => $line['cost_center_id'] ?? null,
                'fund_source_id'       => $line['fund_source_id'] ?? null,
                'bank_account_id'      => $line['bank_account_id'] ?? null,
                'document_reference'   => $line['document_reference'] ?? null,
            ];
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        // Core Mathematical Invariant: sum(debit) == sum(credit)
        if (abs($totalDebit - $totalCredit) > 0.001) {
            throw new DomainException("Unbalanced journal entry: Total debit (S/ {$totalDebit}) does not equal total credit (S/ {$totalCredit}).");
        }

        // 4. Atomic Database Persistence
        return DB::transaction(function () use ($header, $preparedLines, $period, $entryDate, $totalDebit, $totalCredit, $sourceType, $sourceId, $eventKey) {
            $entryNumber = $this->generateNextEntryNumber($period);

            $entry = JournalEntry::create([
                'uuid'                 => (string) Str::uuid(),
                'accounting_period_id' => $period->id,
                'entry_number'         => $entryNumber,
                'entry_date'           => $entryDate->toDateString(),
                'entry_type'           => $header['entry_type'] ?? VoucherType::OPERATING,
                'concept'              => $header['concept'] ?? 'Asiento contable operativo',
                'currency'             => $header['currency'] ?? 'PEN',
                'exchange_rate'        => $header['exchange_rate'] ?? 1.0000,
                'total_debit'          => $totalDebit,
                'total_credit'         => $totalCredit,
                'status'               => $header['status'] ?? JournalStatus::POSTED,
                'source_type'          => $sourceType,
                'source_id'            => $sourceId,
                'event_key'            => $eventKey,
                'idempotency_key'      => $header['idempotency_key'] ?? md5("{$sourceType}_{$sourceId}_{$eventKey}"),
                'reverses_entry_id'    => $header['reverses_entry_id'] ?? null,
                'created_by_user_id'   => $header['created_by_user_id'] ?? 1,
                'posted_by_user_id'    => $header['posted_by_user_id'] ?? ($header['created_by_user_id'] ?? 1),
                'posted_at'            => now(),
            ]);

            foreach ($preparedLines as $lineData) {
                $lineData['journal_entry_id'] = $entry->id;
                JournalEntryLine::create($lineData);
            }

            if ($entry->isPosted()) {
                AccountingAuditService::log(
                    'POST_ENTRY',
                    $entry,
                    auth()->user() ?? User::find($entry->posted_by_user_id),
                    "Publicación de asiento contable {$entry->entry_number}",
                    ['total_debit' => $totalDebit, 'total_credit' => $totalCredit]
                );
            }

            return $entry->load(['lines.account', 'period']);
        });
    }

    /**
     * Create an immutable reversal entry for a posted journal entry.
     */
    public function reverseJournalEntry(JournalEntry $entry, User $user, ?string $concept = null): JournalEntry
    {
        if (!$entry->isPosted()) {
            throw new DomainException('Only posted journal entries can be reversed.');
        }

        if ($entry->reversed_by_entry_id) {
            throw new DomainException('This journal entry has already been reversed.');
        }

        $period = $this->resolveOpenPeriod(now());
        if (!$period || !$period->isOpen()) {
            throw new DomainException('No open accounting period available for reversal entry.');
        }

        return DB::transaction(function () use ($entry, $user, $concept, $period) {
            $reversalLines = [];
            foreach ($entry->lines as $line) {
                // Invert debits and credits
                $reversalLines[] = [
                    'account_id'           => $line->account_id,
                    'debit'                => $line->credit,
                    'credit'               => $line->debit,
                    'debit_usd'            => $line->credit_usd,
                    'credit_usd'           => $line->debit_usd,
                    'glosa'                => 'Extorno: ' . ($line->glosa ?: $entry->concept),
                    'third_party_type'     => $line->third_party_type,
                    'third_party_id'       => $line->third_party_id,
                    'third_party_document' => $line->third_party_document,
                    'cost_center_id'       => $line->cost_center_id,
                    'fund_source_id'       => $line->fund_source_id,
                    'bank_account_id'      => $line->bank_account_id,
                    'document_reference'   => $line->document_reference,
                ];
            }

            $reversalConcept = $concept ?: ("Extorno de asiento N° " . $entry->entry_number . " - " . $entry->concept);

            $reversalHeader = [
                'accounting_period_id' => $period->id,
                'entry_date'           => now()->toDateString(),
                'entry_type'           => VoucherType::REVERSAL,
                'concept'              => $reversalConcept,
                'currency'             => $entry->currency,
                'exchange_rate'        => $entry->exchange_rate,
                'created_by_user_id'   => $user->id,
                'posted_by_user_id'    => $user->id,
                'reverses_entry_id'    => $entry->id,
                'source_type'          => $entry->source_type,
                'source_id'            => $entry->source_id,
                'event_key'            => 'reversal_' . $entry->id,
            ];

            $reversalEntry = $this->createJournalEntry($reversalHeader, $reversalLines);

            // Update original entry to mark it reversed and link reversal entry
            $entry->update([
                'reversed_by_entry_id' => $reversalEntry->id,
                'status'               => JournalStatus::REVERSED,
            ]);

            return $reversalEntry;
        });
    }

    /**
     * Resolve or automatically open the standard accounting period for a date.
     */
    public function resolveOpenPeriod(Carbon $date): AccountingPeriod
    {
        $year = $date->year;
        $month = $date->month;
        $code = sprintf('%04d-%02d', $year, $month);

        $period = AccountingPeriod::where('period_code', $code)->first();

        if (!$period) {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            $period = AccountingPeriod::create([
                'fiscal_year' => $year,
                'month'       => $month,
                'period_code' => $code,
                'start_date'  => $startDate->toDateString(),
                'end_date'    => $endDate->toDateString(),
                'status'      => 'OPEN',
            ]);
        }

        return $period;
    }

    /**
     * Generate the next correlative entry number for the period.
     */
    public function generateNextEntryNumber(AccountingPeriod $period): string
    {
        $count = JournalEntry::where('accounting_period_id', $period->id)->count() + 1;
        return sprintf('%s-%06d', $period->period_code, $count);
    }
}
