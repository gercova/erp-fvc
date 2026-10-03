<?php

namespace Tests\Feature;

use App\Exports\ActivityDetailedReportExport;
use App\Imports\EconomicReportHistoricalImport;
use App\Models\ActivityTransaction;
use App\Models\ActivityTransactionCategory;
use App\Models\DetailSaleNote;
use App\Models\FundSource;
use App\Models\Product;
use App\Models\ProducedItem;
use App\Models\ProductionBatch;
use App\Models\ProductionCampaign;
use App\Models\ProductionHarvest;
use App\Models\ProductionInputMovement;
use App\Models\ProductionLaborCost;
use App\Models\ProductionRawMaterial;
use App\Models\ProductiveActivity;
use App\Models\RdrBankReconciliation;
use App\Models\SaleNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductiveActivitiesIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected FundSource $fundBn;
    protected FundSource $fundCoop;
    protected ProductiveActivity $activityGavilan;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Usuario Administrador
        $this->adminUser = User::where('user', 'admin')->first() ?? User::firstOrCreate(
            ['user' => 'admin'],
            [
                'nombres' => 'Administrador General',
                'estado' => 1,
                'idcaja' => 1,
                'idalmacen' => 1,
                'password' => bcrypt('admin123'),
            ]
        );

        if (!$this->adminUser->hasRole('SUPERADMIN')) {
            $role = Role::firstOrCreate(['name' => 'SUPERADMIN']);
            $this->adminUser->assignRole($role);
        }

        // 2. Fuentes de Financiamiento
        $this->fundBn = FundSource::where('code', 'BN')->orWhere('code', 'BN-09-RDR')->first()
            ?? FundSource::create([
                'code' => 'BN',
                'name' => 'Banco de la Nación - Cta. Cte. RDR',
                'bank_name' => 'Banco de la Nación',
                'initial_balance' => 45000.00,
                'current_balance' => 45000.00,
                'is_active' => true,
            ]);

        $this->fundCoop = FundSource::where('code', 'COOP_TOCACHE')->orWhere('code', 'COOP-TOCACHE')->first()
            ?? FundSource::create([
                'code' => 'COOP_TOCACHE',
                'name' => 'Cooperativa de Ahorro y Crédito Tocache',
                'bank_name' => 'Coop. Tocache',
                'initial_balance' => 12500.00,
                'current_balance' => 12500.00,
                'is_active' => true,
            ]);

        // 3. Actividad Base: Fundo Gavilán
        $this->activityGavilan = ProductiveActivity::firstOrCreate(
            ['code' => 'ACT-GAVILAN'],
            [
                'name' => 'Fundo Gavilán - Palma Aceitera',
                'type' => 'AGRICULTURAL',
                'cost_center_code' => 'CC-0101',
                'head_user_id' => $this->adminUser->id,
                'status' => 'ACTIVA',
                'execution_progress_percent' => 100.0,
            ]
        );
    }

    /**
     * Caso 1: Cálculo del Balance Mensual por Actividad y Validación del Caso Real
     * Derivado del informe económico: Balance Diciembre de Fundo Gavilán = S/ 67,142.10
     */
    public function test_monthly_balance_calculation_per_activity_matches_economic_report(): void
    {
        $catIngreso = ActivityTransactionCategory::firstOrCreate(
            ['name' => 'Venta de Racimos de Fruto Fresco (Palma)'],
            ['code' => 'ING-PALM-TEST', 'type' => 'INCOME', 'is_active' => true]
        );

        $catEgreso = ActivityTransactionCategory::firstOrCreate(
            ['name' => 'Mano de Obra y Cosecha de Palma'],
            ['code' => 'EGR-PALM-TEST', 'type' => 'EXPENSE', 'is_active' => true]
        );

        // Limpiar transacciones previas del mes 12 del 2025 para aislamiento absoluto
        ActivityTransaction::withTrashed()
            ->where('productive_activity_id', $this->activityGavilan->id)
            ->where('period_year', 2025)
            ->where('period_month', 12)
            ->forceDelete();

        // Ingreso Diciembre: S/ 88,142.10
        ActivityTransaction::create([
            'transaction_code' => 'TRX-TEST-DIC-INC-' . Str::random(6),
            'productive_activity_id' => $this->activityGavilan->id,
            'category_id' => $catIngreso->id,
            'fund_source_id' => $this->fundBn->id,
            'transaction_type' => 'INCOME',
            'amount' => 88142.10,
            'transaction_date' => '2025-12-15',
            'period_month' => 12,
            'period_year' => 2025,
            'voucher_type' => 'LIQUIDACION',
            'voucher_number' => 'LIQ-DIC-001',
            'concept' => 'Liquidación Diciembre Palma Fundo Gavilán',
            'status' => 'VERIFIED',
            'registered_by_user_id' => $this->adminUser->id,
        ]);

        // Egreso Diciembre: S/ 21,000.00
        ActivityTransaction::create([
            'transaction_code' => 'TRX-TEST-DIC-EXP-' . Str::random(6),
            'productive_activity_id' => $this->activityGavilan->id,
            'category_id' => $catEgreso->id,
            'fund_source_id' => $this->fundCoop->id,
            'transaction_type' => 'EXPENSE',
            'amount' => 21000.00,
            'transaction_date' => '2025-12-15',
            'period_month' => 12,
            'period_year' => 2025,
            'voucher_type' => 'PLANILLA',
            'voucher_number' => 'PLN-DIC-001',
            'concept' => 'Planilla de Cosecheros Diciembre',
            'status' => 'VERIFIED',
            'registered_by_user_id' => $this->adminUser->id,
        ]);

        // Consultar el endpoint del Tablero Consolidado
        $response = $this->actingAs($this->adminUser)->getJson(route('productive_activities.cost_center.data', ['year' => 2025]));

        $response->assertStatus(200);
        $data = $response->json();

        // Buscar Fundo Gavilán en la matriz
        $gavilanRow = collect($data['activities'])->firstWhere('id', $this->activityGavilan->id);
        $this->assertNotNull($gavilanRow, 'Fundo Gavilán debe estar presente en la matriz de centros de costo.');

        $month12 = $gavilanRow['months'][12];
        $this->assertEquals(88142.10, $month12['income']);
        $this->assertEquals(21000.00, $month12['expense']);

        // Verificación matemática estricta del caso real: S/ 67,142.10
        $expectedBalance = 67142.10;
        $this->assertEquals($expectedBalance, round($month12['balance'], 2), 'El saldo de Diciembre para Fundo Gavilán debe coincidir exactamente con S/ 67,142.10.');

        // Verificar que los totales consolidados cuadren: Ingresos - Egresos = Saldo
        $grandTotals = $data['grand_totals'];
        $this->assertEquals(
            round($grandTotals['income'] - $grandTotals['expense'], 2),
            round($grandTotals['balance'], 2),
            'La ecuación contable consolidada (Ingresos - Egresos = Saldo) debe ser exacta.'
        );
    }

    /**
     * Caso 2: Módulo de Conciliación RDR y Ajustes Contables
     * Verifica que el sistema compare el saldo declarado vs saldo calculado y detecte diferencias
     */
    public function test_rdr_reconciliation_module_detects_bank_discrepancies(): void
    {
        // 1. Guardar conciliación con saldo bancario declarado
        $response = $this->actingAs($this->adminUser)->postJson(route('productive_activities.rdr.reconciliations.store'), [
            'fund_source_id' => $this->fundBn->id,
            'period_year' => 2025,
            'period_month' => 12,
            'statement_closing_date' => '2025-12-31',
            'bank_statement_balance' => 50000.00,
            'notes' => 'Auditoría de cierre anual Banco de la Nación',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $rec = RdrBankReconciliation::where('fund_source_id', $this->fundBn->id)
            ->where('period_year', 2025)
            ->where('period_month', 12)
            ->first();

        $this->assertNotNull($rec);
        $this->assertEquals(50000.00, (float) $rec->bank_statement_balance);
        $this->assertNotEmpty($rec->status);

        // Si el saldo bancario declarado difiere del saldo del sistema, el estado debe ser DISCREPANCY
        if (abs((float)$rec->reconciled_difference) > 0.01) {
            $this->assertEquals('DISCREPANCY', $rec->status);
        } else {
            $this->assertEquals('BALANCED', $rec->status);
        }

        // 2. Registrar Transferencia a la CUT (Ajuste)
        $initialFundBalance = (float) $this->fundBn->fresh()->current_balance;
        $cutTransferAmount = 1500.00;

        $cutResponse = $this->actingAs($this->adminUser)->postJson(route('productive_activities.rdr.cut_transfers.store'), [
            'productive_activity_id' => $this->activityGavilan->id,
            'source_fund_id' => $this->fundBn->id,
            'cut_account_number' => 'CUT-0000-8877',
            'amount' => $cutTransferAmount,
            'transfer_date' => '2025-12-28',
            'bank_operation_number' => 'OP-BN-99221',
            'notes' => 'Transferencia de recaudación RDR a la CUT institucional',
        ]);

        $cutResponse->assertStatus(200);
        $cutResponse->assertJson(['success' => true]);

        // Verificar que el saldo del fondo se haya descontado
        $newFundBalance = (float) $this->fundBn->fresh()->current_balance;
        $this->assertEquals(
            round($initialFundBalance - $cutTransferAmount, 2),
            round($newFundBalance, 2),
            'El saldo del fondo bancario debe debitar el monto transferido a la CUT.'
        );
    }

    /**
     * Caso 3: Importador Histórico Excel Resiliente
     * Verifica la capacidad de procesar archivos multi-hoja, auto-crear categorías y reportar observaciones
     */
    public function test_historical_excel_import_resilience_and_category_autocreation(): void
    {
        $tempFile = storage_path('app/test_import_suite.xlsx');

        // Generar archivo de prueba con el formato institucional multi-hoja
        Excel::store(new ActivityDetailedReportExport(2025), 'test_import_suite.xlsx', 'local');
        $this->assertFileExists($tempFile);

        // Ejecutar el importador resiliente
        $importer = new EconomicReportHistoricalImport(2025);
        $summary = $importer->import($tempFile);

        // Aserciones de integridad del proceso ETL
        $this->assertGreaterThan(0, $summary['total_sheets_processed'], 'Debe procesar al menos una hoja de actividad.');
        $this->assertGreaterThan(0, $summary['total_rows_imported'], 'Debe extraer filas de rubros operacionales.');
        $this->assertGreaterThan(0, $summary['total_transactions_created'], 'Debe crear transacciones en base de datos.');
        $this->assertIsArray($summary['assumptions_applied'], 'Debe documentar los supuestos técnicos aplicados.');
        $this->assertIsArray($summary['pending_manual_review'], 'Debe retornar la lista de filas pendientes de revisión.');

        // Limpiar archivo temporal
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
    }

    /**
     * Caso 4: Cálculo de Rentabilidad por Producto y Actividad
     * Verifica que Margen Neto = Ingresos - (Mano de Obra + Insumos) y cálculo de ROI
     */
    public function test_profitability_calculation_per_product(): void
    {
        $activityProfit = ProductiveActivity::firstOrCreate(
            ['code' => 'ACT-PALMA-RENT'],
            [
                'name' => 'Fundo Gavilán - Módulo Palma Rentabilidad',
                'type' => 'AGRICULTURAL',
                'cost_center_code' => 'CC-RENT-01',
                'head_user_id' => $this->adminUser->id,
                'status' => 'ACTIVA',
                'execution_progress_percent' => 100.0,
            ]
        );

        $campaign = ProductionCampaign::firstOrCreate(
            ['campaign_code' => 'CAMP-RENT-2025'],
            [
                'productive_activity_id' => $activityProfit->id,
                'name' => 'Campaña Anual Palma Rentabilidad 2025',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'status' => 'in_progress',
                'created_by_user_id' => $this->adminUser->id,
            ]
        );
        $campaign->update(['productive_activity_id' => $activityProfit->id]);

        $batch = ProductionBatch::firstOrCreate(
            ['production_campaign_id' => $campaign->id, 'batch_code' => 'LOTE-RENT-01'],
            [
                'phase_name' => 'Cosecha y Mantenimiento',
                'start_date' => '2025-01-01',
                'status' => 'active',
            ]
        );

        // 1. Insumo consumido: S/ 4,000.00
        $rawMaterial = ProductionRawMaterial::firstOrCreate(
            ['name' => 'Fertilizante NPK Palma Test'],
            ['category' => 'fertilizer', 'unit_of_measurement' => 'Saco 50kg', 'default_unit_cost' => 80.00, 'is_active' => true]
        );

        ProductionInputMovement::where('productive_activity_id', $activityProfit->id)
            ->where('production_raw_material_id', $rawMaterial->id)
            ->forceDelete();

        ProductionInputMovement::create([
            'productive_activity_id' => $activityProfit->id,
            'production_campaign_id' => $campaign->id,
            'production_batch_id' => $batch->id,
            'production_raw_material_id' => $rawMaterial->id,
            'movement_type' => 'outflow_consumption',
            'quantity' => 50,
            'unit_cost' => 80.00,
            'total_cost' => 4000.00,
            'movement_date' => '2025-06-15',
            'registered_by_user_id' => $this->adminUser->id,
        ]);

        // 2. Mano de obra: S/ 2,500.00
        ProductionLaborCost::where('production_batch_id', $batch->id)->forceDelete();
        ProductionLaborCost::create([
            'production_batch_id' => $batch->id,
            'worker_name' => 'Cuadrilla de Cosecha Campo',
            'task_description' => 'Cosecha de racimos',
            'task_date' => '2025-06-15',
            'hours_worked' => 80,
            'hourly_rate' => 31.25,
            'labor_cost' => 2500.00,
        ]);

        // Costo Total = Insumos (4,000) + Mano de Obra (2,500) = S/ 6,500.00
        $totalCost = 6500.00;

        // 3. Producto en el catálogo central y Producto Producido
        $product = Product::firstOrCreate(
            ['codigo_interno' => 'PROD-TEST-PALMA-01'],
            [
                'descripcion' => 'Racimo Fruto Fresco Test',
                'idunidad' => 46,
                'idcategoria' => 3,
                'igv' => 18,
                'idcodigo_igv' => 1,
                'precio_compra' => 0.00,
                'precio_venta' => 600.00,
                'stock_actual' => 100,
                'opcion' => 1,
            ]
        );

        $producedItem = ProducedItem::firstOrCreate(
            ['productive_activity_id' => $activityProfit->id, 'name' => 'Racimo Fruto Fresco Test'],
            [
                'unit_of_measurement' => 'Tonelada',
                'standard_cost' => 260.00,
                'product_id' => $product->id,
                'is_published_to_sales' => true,
            ]
        );
        $producedItem->update(['product_id' => $product->id]);

        // Limpiar cosechas previas de prueba
        ProductionHarvest::where('produced_item_id', $producedItem->id)->forceDelete();
        $harvest = ProductionHarvest::create([
            'productive_activity_id' => $activityProfit->id,
            'production_campaign_id' => $campaign->id,
            'production_batch_id' => $batch->id,
            'produced_item_id' => $producedItem->id,
            'harvest_date' => '2025-06-20',
            'quantity' => 25.00,
            'field_weight_kg' => 25000.00,
            'field_ticket_code' => 'TIK-TEST-600',
            'registered_by_user_id' => $this->adminUser->id,
        ]);

        // Limpiar ventas previas del producto de prueba
        DetailSaleNote::where('idproducto', $product->id)->delete();

        // Registrar Venta en el Core (SaleNote + DetailSaleNote) = S/ 15,000.00
        $saleNote = SaleNote::create([
            'idtipo_comprobante' => 7,
            'serie' => 'NV01',
            'correlativo' => (string) rand(10000, 99999),
            'fecha_emision' => '2025-06-25',
            'fecha_vencimiento' => '2025-06-25',
            'hora' => '10:00:00',
            'idcliente' => 1,
            'modo_pago' => 1,
            'subtotal' => 15000.00,
            'igv' => 0.00,
            'total' => 15000.00,
            'estado' => 1,
            'idusuario' => $this->adminUser->id,
            'idarqueocaja' => 1,
        ]);

        DetailSaleNote::create([
            'idnotaventa' => $saleNote->id,
            'idproducto' => $product->id,
            'cantidad' => 25.00,
            'descuento' => 0.00,
            'igv' => 0.00,
            'precio_unitario' => 600.00,
            'precio_total' => 15000.00,
            'idalmacen' => 1,
            'opcion' => 1,
        ]);

        // Consultar el reporte de Rentabilidad filtrando por el período de junio 2025
        $response = $this->actingAs($this->adminUser)->get(route('production.profitability.index', [
            'activity_id' => $activityProfit->id,
            'product_id' => $producedItem->id,
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-30',
        ]));
        $response->assertStatus(200);

        // Verificación del cálculo de Margen Neto y ROI
        $expectedNetMargin = 15000.00 - $totalCost; // S/ 8,500.00
        $expectedRoi = ($expectedNetMargin / $totalCost) * 100; // 130.77%

        $this->assertEquals(8500.00, $expectedNetMargin);
        $this->assertEquals(130.77, round($expectedRoi, 2));

        // Validar también que la vista contenga las métricas calculadas
        $response->assertViewHas('reportData');
        $reportData = $response->viewData('reportData');
        $this->assertNotEmpty($reportData);
        $itemReport = collect($reportData)->firstWhere('product_name', 'Racimo Fruto Fresco Test');
        $this->assertNotNull($itemReport);
        $this->assertEquals(25.00, $itemReport['volume_harvested']);
        $this->assertEquals(15000.00, $itemReport['sales_revenue']);
        $this->assertEquals(6500.00, $itemReport['total_cost']);
        $this->assertEquals(8500.00, $itemReport['gross_margin']);
        $this->assertEquals(130.77, round($itemReport['roi_percent'], 2));
    }

    /**
     * Caso 5: Verificación del Middleware CheckProductiveActivityAccess
     * Los usuarios no supervisores solo pueden registrar operaciones en actividades asignadas
     */
    public function test_activity_access_middleware_restricts_unauthorized_users(): void
    {
        // Crear usuario con rol RESPONSABLE_ACTIVIDAD
        $responsableUser = User::firstOrCreate(
            ['user' => 'responsable_gavilan'],
            [
                'nombres' => 'Responsable Gavilán Test',
                'password' => bcrypt('password'),
                'estado' => 1,
                'idcaja' => 1,
                'idalmacen' => 1,
            ]
        );
        $responsableRole = Role::firstOrCreate(['name' => 'RESPONSABLE_ACTIVIDAD']);
        $responsableUser->assignRole($responsableRole);

        // Asignar al usuario como responsable únicamente de Fundo Gavilán
        $this->activityGavilan->update(['head_user_id' => $responsableUser->id]);

        // Crear una segunda actividad donde NO está asignado
        $activityPiscicola = ProductiveActivity::firstOrCreate(
            ['code' => 'ACT-PISC-TEST'],
            [
                'name' => 'Módulo Piscícola No Autorizado',
                'type' => 'AQUACULTURE',
                'cost_center_code' => 'CC-PISC',
                'head_user_id' => $this->adminUser->id, // Otro responsable
                'status' => 'ACTIVA',
            ]
        );

        $catIngreso = ActivityTransactionCategory::first();

        // 1. Intento de registrar movimiento en actividad NO asignada -> Debe retornar 403 Forbidden
        $unauthorizedResponse = $this->actingAs($responsableUser)->postJson(route('productive_activities.transactions.store'), [
            'productive_activity_id' => $activityPiscicola->id,
            'category_id' => $catIngreso->id,
            'fund_source_id' => $this->fundBn->id,
            'transaction_type' => 'INCOME',
            'amount' => 500.00,
            'transaction_date' => '2025-10-10',
            'period_month' => 10,
            'period_year' => 2025,
            'voucher_type' => 'RECIBO',
            'voucher_number' => 'REC-001',
            'concept' => 'Intento no autorizado',
        ]);

        $unauthorizedResponse->assertStatus(403);

        // 2. Registro en su propia actividad asignada -> Debe ser Exitoso
        $authorizedResponse = $this->actingAs($responsableUser)->postJson(route('productive_activities.transactions.store'), [
            'productive_activity_id' => $this->activityGavilan->id,
            'category_id' => $catIngreso->id,
            'fund_source_id' => $this->fundBn->id,
            'transaction_type' => 'INCOME',
            'amount' => 500.00,
            'transaction_date' => '2025-10-10',
            'period_month' => 10,
            'period_year' => 2025,
            'voucher_type' => 'RECIBO',
            'voucher_number' => 'REC-002',
            'concept' => 'Registro autorizado en actividad propia',
        ]);

        $authorizedResponse->assertStatus(200);
        $authorizedResponse->assertJson(['success' => true]);

        // 3. Usuario de CONTABILIDAD puede consultar de manera consolidada
        $contabilidadUser = User::firstOrCreate(
            ['user' => 'auditor_contable'],
            [
                'nombres' => 'Auditor Contable Test',
                'password' => bcrypt('password'),
                'estado' => 1,
                'idcaja' => 1,
                'idalmacen' => 1,
            ]
        );
        $contabilidadRole = Role::firstOrCreate(['name' => 'CONTABILIDAD']);
        $contabilidadUser->assignRole($contabilidadRole);

        $readResponse = $this->actingAs($contabilidadUser)->get(route('productive_activities.cost_center.index'));
        $readResponse->assertStatus(200);
    }

    public function test_productive_activity_show_with_tracking_logs_ordered_by_log_date(): void
    {
        $log = \App\Models\ActivityTrackingLog::create([
            'productive_activity_id' => $this->activityGavilan->id,
            'user_id' => $this->adminUser->id,
            'log_date' => now()->toDateString(),
            'progress_percent' => 50.0,
            'status' => 'ACTIVA',
            'comment' => 'Avance al 50% verificado',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('productive_activities.show', $this->activityGavilan->id));

        $response->assertStatus(200);
        $response->assertSee('Avance al 50% verificado');
        $response->assertSee('50%');

        $log->delete();
    }
}
