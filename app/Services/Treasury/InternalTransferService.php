<?php

namespace App\Services\Treasury;

use App\Enums\VoucherType;
use App\Models\ArchingCash;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\FundSource;
use App\Models\InternalTransfer;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalPostingService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InternalTransferService
{
    public function __construct(
        protected JournalPostingService $journalPostingService
    ) {}

    /**
     * Transfer funds between two bank accounts.
     * Generates double journal entry:
     * Debit: Destination Bank (104x)
     * Credit: Source Bank (104x)
     */
    public function transferBetweenBankAccounts(
        BankAccount $source,
        BankAccount $destination,
        float $amount,
        string $date,
        string $concept,
        ?string $reference = null,
        int $userId = 1
    ): InternalTransfer {
        if ($source->id === $destination->id) {
            throw new DomainException("La cuenta bancaria de origen y destino no pueden ser iguales.");
        }

        if ($amount <= 0) {
            throw new DomainException("El monto a transferir debe ser mayor a cero.");
        }

        $formattedDate = Carbon::parse($date)->format('Y-m-d');
        $ref = $reference ?: 'TRF-' . strtoupper(Str::random(6));

        return DB::transaction(function () use ($source, $destination, $amount, $formattedDate, $concept, $ref, $userId) {
            $transfer = InternalTransfer::create([
                'uuid'                       => (string) Str::uuid(),
                'transfer_code'              => 'TRF-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                'transfer_type'              => 'BANK_TO_BANK',
                'source_type'                => 'BANK_ACCOUNT',
                'source_bank_account_id'      => $source->id,
                'destination_type'           => 'BANK_ACCOUNT',
                'destination_bank_account_id' => $destination->id,
                'amount'                     => round($amount, 2),
                'transfer_date'              => $formattedDate,
                'reference_number'           => $ref,
                'concept'                    => $concept,
                'created_by_user_id'         => $userId,
                'status'                     => 'COMPLETED',
            ]);

            // Update bank accounts balances
            $source->decrement('current_balance', $amount);
            $destination->increment('current_balance', $amount);

            // Update fund sources balances
            if ($source->fundSource) {
                $source->fundSource->decrement('current_balance', $amount);
            }
            if ($destination->fundSource) {
                $destination->fundSource->increment('current_balance', $amount);
            }

            // Post double journal entry
            $header = [
                'entry_date'     => $formattedDate,
                'entry_type'     => VoucherType::OPERATING,
                'concept'        => "Transferencia entre cuentas bancarias: {$concept}",
                'source_type'    => InternalTransfer::class,
                'source_id'      => $transfer->id,
                'event_key'      => 'bank_to_bank_transfer',
                'currency'       => $source->currency ?? 'PEN',
                'idempotency_key'=> md5("internal_transfer_{$transfer->id}_{$amount}"),
            ];

            $lines = [
                // Debit: Destination Bank
                [
                    'account_id'         => $destination->accounting_account_id,
                    'debit'              => round($amount, 2),
                    'credit'             => 0.00,
                    'bank_account_id'    => $destination->id,
                    'fund_source_id'     => $destination->fund_source_id,
                    'glosa'              => "Recepción de transferencia desde {$source->bank_name}: {$concept}",
                    'document_reference' => $ref,
                ],
                // Credit: Source Bank
                [
                    'account_id'         => $source->accounting_account_id,
                    'debit'              => 0.00,
                    'credit'             => round($amount, 2),
                    'bank_account_id'    => $source->id,
                    'fund_source_id'     => $source->fund_source_id,
                    'glosa'              => "Envío de transferencia a {$destination->bank_name}: {$concept}",
                    'document_reference' => $ref,
                ],
            ];

            $entry = $this->journalPostingService->postRaw($header, $lines);
            if ($entry) {
                $transfer->update(['journal_entry_id' => $entry->id]);
            }

            return $transfer->fresh(['sourceBankAccount', 'destinationBankAccount', 'journalEntry']);
        });
    }

    /**
     * Deposit cash count (arqueo de caja) or physical cash to a bank account.
     * Generates double journal entry:
     * Debit: Bank Account (104x)
     * Credit: Cash Drawer / Mostrador POS (1011 / 1012)
     */
    public function depositCashToBank(
        mixed $archingCashOrId,
        BankAccount $bankAccount,
        float $amount,
        string $date,
        string $concept,
        ?string $reference = null,
        int $userId = 1
    ): InternalTransfer {
        if ($amount <= 0) {
            throw new DomainException("El monto a depositar debe ser mayor a cero.");
        }

        $archingCashId = $archingCashOrId instanceof ArchingCash ? $archingCashOrId->id : $archingCashOrId;
        $formattedDate = Carbon::parse($date)->format('Y-m-d');
        $ref = $reference ?: 'DEP-' . strtoupper(Str::random(6));

        // Resolve cash account (1011 or 1012)
        $cashAccount = ChartOfAccount::where('code', '1011')->first()
            ?? ChartOfAccount::where('code', '1012')->first()
            ?? ChartOfAccount::where('code', '101')->first();

        if (!$cashAccount) {
            throw new DomainException("No se encontró la cuenta contable de caja (1011/1012).");
        }

        return DB::transaction(function () use ($archingCashId, $bankAccount, $cashAccount, $amount, $formattedDate, $concept, $ref, $userId) {
            $transfer = InternalTransfer::create([
                'uuid'                       => (string) Str::uuid(),
                'transfer_code'              => 'DEP-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                'transfer_type'              => 'CASH_TO_BANK',
                'source_type'                => 'CASH',
                'source_arching_cash_id'     => $archingCashId,
                'destination_type'           => 'BANK_ACCOUNT',
                'destination_bank_account_id' => $bankAccount->id,
                'amount'                     => round($amount, 2),
                'transfer_date'              => $formattedDate,
                'reference_number'           => $ref,
                'concept'                    => $concept,
                'created_by_user_id'         => $userId,
                'status'                     => 'COMPLETED',
            ]);

            // Increment destination bank balance
            $bankAccount->increment('current_balance', $amount);
            if ($bankAccount->fundSource) {
                $bankAccount->fundSource->increment('current_balance', $amount);
            }

            // Post double journal entry: Debit Bank (104x), Credit Cash (1011/1012)
            $header = [
                'entry_date'     => $formattedDate,
                'entry_type'     => VoucherType::OPERATING,
                'concept'        => "Depósito de recaudación / arqueo de caja a banco: {$concept}",
                'source_type'    => InternalTransfer::class,
                'source_id'      => $transfer->id,
                'event_key'      => 'cash_to_bank_deposit',
                'currency'       => $bankAccount->currency ?? 'PEN',
                'idempotency_key'=> md5("cash_deposit_{$transfer->id}_{$amount}"),
            ];

            $lines = [
                // Debit: Bank Account (104x)
                [
                    'account_id'         => $bankAccount->accounting_account_id,
                    'debit'              => round($amount, 2),
                    'credit'             => 0.00,
                    'bank_account_id'    => $bankAccount->id,
                    'fund_source_id'     => $bankAccount->fund_source_id,
                    'glosa'              => "Depósito bancario desde caja: {$concept}",
                    'document_reference' => $ref,
                ],
                // Credit: Cash Account (1011/1012)
                [
                    'account_id'         => $cashAccount->id,
                    'debit'              => 0.00,
                    'credit'             => round($amount, 2),
                    'glosa'              => "Salida de efectivo por depósito al banco {$bankAccount->bank_name}: {$concept}",
                    'document_reference' => $ref,
                ],
            ];

            $entry = $this->journalPostingService->postRaw($header, $lines);
            if ($entry) {
                $transfer->update(['journal_entry_id' => $entry->id]);
            }

            return $transfer->fresh(['destinationBankAccount', 'journalEntry']);
        });
    }

    /**
     * Transfer funds to the Cuenta Única del Tesoro (CUT).
     * Generates double journal entry:
     * Debit: CUT (1071)
     * Credit: Operational Bank Account (104x)
     */
    public function transferToCut(
        BankAccount $sourceBank,
        FundSource $cutFund,
        float $amount,
        string $date,
        string $concept,
        ?string $reference = null,
        int $userId = 1
    ): InternalTransfer {
        if ($amount <= 0) {
            throw new DomainException("El monto a transferir a la CUT debe ser mayor a cero.");
        }

        $formattedDate = Carbon::parse($date)->format('Y-m-d');
        $ref = $reference ?: 'CUT-' . strtoupper(Str::random(6));

        $cutAccount = ChartOfAccount::where('code', '1071')->first()
            ?? ChartOfAccount::where('code', '107')->first();

        if (!$cutAccount) {
            throw new DomainException("No se encontró la cuenta contable de la CUT (1071).");
        }

        return DB::transaction(function () use ($sourceBank, $cutFund, $cutAccount, $amount, $formattedDate, $concept, $ref, $userId) {
            $transfer = InternalTransfer::create([
                'uuid'                       => (string) Str::uuid(),
                'transfer_code'              => 'CUT-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                'transfer_type'              => 'TRANSFER_TO_CUT',
                'source_type'                => 'BANK_ACCOUNT',
                'source_bank_account_id'      => $sourceBank->id,
                'destination_type'           => 'CUT',
                'destination_fund_source_id' => $cutFund->id,
                'amount'                     => round($amount, 2),
                'transfer_date'              => $formattedDate,
                'reference_number'           => $ref,
                'concept'                    => $concept,
                'created_by_user_id'         => $userId,
                'status'                     => 'COMPLETED',
            ]);

            // Update balances
            $sourceBank->decrement('current_balance', $amount);
            if ($sourceBank->fundSource) {
                $sourceBank->fundSource->decrement('current_balance', $amount);
            }
            $cutFund->increment('current_balance', $amount);

            // Post double journal entry: Debit 1071 (CUT), Credit 104x (Source Bank)
            $header = [
                'entry_date'     => $formattedDate,
                'entry_type'     => VoucherType::OPERATING,
                'concept'        => "Transferencia a Cuenta Única del Tesoro (CUT): {$concept}",
                'source_type'    => InternalTransfer::class,
                'source_id'      => $transfer->id,
                'event_key'      => 'cut_transfer',
                'currency'       => $sourceBank->currency ?? 'PEN',
                'idempotency_key'=> md5("cut_transfer_{$transfer->id}_{$amount}"),
            ];

            $lines = [
                // Debit: CUT (1071)
                [
                    'account_id'         => $cutAccount->id,
                    'debit'              => round($amount, 2),
                    'credit'             => 0.00,
                    'fund_source_id'     => $cutFund->id,
                    'glosa'              => "Depósito en Cuenta Única del Tesoro (CUT): {$concept}",
                    'document_reference' => $ref,
                ],
                // Credit: Operational Bank (104x)
                [
                    'account_id'         => $sourceBank->accounting_account_id,
                    'debit'              => 0.00,
                    'credit'             => round($amount, 2),
                    'bank_account_id'    => $sourceBank->id,
                    'fund_source_id'     => $sourceBank->fund_source_id,
                    'glosa'              => "Transferencia desde {$sourceBank->bank_name} hacia la CUT: {$concept}",
                    'document_reference' => $ref,
                ],
            ];

            $entry = $this->journalPostingService->postRaw($header, $lines);
            if ($entry) {
                $transfer->update(['journal_entry_id' => $entry->id]);
            }

            return $transfer->fresh(['sourceBankAccount', 'destinationFundSource', 'journalEntry']);
        });
    }
}
