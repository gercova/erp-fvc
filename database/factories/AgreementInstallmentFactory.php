<?php

namespace Database\Factories;

use App\Enums\InstallmentStatus;
use App\Models\Agreement;
use App\Models\AgreementInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgreementInstallment>
 */
class AgreementInstallmentFactory extends Factory
{
    protected $model = AgreementInstallment::class;

    public function definition(): array
    {
        return [
            'uuid'               => (string) Str::uuid(),
            'agreement_id'       => Agreement::factory(),
            'installment_number' => 1,
            'description'        => 'Cuota de financiamiento por convenio',
            'milestone_condition'=> 'A la firma y entrega del informe de actividades',
            'due_date'           => now()->addDays(30)->toDateString(),
            'amount'             => 2500.00,
            'currency'           => 'PEN',
            'igv_affected'       => true,
            'status'             => InstallmentStatus::PENDING,
            'billing_id'         => null,
            'sale_note_id'       => null,
        ];
    }
}
