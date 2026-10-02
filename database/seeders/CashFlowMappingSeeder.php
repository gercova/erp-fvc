<?php

namespace Database\Seeders;

use App\Models\CashFlowMapping;
use Illuminate\Database\Seeder;

class CashFlowMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mappings = [
            // OPERATING ACTIVITIES
            [
                'category' => 'OPERATING',
                'flow_type' => 'INFLOW',
                'concept_name' => 'Cobranzas de facturas y notas por cobrar a clientes',
                'account_prefix' => '12',
                'event_key' => 'sale',
                'sort_order' => 10,
            ],
            [
                'category' => 'OPERATING',
                'flow_type' => 'INFLOW',
                'concept_name' => 'Ventas contado y comerciales',
                'account_prefix' => '70',
                'event_key' => 'sale_completed',
                'sort_order' => 20,
            ],
            [
                'category' => 'OPERATING',
                'flow_type' => 'INFLOW',
                'concept_name' => 'Otros ingresos de gestión y sobrantes de caja',
                'account_prefix' => '75',
                'event_key' => 'other_income',
                'sort_order' => 30,
            ],
            [
                'category' => 'OPERATING',
                'flow_type' => 'OUTFLOW',
                'concept_name' => 'Pagos a proveedores comerciales por compras',
                'account_prefix' => '42',
                'event_key' => 'buy',
                'sort_order' => 40,
            ],
            [
                'category' => 'OPERATING',
                'flow_type' => 'OUTFLOW',
                'concept_name' => 'Compras directas de insumos y mercaderías',
                'account_prefix' => '60',
                'event_key' => 'purchase_recorded',
                'sort_order' => 50,
            ],
            [
                'category' => 'OPERATING',
                'flow_type' => 'OUTFLOW',
                'concept_name' => 'Remuneraciones y jornales de campo',
                'account_prefix' => '62',
                'event_key' => 'payroll',
                'sort_order' => 60,
            ],
            [
                'category' => 'OPERATING',
                'flow_type' => 'OUTFLOW',
                'concept_name' => 'Gastos de servicios prestados por terceros y viáticos',
                'account_prefix' => '63',
                'event_key' => 'services',
                'sort_order' => 70,
            ],
            [
                'category' => 'OPERATING',
                'flow_type' => 'OUTFLOW',
                'concept_name' => 'Tributos, IGV y Renta',
                'account_prefix' => '40',
                'event_key' => 'taxes',
                'sort_order' => 80,
            ],
            [
                'category' => 'OPERATING',
                'flow_type' => 'OUTFLOW',
                'concept_name' => 'Otros gastos de gestión operativa',
                'account_prefix' => '65',
                'event_key' => 'other_expense',
                'sort_order' => 90,
            ],

            // INVESTING ACTIVITIES
            [
                'category' => 'INVESTING',
                'flow_type' => 'OUTFLOW',
                'concept_name' => 'Adquisición de maquinaria, inmuebles y equipos',
                'account_prefix' => '33',
                'event_key' => 'fixed_asset_purchase',
                'sort_order' => 100,
            ],
            [
                'category' => 'INVESTING',
                'flow_type' => 'OUTFLOW',
                'concept_name' => 'Desarrollo de plantaciones y activos biológicos',
                'account_prefix' => '35',
                'event_key' => 'biological_asset_dev',
                'sort_order' => 110,
            ],
            [
                'category' => 'INVESTING',
                'flow_type' => 'INFLOW',
                'concept_name' => 'Venta de activos e inversiones',
                'account_prefix' => '756',
                'event_key' => 'asset_sale',
                'sort_order' => 120,
            ],

            // FINANCING ACTIVITIES
            [
                'category' => 'FINANCING',
                'flow_type' => 'OUTFLOW',
                'concept_name' => 'Transferencias a la Cuenta Única del Tesoro (CUT)',
                'account_prefix' => '107',
                'event_key' => 'cut_transfer',
                'sort_order' => 130,
            ],
            [
                'category' => 'FINANCING',
                'flow_type' => 'OUTFLOW',
                'concept_name' => 'Préstamos internos y habilitaciones de viáticos',
                'account_prefix' => '1413',
                'event_key' => 'internal_loan_disbursement',
                'sort_order' => 140,
            ],
            [
                'category' => 'FINANCING',
                'flow_type' => 'INFLOW',
                'concept_name' => 'Devolución de préstamos internos y habilitaciones',
                'account_prefix' => '1413',
                'event_key' => 'internal_loan_repayment',
                'sort_order' => 150,
            ],
            [
                'category' => 'FINANCING',
                'flow_type' => 'INFLOW',
                'concept_name' => 'Financiamiento recibido y aportes de capital',
                'account_prefix' => '45',
                'event_key' => 'financing_received',
                'sort_order' => 160,
            ],
        ];

        foreach ($mappings as $data) {
            CashFlowMapping::updateOrCreate(
                [
                    'category' => $data['category'],
                    'concept_name' => $data['concept_name'],
                ],
                $data
            );
        }
    }
}
