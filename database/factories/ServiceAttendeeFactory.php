<?php

namespace Database\Factories;

use App\Models\ServiceAttendee;
use App\Models\ServiceSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceAttendee>
 */
class ServiceAttendeeFactory extends Factory
{
    protected $model = ServiceAttendee::class;

    public function definition(): array
    {
        return [
            'uuid'                  => (string) Str::uuid(),
            'service_session_id'    => ServiceSession::factory(),
            'service_engagement_id' => null, // resolved in booted creating hook
            'full_name'             => fake()->name(),
            'dni_or_document'       => fake()->unique()->numerify('########'),
            'email'                 => fake()->unique()->safeEmail(),
            'phone'                 => fake()->numerify('9########'),
            'organization'          => fake()->company(),
            'attended'              => true,
            'evaluation_score'      => 18.00,
            'certificate_code'      => 'CERT-2026-' . fake()->unique()->numberBetween(1000, 9999),
            'certificate_issued_at' => now(),
            'notes'                 => 'Participante destacado en la evaluación práctica.',
        ];
    }
}
