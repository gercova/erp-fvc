<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'iddoc'         => 6, // RUC
            'nro_documento' => '20' . fake()->unique()->numerify('#########'),
            'nombres'       => fake()->company() . ' S.A.C.',
            'direccion'     => fake()->address(),
            'codigo_pais'   => 'PE',
            'ubigeo'        => '150101',
            'telefono'      => fake()->numerify('9########'),
            'email'         => fake()->unique()->companyEmail(),
        ];
    }

    public function dni(): static
    {
        return $this->state(fn() => [
            'iddoc'         => 1, // DNI
            'nro_documento' => fake()->unique()->numerify('########'),
            'nombres'       => fake()->name(),
        ]);
    }
}
