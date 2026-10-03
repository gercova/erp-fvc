<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\RdrBankReconciliation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RdrBankReconciliation>
 */
class RdrBankReconciliationFactory extends Factory
{
    protected $model = RdrBankReconciliation::class;

    public function definition(): array
    {
        $balance = 50000.00;

        return [
            'uuid'                      => (string) Str::uuid(),
            'fund_source_id'            => 1,
            'bank_account_id'           => BankAccount::value('id') ?? 1,
            'accounting_period_id'      => null,
            'period_year'               => 2026,
            'period_month'              => 10,
            'statement_closing_date'    => '2026-10-31',
            'bank_statement_balance'    => $balance,
            'system_calculated_balance' => $balance,
            'book_calculated_balance'   => $balance,
            'uncredited_deposits'       => 0.00,
            'outstanding_checks'        => 0.00,
            'unrecorded_bank_charges'   => 0.00,
            'unrecorded_bank_credits'   => 0.00,
            'reconciled_difference'     => 0.00,
            'reconciled_at'             => now(),
            'status'                    => 'RECONCILED',
            'reconciled_by_user_id'     => User::value('id') ?? 1,
            'approved_by_user_id'       => User::value('id') ?? 1,
            'notes'                     => 'Conciliación bancaria mensual cuadrada y aprobada.',
        ];
    }
}
