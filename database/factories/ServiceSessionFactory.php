<?php

namespace Database\Factories;

use App\Enums\ServiceSessionStatus;
use App\Models\ServiceEngagement;
use App\Models\ServiceSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceSession>
 */
class ServiceSessionFactory extends Factory
{
    protected $model = ServiceSession::class;

    public function definition(): array
    {
        return [
            'uuid'                  => (string) Str::uuid(),
            'service_engagement_id' => ServiceEngagement::factory(),
            'session_number'        => 1,
            'topic'                 => 'Taller Teórico-Práctico de Capacitación y Transferencia Tecnológica',
            'instructor_user_id'    => User::value('id') ?? 1,
            'session_date'          => now()->toDateString(),
            'start_time'            => '09:00:00',
            'end_time'              => '13:00:00',
            'duration_hours'        => 4.00,
            'location'              => 'Auditorio Principal FVC',
            'status'                => ServiceSessionStatus::CONDUCTED,
            'observations'          => 'Sesión ejecutada conforme al plan de trabajo.',
        ];
    }
}
