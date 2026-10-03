<?php

namespace Database\Factories;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Provider>
 */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'iddoc'         => 6, // RUC
            'nro_documento' => '20' . fake()->unique()->numerify('#########'),
            'nombres'       => 'PROVEEDOR COMERCIAL ' . fake()->company() . ' S.A.C.',
            'direccion'     => fake()->address(),
            'codigo_pais'   => 'PE',
            'ubigeo'        => '150101',
            'telefono'      => fake()->numerify('9########'),
            'email'         => fake()->unique()->companyEmail(),
        ];
    }
}
