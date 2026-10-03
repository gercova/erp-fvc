<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\FundSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        $account10411Id = ChartOfAccount::where('code', '10411')->value('id') ?? 11;
        $fundSourceId = FundSource::value('id') ?? 1;

        return [
            'uuid'                  => (string) Str::uuid(),
            'fund_source_id'        => $fundSourceId,
            'account_number'        => '00-011-' . fake()->unique()->numerify('######'),
            'cci'                   => '018011' . fake()->unique()->numerify('##############'),
            'bank_name'             => 'Banco de la Nación',
            'account_type'          => 'CURRENT',
            'currency'              => 'PEN',
            'accounting_account_id' => $account10411Id,
            'initial_balance'       => 0.00,
            'current_balance'       => 0.00,
            'is_active'             => true,
        ];
    }
}
