<?php

namespace Database\Factories;

use App\Models\Billing;
use App\Models\Client;
use App\Models\TypeDocument;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Billing>
 */
class BillingFactory extends Factory
{
    protected $model = Billing::class;

    public function definition(): array
    {
        $taxable = round(fake()->randomFloat(2, 50, 1000), 2);
        $igv = round($taxable * 0.18, 2);
        $total = round($taxable + $igv, 2);

        return [
            'idtipo_comprobante' => 1, // Factura by default
            'serie'              => 'F001',
            'correlativo'        => str_pad((string) fake()->unique()->numberBetween(1, 99999), 8, '0', STR_PAD_LEFT),
            'fecha_emision'      => now()->toDateString(),
            'fecha_vencimiento'  => now()->addDays(15)->toDateString(),
            'hora'               => now()->toTimeString(),
            'idcliente'          => Client::factory(),
            'idmoneda'           => 1, // PEN
            'idpago'             => 1, // Contado / Efectivo
            'modo_pago'          => 1, // Contado
            'sunat_forma_pago'   => 'Contado',
            'exonerada'          => 0.00,
            'inafecta'           => 0.00,
            'gravada'            => $taxable,
            'anticipo'           => 0.00,
            'igv'                => $igv,
            'icbper'             => 0.00,
            'gratuita'           => 0.00,
            'otros_cargos'       => 0.00,
            'total'              => $total,
            'monto_credito'      => 0.00,
            'cuotas'             => null,
            'anulado'            => 0,
            'idalmacen'          => Warehouse::value('id') ?? 1,
            'idusuario'          => User::value('id') ?? 1,
        ];
    }

    public function invoice(): static
    {
        return $this->state(fn() => [
            'idtipo_comprobante' => 1,
            'serie'              => 'F001',
        ]);
    }

    public function receipt(): static
    {
        return $this->state(fn() => [
            'idtipo_comprobante' => 2,
            'serie'              => 'B001',
        ]);
    }

    public function exempt(): static
    {
        return $this->state(function (array $attributes) {
            $amount = round(fake()->randomFloat(2, 50, 500), 2);
            return [
                'gravada'   => 0.00,
                'exonerada' => $amount,
                'inafecta'  => 0.00,
                'igv'       => 0.00,
                'total'     => $amount,
            ];
        });
    }

    public function nonTaxable(): static
    {
        return $this->state(function (array $attributes) {
            $amount = round(fake()->randomFloat(2, 50, 500), 2);
            return [
                'gravada'   => 0.00,
                'exonerada' => 0.00,
                'inafecta'  => $amount,
                'igv'       => 0.00,
                'total'     => $amount,
            ];
        });
    }

    public function creditNote(Billing $parent): static
    {
        return $this->state(fn() => [
            'idtipo_comprobante'     => 3, // Nota de Crédito
            'serie'                  => $parent->idtipo_comprobante == 1 ? 'FC01' : 'BC01',
            'idfactura_anular'       => $parent->id,
            'id_tipo_nota_credito'   => 1, // Anulación de la operación
            'motivo'                 => 'Devolución de mercadería o anulación de operación',
            'gravada'                => $parent->gravada,
            'exonerada'              => $parent->exonerada,
            'inafecta'               => $parent->inafecta,
            'igv'                    => $parent->igv,
            'total'                  => $parent->total,
            'idcliente'              => $parent->idcliente,
        ]);
    }
}
