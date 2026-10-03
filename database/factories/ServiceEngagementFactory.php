<?php

namespace Database\Factories;

use App\Enums\ServiceEngagementStatus;
use App\Models\Client;
use App\Models\ProductiveActivity;
use App\Models\ServiceEngagement;
use App\Models\TechnologicalService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceEngagement>
 */
class ServiceEngagementFactory extends Factory
{
    protected $model = ServiceEngagement::class;

    public function definition(): array
    {
        $hours = 20.00;
        $rate = 100.00;
        $total = $hours * $rate;

        return [
            'uuid'                        => (string) Str::uuid(),
            'code'                        => 'SERV-2026-' . str_pad((string) fake()->unique()->numberBetween(100, 999), 4, '0', STR_PAD_LEFT),
            'client_id'                   => Client::value('id') ?? 1,
            'technological_service_id'    => TechnologicalService::value('id') ?? 1,
            'productive_activity_id'      => ProductiveActivity::value('id') ?? 1,
            'responsible_user_id'         => User::value('id') ?? 1,
            'description'                 => 'Capacitación y Asistencia Técnica Especializada',
            'delivery_modality'           => 'IN_PERSON',
            'contracted_hours'            => $hours,
            'consumed_hours'              => 0.00,
            'hourly_rate'                 => $rate,
            'quantity'                    => 1.00,
            'unit_price'                  => $total,
            'total_amount'                => $total,
            'currency'                    => 'PEN',
            'start_date'                  => now()->toDateString(),
            'expected_delivery_date'      => now()->addDays(30)->toDateString(),
            'status'                      => ServiceEngagementStatus::IN_PROGRESS,
            'min_attendance_percent'      => 80.00,
            'low_balance_threshold_hours' => 5.00,
        ];
    }
}
