<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\BankMovement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankMovement>
 */
class BankMovementFactory extends Factory
{
    protected $model = BankMovement::class;

    public function definition(): array
    {
        return [
            'uuid'                     => (string) Str::uuid(),
            'bank_account_id'          => BankAccount::value('id') ?? 1,
            'movement_date'            => now()->toDateString(),
            'operation_number'         => 'OP-' . fake()->unique()->numberBetween(100000, 999999),
            'movement_type'            => 'DEPOSIT',
            'amount'                   => round(fake()->randomFloat(2, 50, 3000), 2),
            'concept'                  => 'Abono en cuenta bancaria por recaudación institucional',
            'reconciliation_status'    => 'UNMATCHED',
            'matched_journal_entry_id' => null,
            'journal_entry_id'         => null,
        ];
    }
}
