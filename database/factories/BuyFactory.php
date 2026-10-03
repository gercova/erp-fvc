<?php

namespace Database\Factories;

use App\Models\Buy;
use App\Models\Provider;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Buy>
 */
class BuyFactory extends Factory
{
    protected $model = Buy::class;

    public function definition(): array
    {
        $subtotal = round(fake()->randomFloat(2, 100, 2000), 2);
        $igv = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);

        return [
            'idproveedor'        => Provider::value('id') ?? 1,
            'idtipo_comprobante' => 1, // Factura Proveedor
            'serie'              => 'E001',
            'correlativo'        => str_pad((string) fake()->unique()->numberBetween(1, 99999), 8, '0', STR_PAD_LEFT),
            'fecha_emision'      => now()->toDateString(),
            'fecha_vencimiento'  => now()->addDays(30)->toDateString(),
            'hora'               => now()->toTimeString(),
            'idmoneda'           => 1, // PEN
            'modo_pago'          => 1, // 1 = Contado
            'exonerada'          => 0.00,
            'inafecta'           => 0.00,
            'gravada'            => $subtotal,
            'igv'                => $igv,
            'total'              => $total,
            'monto_credito'      => 0.00,
            'cuotas'             => null,
            'estado'             => 1, // 1 = Activo/Registrado
            'idalmacen'          => Warehouse::value('id') ?? 1,
            'idusuario'          => User::value('id') ?? 1,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn() => [
            'modo_pago'     => 1,
            'monto_credito' => null,
            'cuotas'        => null,
        ]);
    }

    public function credit(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total'] ?? 1000.00;
            return [
                'modo_pago'         => 2, // 2 = Crédito
                'monto_credito'     => $total,
                'fecha_vencimiento' => now()->addDays(60)->toDateString(),
                'cuotas'            => [
                    [
                        'nro_cuota' => 1,
                        'fecha'     => now()->addDays(30)->toDateString(),
                        'monto'     => round($total / 2, 2),
                    ],
                    [
                        'nro_cuota' => 2,
                        'fecha'     => now()->addDays(60)->toDateString(),
                        'monto'     => round($total - round($total / 2, 2), 2),
                    ],
                ],
            ];
        });
    }
}
