<?php

namespace Database\Factories;

use App\Enums\AgreementStatus;
use App\Enums\AgreementType;
use App\Models\Agreement;
use App\Models\Area;
use App\Models\Client;
use App\Models\ProductiveActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agreement>
 */
class AgreementFactory extends Factory
{
    protected $model = Agreement::class;

    public function definition(): array
    {
        $amount = round(fake()->randomFloat(2, 5000, 50000), 2);
        $startDate = now()->subMonths(1)->toDateString();
        $endDate   = now()->addMonths(11)->toDateString();

        return [
            'uuid'                              => (string) Str::uuid(),
            'code'                              => 'CONV-2026-' . str_pad((string) fake()->unique()->numberBetween(100, 999), 4, '0', STR_PAD_LEFT),
            'type'                              => AgreementType::SPECIFIC,
            'title'                             => 'Convenio Específico de Cooperación y Servicios: ' . fake()->company(),
            'objective'                         => 'Desarrollo de capacidades tecnológicas y transferencia productiva.',
            'client_id'                         => Client::value('id') ?? 1,
            'counterparty_signatory_name'       => fake()->name(),
            'counterparty_signatory_role'       => 'Representante Legal',
            'counterparty_signatory_document'   => fake()->numerify('########'),
            'area_id'                           => Area::value('id') ?? 1,
            'coordinator_user_id'               => User::value('id') ?? 1,
            'productive_activity_id'            => ProductiveActivity::value('id'),
            'signature_date'                    => $startDate,
            'start_date'                        => $startDate,
            'end_date'                          => $endDate,
            'original_end_date'                 => $endDate,
            'currency'                          => 'PEN',
            'total_amount'                      => $amount,
            'original_amount'                   => $amount,
            'counterparty_contribution'         => $amount,
            'institution_contribution'          => 0.00,
            'status'                            => AgreementStatus::ACTIVE,
            'requires_financial_settlement'     => true,
            'resolution_number'                 => 'RES-2026-' . fake()->numberBetween(10, 99),
            'created_by_user_id'                => User::value('id') ?? 1,
        ];
    }
}
