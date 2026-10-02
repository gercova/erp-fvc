<?php

namespace Database\Seeders;

use App\Models\AccountingRule;
use App\Models\AccountingRuleLine;
use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class AccountingRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
                'event_code'  => 'SALE_BILLING',
                'description' => 'Venta comercial con Factura o Boleta Electrónica',
                'source_type' => 'App\Models\Billing',
                'priority'    => 10,
                'lines'       => [
                    ['line_type' => 'DEBIT',  'account_code' => '1212',  'calculation' => 'TOTAL',    'cost_center' => 'NONE',     'desc' => 'Cuentas por cobrar comerciales emitidas'],
                    ['line_type' => 'CREDIT', 'account_code' => '40111', 'calculation' => 'IGV',      'cost_center' => 'NONE',     'desc' => 'IGV Cuenta propia (Débito fiscal)'],
                    ['line_type' => 'CREDIT', 'account_code' => '70111', 'calculation' => 'SUBTOTAL', 'cost_center' => 'DOCUMENT', 'desc' => 'Venta de mercaderías / producción'],
                ],
            ],
            [
                'event_code'  => 'SALE_NOTE',
                'description' => 'Venta interna con Nota de Venta',
                'source_type' => 'App\Models\SaleNote',
                'priority'    => 10,
                'lines'       => [
                    ['line_type' => 'DEBIT',  'account_code' => '1219',  'calculation' => 'TOTAL', 'cost_center' => 'NONE',     'desc' => 'Cuentas por cobrar Notas de Venta'],
                    ['line_type' => 'CREDIT', 'account_code' => '70111', 'calculation' => 'TOTAL', 'cost_center' => 'DOCUMENT', 'desc' => 'Ingreso por ventas régimen interno'],
                ],
            ],
            [
                'event_code'  => 'BUY_INVOICE',
                'description' => 'Compra a proveedor con Factura / Boleta',
                'source_type' => 'App\Models\Buy',
                'priority'    => 10,
                'lines'       => [
                    ['line_type' => 'DEBIT',  'account_code' => '6011',  'calculation' => 'SUBTOTAL', 'cost_center' => 'DOCUMENT', 'desc' => 'Compras mercaderías / insumos'],
                    ['line_type' => 'DEBIT',  'account_code' => '40112', 'calculation' => 'IGV',      'cost_center' => 'NONE',     'desc' => 'IGV Crédito fiscal'],
                    ['line_type' => 'CREDIT', 'account_code' => '4212',  'calculation' => 'TOTAL',    'cost_center' => 'NONE',     'desc' => 'Facturas por pagar a proveedores'],
                ],
            ],
            [
                'event_code'  => 'CASH_COUNT',
                'description' => 'Apertura de turno de caja (Fondo fijo)',
                'source_type' => 'App\Models\ArchingCash',
                'priority'    => 10,
                'lines'       => [
                    ['line_type' => 'DEBIT',  'account_code' => '102',  'calculation' => 'TOTAL', 'cost_center' => 'NONE', 'desc' => 'Fondos fijos de caja'],
                    ['line_type' => 'CREDIT', 'account_code' => '1011', 'calculation' => 'TOTAL', 'cost_center' => 'NONE', 'desc' => 'Salida de Caja Central'],
                ],
            ],
            [
                'event_code'  => 'CUT_TRANSFER',
                'description' => 'Transferencia a la Cuenta Única del Tesoro (CUT)',
                'source_type' => 'App\Models\RdrCutTransfer',
                'priority'    => 10,
                'lines'       => [
                    ['line_type' => 'DEBIT',  'account_code' => '1071',  'calculation' => 'TOTAL', 'cost_center' => 'NONE', 'desc' => 'Fondos sujetos a restricción (CUT)'],
                    ['line_type' => 'CREDIT', 'account_code' => '10411', 'calculation' => 'TOTAL', 'cost_center' => 'NONE', 'desc' => 'Salida Banco de la Nación RDR'],
                ],
            ],
            [
                'event_code'  => 'INTERNAL_LOAN',
                'description' => 'Préstamo interno y habilitación de viáticos de campo',
                'source_type' => 'App\Models\RdrInternalLoan',
                'priority'    => 10,
                'lines'       => [
                    ['line_type' => 'DEBIT',  'account_code' => '1413',  'calculation' => 'TOTAL', 'cost_center' => 'DOCUMENT', 'desc' => 'Préstamos al personal y viáticos'],
                    ['line_type' => 'CREDIT', 'account_code' => '10411', 'calculation' => 'TOTAL', 'cost_center' => 'NONE',     'desc' => 'Salida Banco de la Nación RDR'],
                ],
            ],
            [
                'event_code'  => 'DIRECT_INCOME',
                'description' => 'Ingreso directo APE sin comprobante core',
                'source_type' => 'App\Models\ActivityTransaction',
                'priority'    => 10,
                'lines'       => [
                    ['line_type' => 'DEBIT',  'account_code' => '1012', 'calculation' => 'TOTAL', 'cost_center' => 'NONE',     'desc' => 'Ingreso en Caja / Mostrador'],
                    ['line_type' => 'CREDIT', 'account_code' => '759',  'calculation' => 'TOTAL', 'cost_center' => 'DOCUMENT', 'desc' => 'Otros ingresos de gestión APE'],
                ],
            ],
            [
                'event_code'  => 'DIRECT_EXPENSE',
                'description' => 'Gasto directo APE con Declaración Jurada de campo',
                'source_type' => 'App\Models\ActivityTransaction',
                'priority'    => 10,
                'lines'       => [
                    ['line_type' => 'DEBIT',  'account_code' => '659',  'calculation' => 'TOTAL', 'cost_center' => 'DOCUMENT', 'desc' => 'Otros gastos de gestión campo'],
                    ['line_type' => 'CREDIT', 'account_code' => '1012', 'calculation' => 'TOTAL', 'cost_center' => 'NONE',     'desc' => 'Salida de Caja / Mostrador'],
                ],
            ],
        ];

        foreach ($rules as $ruleData) {
            $lines = $ruleData['lines'];
            unset($ruleData['lines']);

            $rule = AccountingRule::updateOrCreate(
                ['event_code' => $ruleData['event_code']],
                $ruleData
            );

            // Delete old lines if any and re-create
            $rule->ruleLines()->delete();

            foreach ($lines as $line) {
                $account = ChartOfAccount::where('code', $line['account_code'])->first();
                if ($account) {
                    AccountingRuleLine::create([
                        'accounting_rule_id' => $rule->id,
                        'line_type'          => $line['line_type'],
                        'account_id'         => $account->id,
                        'calculation_type'   => $line['calculation'],
                        'cost_center_source' => $line['cost_center'],
                        'description'        => $line['desc'],
                    ]);
                }
            }
        }
    }
}
