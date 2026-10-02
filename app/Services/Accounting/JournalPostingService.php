<?php

namespace App\Services\Accounting;

use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Events\BillingVoided;
use App\Models\AccountingPeriod;
use App\Models\AccountingPostingFailure;
use App\Models\Billing;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\AccountingService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class JournalPostingService
{
    public function __construct(
        protected AccountingEventMapper $mapper,
        protected AccountingService $accountingService
    ) {}

    /**
     * Post a domain event or model into double-entry accounting idempotently.
     * Returns the created or existing JournalEntry, or null if ignored.
     */
    public function post(mixed $eventOrModel, ?string $eventKey = null): ?JournalEntry
    {
        // Handle explicit BillingVoided events
        if ($eventOrModel instanceof BillingVoided) {
            return $this->postVoid($eventOrModel->billing, $eventOrModel->reason);
        }

        // Map event or model to journal entry structure
        $mapped = (is_array($eventOrModel) && isset($eventOrModel['header'], $eventOrModel['lines']))
            ? $eventOrModel
            : $this->mapper->map($eventOrModel);

        if (!$mapped) {
            return null; // Event explicitly ignored (e.g. Single Origin Rule for ActivityTransaction)
        }

        $header = $mapped['header'];
        $lines = $mapped['lines'];

        if ($eventKey) {
            $header['event_key'] = $eventKey;
        }

        $sourceType = $header['source_type'] ?? null;
        $sourceId   = $header['source_id'] ?? null;
        $finalEventKey = $header['event_key'] ?? 'posted';
        $idempotencyKey = $header['idempotency_key'] ?? (
            ($sourceType && $sourceId)
                ? md5("{$sourceType}_{$sourceId}_{$finalEventKey}")
                : (string) Str::uuid()
        );

        // IDEMPOTENCY CHECK:
        // If an entry already exists for (source_type, source_id, event_key), return it immediately.
        if ($sourceType && $sourceId) {
            $existing = JournalEntry::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('event_key', $finalEventKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $existingByIdempotency = JournalEntry::where('idempotency_key', $idempotencyKey)->first();
        if ($existingByIdempotency) {
            return $existingByIdempotency;
        }

        // CLOSED PERIOD HANDLING:
        // If the date falls in a closed or locked period, resolve the first open period and add regularization memo.
        $targetDate = Carbon::parse($header['entry_date'] ?? now());
        $period = $this->resolvePostingPeriod($targetDate, $header);

        // Calculate totals
        $totalDebit = 0.00;
        $totalCredit = 0.00;
        $preparedLines = [];

        foreach ($lines as $index => $line) {
            $debit = round((float) ($line['debit'] ?? 0.0), 2);
            $credit = round((float) ($line['credit'] ?? 0.0), 2);

            if ($debit <= 0 && $credit <= 0) {
                continue;
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $preparedLines[] = [
                'line_number'          => $index + 1,
                'account_id'           => $line['account_id'],
                'debit'                => $debit,
                'credit'               => $credit,
                'debit_usd'            => $line['debit_usd'] ?? 0.0000,
                'credit_usd'           => $line['credit_usd'] ?? 0.0000,
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

        if ($totalDebit <= 0) {
            return null;
        }

        // Persist within atomic DB transaction
        return DB::transaction(function () use ($header, $preparedLines, $period, $totalDebit, $totalCredit, $sourceType, $sourceId, $finalEventKey, $idempotencyKey) {
            $entryNumber = $this->accountingService->generateNextEntryNumber($period);

            $entry = JournalEntry::create([
                'uuid'                 => (string) Str::uuid(),
                'accounting_period_id' => $period->id,
                'entry_number'         => $entryNumber,
                'entry_date'           => $header['entry_date'],
                'entry_type'           => $header['entry_type'] ?? VoucherType::OPERATING,
                'concept'              => $header['concept'] ?? 'Asiento contable automatizado',
                'currency'             => $header['currency'] ?? 'PEN',
                'exchange_rate'        => $header['exchange_rate'] ?? 1.0000,
                'total_debit'          => $totalDebit,
                'total_credit'         => $totalCredit,
                'status'               => JournalStatus::POSTED,
                'source_type'          => $sourceType,
                'source_id'            => $sourceId,
                'event_key'            => $finalEventKey,
                'idempotency_key'      => $idempotencyKey,
                'reverses_entry_id'    => $header['reverses_entry_id'] ?? null,
                'created_by_user_id'   => $header['created_by_user_id'] ?? 1,
                'posted_by_user_id'    => $header['created_by_user_id'] ?? 1,
                'posted_at'            => now(),
            ]);

            foreach ($preparedLines as $lineData) {
                $lineData['journal_entry_id'] = $entry->id;
                JournalEntryLine::create($lineData);
            }

            return $entry->load(['lines.account', 'period']);
        });
    }

    /**
     * Post a reversal contra-entry for a voided billing document.
     * Never physically deletes; creates an offsetting reversal entry and links them.
     */
    public function postVoid(Billing $billing, string $reason = '', ?User $user = null): ?JournalEntry
    {
        // 1. Locate original posted journal entry for this billing
        $originalEntry = JournalEntry::query()
            ->where('source_type', get_class($billing))
            ->where('source_id', $billing->id)
            ->where('event_key', 'posted')
            ->first();

        if (!$originalEntry) {
            return null; // Original document had no accounting entry
        }

        // If already reversed, return the reversal entry
        if ($originalEntry->reversed_by_entry_id) {
            return JournalEntry::find($originalEntry->reversed_by_entry_id);
        }

        $actingUser = $user ?? User::find($billing->idusuario ?? 1) ?? User::first();
        $concept = "Anulación/Extorno comprobante {$billing->serie}-{$billing->correlativo}" . ($reason ? ": {$reason}" : '');

        return $this->accountingService->reverseJournalEntry($originalEntry, $actingUser, $concept);
    }

    /**
     * Resolve accounting period. If the target period is closed/locked,
     * record in the first open period with a regularization memo.
     */
    protected function resolvePostingPeriod(Carbon $date, array &$header): AccountingPeriod
    {
        $exactPeriod = AccountingPeriod::query()
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->first();

        if ($exactPeriod && $exactPeriod->isOpen()) {
            return $exactPeriod;
        }

        // Period is closed, locked, or doesn't exist yet
        $originalPeriodCode = $exactPeriod ? $exactPeriod->period_code : $date->format('Y-m');

        // Find the first open period forward, or current active open period
        $openPeriod = AccountingPeriod::query()
            ->where('status', 'OPEN')
            ->where('end_date', '>=', $date->toDateString())
            ->orderBy('start_date', 'asc')
            ->first();

        if (!$openPeriod) {
            $openPeriod = AccountingPeriod::query()
                ->where('status', 'OPEN')
                ->orderBy('start_date', 'asc')
                ->first();
        }

        if (!$openPeriod) {
            // Auto-create/resolve standard open period for current date
            $openPeriod = $this->accountingService->resolveOpenPeriod(now());
        }

        // Apply regularization memo
        $regularizationMemo = "[REGULARIZACIÓN PERÍODO CERRADO {$originalPeriodCode}] ";
        if (!str_contains($header['concept'] ?? '', '[REGULARIZACIÓN')) {
            $header['concept'] = $regularizationMemo . ($header['concept'] ?? 'Asiento de regularización');
        }

        // Post with date inside the open period (e.g. today or open period start)
        if ($date->lt($openPeriod->start_date) || $date->gt($openPeriod->end_date)) {
            $header['entry_date'] = now()->between($openPeriod->start_date, $openPeriod->end_date)
                ? now()->toDateString()
                : $openPeriod->start_date->toDateString();
        }

        return $openPeriod;
    }

    /**
     * Record an execution failure in the accounting_posting_failures table.
     */
    public function recordFailure(string $eventName, mixed $eventOrModel, Throwable $e): AccountingPostingFailure
    {
        $sourceType = null;
        $sourceId = null;
        $eventKey = null;

        if ($eventOrModel instanceof Model) {
            $sourceType = get_class($eventOrModel);
            $sourceId = $eventOrModel->getKey();
        } elseif (is_object($eventOrModel)) {
            foreach (['sale', 'billing', 'buy', 'archingCash', 'cutTransfer', 'loan', 'transaction', 'document'] as $prop) {
                if (isset($eventOrModel->{$prop}) && $eventOrModel->{$prop} instanceof Model) {
                    $sourceType = get_class($eventOrModel->{$prop});
                    $sourceId = $eventOrModel->{$prop}->getKey();
                    break;
                }
            }
        }

        return AccountingPostingFailure::create([
            'event_name'      => $eventName,
            'source_type'     => $sourceType,
            'source_id'       => $sourceId,
            'event_key'       => $eventKey,
            'payload'         => is_array($eventOrModel) ? $eventOrModel : (method_exists($eventOrModel, 'toArray') ? $eventOrModel->toArray() : ['class' => get_class($eventOrModel)]),
            'error_message'   => $e->getMessage(),
            'stack_trace'     => $e->getTraceAsString(),
            'attempts'        => 1,
            'status'          => 'FAILED',
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Reprocess a failed accounting posting record.
     */
    public function reprocessFailure(int $failureId): bool
    {
        $failure = AccountingPostingFailure::findOrFail($failureId);

        if (!$failure->source_type || !$failure->source_id) {
            throw new DomainException("Failure #{$failureId} does not have a resolvable source model.");
        }

        $sourceModel = $failure->source_type::find($failure->source_id);
        if (!$sourceModel) {
            throw new DomainException("Source model {$failure->source_type} #{$failure->source_id} not found.");
        }

        try {
            $this->post($sourceModel);
            $failure->markAsReprocessed();
            return true;
        } catch (Throwable $e) {
            $failure->increment('attempts');
            $failure->update([
                'error_message'   => $e->getMessage(),
                'stack_trace'     => $e->getTraceAsString(),
                'last_attempt_at' => now(),
            ]);
            throw $e;
        }
    }

    /**
     * Post a raw journal entry with explicit header and lines.
     */
    public function postRaw(array $header, array $lines, ?string $eventKey = null): ?JournalEntry
    {
        return $this->post(['header' => $header, 'lines' => $lines], $eventKey);
    }
}
