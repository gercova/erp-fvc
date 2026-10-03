<?php

namespace Database\Factories;

use App\Models\ArchingCash;
use App\Models\Cash;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArchingCash>
 */
class ArchingCashFactory extends Factory
{
    protected $model = ArchingCash::class;

    public function definition(): array
    {
        $initial = 200.00;
        $sales = 350.00;
        $inflow = 50.00;
        $outflow = 30.00;
        $estimated = round($initial + $sales + $inflow - $outflow, 2);

        return [
            'idcaja'         => Cash::value('id') ?? 1,
            'idusuario'      => User::value('id') ?? 1,
            'idalmacen'      => Warehouse::value('id') ?? 1,
            'fecha_inicio'   => now()->toDateString(),
            'fecha_fin'      => now()->toDateString(),
            'monto_inicial'  => $initial,
            'monto_final'    => $estimated,
            'total_ventas'   => $sales,
            'estado'         => 0, // 0 = Cerrado
            'monto_estimado' => $estimated,
            'diferencia'     => 0.00,
            'total_ingresos' => $inflow,
            'total_egresos'  => $outflow,
        ];
    }

    public function open(): static
    {
        return $this->state(fn() => [
            'fecha_fin'      => null,
            'monto_final'    => null,
            'estado'         => 1, // 1 = Abierto
            'monto_estimado' => null,
            'diferencia'     => null,
        ]);
    }
}
