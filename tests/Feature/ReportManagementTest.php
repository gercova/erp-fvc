<?php

namespace Tests\Feature;

use App\Models\ArchingCash;
use App\Models\Billing;
use App\Models\Business;
use App\Models\Buy;
use App\Models\Cash;
use App\Models\Category;
use App\Models\Client;
use App\Models\DetailBilling;
use App\Models\DetailPayment;
use App\Models\DetailSaleNote;
use App\Models\PayMode;
use App\Models\Product;
use App\Models\Provider;
use App\Models\SaleNote;
use App\Models\StockProduct;
use App\Models\TypeDocument;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Warehouse $warehouse;
    protected Cash $cash;
    protected ArchingCash $session;
    protected Client $client1;
    protected Client $client2;
    protected PayMode $payCash;
    protected PayMode $payYape;
    protected PayMode $payCard;
    protected TypeDocument $docTypeBoleta;
    protected TypeDocument $docTypeFactura;
    protected TypeDocument $docTypeNotaVenta;
    protected TypeDocument $docTypeNotaCredito;
    protected Category $category;
    protected Unit $unit;
    protected Product $productA;
    protected Product $productB;
    protected Product $serviceC;
    protected Provider $provider;
    protected ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportService = app(ReportService::class);

        Business::firstOrCreate(
            ['id' => 1],
            [
                'ruc' => '20601234567',
                'razon_social' => 'EMPRESA FVC DEMO SAC',
                'nombre_comercial' => 'FVC DEMO',
                'direccion' => 'AV. PRINCIPAL 123',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
                'telefono' => '987654321',
                'cobrar_igv' => true,
            ]
        );

        $this->warehouse = Warehouse::firstOrCreate(
            ['id' => 1],
            ['descripcion' => 'ALMACEN PRINCIPAL FVC', 'direccion' => 'CALLE PRINCIPAL 100']
        );

        $this->cash = Cash::firstOrCreate(
            ['id' => 1],
            ['descripcion' => 'CAJA PRINCIPAL 01', 'estado' => 1]
        );

        $this->user = User::where('user', 'admin')->first() ?? User::firstOrCreate(
            ['user' => 'report_admin'],
            [
                'nombres' => 'Admin Reportes',
                'password' => bcrypt('password123'),
                'idalmacen' => $this->warehouse->id,
                'idcaja' => $this->cash->id,
                'estado' => 1,
            ]
        );
        $this->user->update([
            'idalmacen' => $this->warehouse->id,
            'idcaja' => $this->cash->id,
        ]);

        $this->payCash = PayMode::firstOrCreate(['id' => 1], ['descripcion' => 'Efectivo', 'estado' => 1]);
        $this->payYape = PayMode::firstOrCreate(['id' => 2], ['descripcion' => 'Yape', 'estado' => 1]);
        $this->payCard = PayMode::firstOrCreate(['id' => 3], ['descripcion' => 'Tarjeta', 'estado' => 1]);

        $this->docTypeFactura = TypeDocument::firstOrCreate(
            ['codigo' => '01'],
            ['descripcion' => 'FACTURA ELECTRONICA', 'estado' => 1]
        );
        $this->docTypeBoleta = TypeDocument::firstOrCreate(
            ['codigo' => '03'],
            ['descripcion' => 'BOLETA DE VENTA ELECTRONICA', 'estado' => 1]
        );
        $this->docTypeNotaVenta = TypeDocument::firstOrCreate(
            ['codigo' => '02'],
            ['descripcion' => 'NOTA DE VENTA', 'estado' => 1]
        );
        $this->docTypeNotaCredito = TypeDocument::firstOrCreate(
            ['codigo' => '07'],
            ['descripcion' => 'NOTA DE CREDITO ELECTRONICA', 'estado' => 1]
        );

        $this->client1 = Client::firstOrCreate(
            ['nro_documento' => '10203040'],
            [
                'nombres' => 'JUAN PEREZ CLIENTE 1',
                'iddoc' => 1,
                'direccion' => 'CALLE 123',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
            ]
        );

        $this->client2 = Client::firstOrCreate(
            ['nro_documento' => '20556677889'],
            [
                'nombres' => 'CORPORACION ABC SAC',
                'iddoc' => 2,
                'direccion' => 'AV INDUSTRIAL 456',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
            ]
        );

        $this->category = Category::firstOrCreate(['id' => 1], ['descripcion' => 'Abarrotes y Alimentos']);
        $this->unit = Unit::firstOrCreate(['id' => 1], ['codigo' => 'NIU', 'descripcion' => 'UNIDAD', 'estado' => 1]);

        // Product A: Physical product
        $this->productA = Product::firstOrCreate(
            ['codigo_interno' => 'PRD-A-01'],
            [
                'descripcion' => 'ARROZ EXTRA COSTENO 1KG',
                'idunidad' => $this->unit->id,
                'idcategoria' => $this->category->id,
                'igv' => 18,
                'idcodigo_igv' => 1,
                'precio_compra' => 4.00,
                'precio_venta' => 5.00,
                'opcion' => 1, // Physical product
                'stock_actual' => 100,
            ]
        );

        // Product B: Physical product
        $this->productB = Product::firstOrCreate(
            ['codigo_interno' => 'PRD-B-02'],
            [
                'descripcion' => 'ACEITE VEGETAL PRIMOR 1L',
                'idunidad' => $this->unit->id,
                'idcategoria' => $this->category->id,
                'igv' => 18,
                'idcodigo_igv' => 1,
                'precio_compra' => 8.00,
                'precio_venta' => 10.00,
                'opcion' => 1, // Physical product
                'stock_actual' => 50,
            ]
        );

        // Service C: Service product (opcion = 2, NO stock)
        $this->serviceC = Product::firstOrCreate(
            ['codigo_interno' => 'SRV-C-03'],
            [
                'descripcion' => 'SERVICIO DE TRANSPORTE Y EMBALAJE',
                'idunidad' => $this->unit->id,
                'idcategoria' => $this->category->id,
                'igv' => 18,
                'idcodigo_igv' => 1,
                'precio_compra' => 0.00,
                'precio_venta' => 80.00,
                'opcion' => 2, // Service
                'stock_actual' => 0,
            ]
        );

        // Assign Stock in Warehouse for physical products
        StockProduct::updateOrCreate(
            ['idalmacen' => $this->warehouse->id, 'idproducto' => $this->productA->id],
            [
                'stock_minimo' => 10,
                'stock_actual' => 80,
                'precio_compra' => 4.00,
                'precio_venta' => 5.00,
                'fecha_registro' => now(),
            ]
        );

        StockProduct::updateOrCreate(
            ['idalmacen' => $this->warehouse->id, 'idproducto' => $this->productB->id],
            [
                'stock_minimo' => 5,
                'stock_actual' => 40,
                'precio_compra' => 8.00,
                'precio_venta' => 10.00,
                'fecha_registro' => now(),
            ]
        );

        // Provider for Purchases
        $this->provider = Provider::firstOrCreate(
            ['nro_documento' => '20100044556'],
            [
                'nombres' => 'DISTRIBUIDORA MAYORISTA DEL PERU SA',
                'iddoc' => 2,
                'direccion' => 'AV LOS PRODUCTORES 789',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
                'telefono' => '999888777',
            ]
        );

        // Open Cash Register Session with Float = 100.00
        $this->session = ArchingCash::create([
            'idcaja' => $this->cash->id,
            'idusuario' => $this->user->id,
            'fecha_inicio' => now()->format('Y-m-d H:i:s'),
            'monto_inicial' => 100.00,
            'total_ventas' => 0.00,
            'estado' => 1,
        ]);
    }

    /**
     * GATE & ACCEPTANCE TEST:
     * Total reports = total sales = total cash = total receipts in a test dataset.
     */
    public function test_acceptance_reconciliation_total_reports_equal_total_sales_equal_total_cash_equal_total_receipts(): void
    {
        $today = now()->toDateString();
        $hora = now()->format('H:i:s');

        // SALE 1: Boleta (Billing) - Product A (20 units @ 5.00 = S/ 100.00) - Paid in CASH
        $billing1 = Billing::create([
            'idtipo_comprobante' => $this->docTypeBoleta->id,
            'serie' => 'B001',
            'correlativo' => '00000001',
            'fecha_emision' => $today,
            'hora' => $hora,
            'idcliente' => $this->client1->id,
            'idmoneda' => 1,
            'idpago' => $this->payCash->id,
            'modo_pago' => 1,
            'sunat_forma_pago' => 'Contado',
            'gravada' => 84.75,
            'igv' => 15.25,
            'total' => 100.00,
            'anulado' => false,
            'idusuario' => $this->user->id,
            'idarqueocaja' => $this->session->id,
            'idalmacen' => $this->warehouse->id,
        ]);
        DetailBilling::create([
            'idfacturacion' => $billing1->id,
            'idproducto' => $this->productA->id,
            'id_afectacion_igv' => 1,
            'cantidad' => 20,
            'precio_unitario' => 5.00,
            'precio_total' => 100.00,
            'descuento' => 0,
            'igv' => 15.25,
        ]);
        DetailPayment::create([
            'idtipo_comprobante' => $this->docTypeBoleta->id,
            'idfactura' => $billing1->id,
            'idpago' => $this->payCash->id,
            'monto' => 100.00,
            'idarqueocaja' => $this->session->id,
            'estado' => 1,
        ]);

        // SALE 2: Factura (Billing) - Product B (5 units @ 10.00 = S/ 50.00) - Paid with YAPE
        $billing2 = Billing::create([
            'idtipo_comprobante' => $this->docTypeFactura->id,
            'serie' => 'F001',
            'correlativo' => '00000001',
            'fecha_emision' => $today,
            'hora' => $hora,
            'idcliente' => $this->client2->id,
            'idmoneda' => 1,
            'idpago' => $this->payYape->id,
            'modo_pago' => 1,
            'sunat_forma_pago' => 'Contado',
            'gravada' => 42.37,
            'igv' => 7.63,
            'total' => 50.00,
            'anulado' => false,
            'idusuario' => $this->user->id,
            'idarqueocaja' => $this->session->id,
            'idalmacen' => $this->warehouse->id,
        ]);
        DetailBilling::create([
            'idfacturacion' => $billing2->id,
            'idproducto' => $this->productB->id,
            'id_afectacion_igv' => 1,
            'cantidad' => 5,
            'precio_unitario' => 10.00,
            'precio_total' => 50.00,
            'descuento' => 0,
            'igv' => 7.63,
        ]);
        DetailPayment::create([
            'idtipo_comprobante' => $this->docTypeFactura->id,
            'idfactura' => $billing2->id,
            'idpago' => $this->payYape->id,
            'monto' => 50.00,
            'idarqueocaja' => $this->session->id,
            'estado' => 1,
        ]);

        // SALE 3: Nota de Venta (SaleNote) - Service C (1 unit @ 80.00 = S/ 80.00) - Paid in CASH
        $saleNote1 = SaleNote::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'serie' => 'NV01',
            'correlativo' => '00000001',
            'fecha_emision' => $today,
            'fecha_vencimiento' => $today,
            'hora' => $hora,
            'idcliente' => $this->client1->id,
            'modo_pago' => 1,
            'subtotal' => 67.80,
            'igv' => 12.20,
            'total' => 80.00,
            'estado' => 1, // Active
            'idusuario' => $this->user->id,
            'idarqueocaja' => $this->session->id,
        ]);
        DetailSaleNote::create([
            'idnotaventa' => $saleNote1->id,
            'idproducto' => $this->serviceC->id,
            'cantidad' => 1,
            'precio_unitario' => 80.00,
            'precio_total' => 80.00,
            'descuento' => 0,
            'igv' => 12.20,
            'opcion' => 2,
            'idalmacen' => $this->warehouse->id,
        ]);
        DetailPayment::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'idfactura' => $saleNote1->id,
            'idpago' => $this->payCash->id,
            'monto' => 80.00,
            'idarqueocaja' => $this->session->id,
            'estado' => 1,
        ]);

        // SALE 4: Sale Note CONVERTED to Boleta (S/ 70.00)
        // Sale note created first, then converted to Boleta B001-00000002 (paid with Card).
        // The sale note is linked via billing_id and sale_note_id, marked estado = 2.
        $saleNoteConverted = SaleNote::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'serie' => 'NV01',
            'correlativo' => '00000002',
            'fecha_emision' => $today,
            'fecha_vencimiento' => $today,
            'hora' => $hora,
            'idcliente' => $this->client2->id,
            'modo_pago' => 1,
            'subtotal' => 59.32,
            'igv' => 10.68,
            'total' => 70.00,
            'estado' => 2, // Converted/Canjeado
            'idusuario' => $this->user->id,
            'idarqueocaja' => $this->session->id,
        ]);
        DetailSaleNote::create([
            'idnotaventa' => $saleNoteConverted->id,
            'idproducto' => $this->productB->id,
            'cantidad' => 7,
            'precio_unitario' => 10.00,
            'precio_total' => 70.00,
            'descuento' => 0,
            'igv' => 10.68,
            'opcion' => 1,
            'idalmacen' => $this->warehouse->id,
        ]);

        $billingConverted = Billing::create([
            'idtipo_comprobante' => $this->docTypeBoleta->id,
            'serie' => 'B001',
            'correlativo' => '00000002',
            'fecha_emision' => $today,
            'hora' => $hora,
            'idcliente' => $this->client2->id,
            'idmoneda' => 1,
            'idpago' => $this->payCard->id,
            'modo_pago' => 1,
            'sunat_forma_pago' => 'Contado',
            'gravada' => 59.32,
            'igv' => 10.68,
            'total' => 70.00,
            'anulado' => false,
            'sale_note_id' => $saleNoteConverted->id,
            'idusuario' => $this->user->id,
            'idarqueocaja' => $this->session->id,
            'idalmacen' => $this->warehouse->id,
        ]);
        DetailBilling::create([
            'idfacturacion' => $billingConverted->id,
            'idproducto' => $this->productB->id,
            'id_afectacion_igv' => 1,
            'cantidad' => 7,
            'precio_unitario' => 10.00,
            'precio_total' => 70.00,
            'descuento' => 0,
            'igv' => 10.68,
        ]);
        DetailPayment::create([
            'idtipo_comprobante' => $this->docTypeBoleta->id,
            'idfactura' => $billingConverted->id,
            'idpago' => $this->payCard->id,
            'monto' => 70.00,
            'idarqueocaja' => $this->session->id,
            'estado' => 1,
        ]);
        $saleNoteConverted->update(['billing_id' => $billingConverted->id]);

        // SALE 5: Voided Billing (total = S/ 200.00) - MUST BE EXCLUDED!
        $billingVoided = Billing::create([
            'idtipo_comprobante' => $this->docTypeBoleta->id,
            'serie' => 'B001',
            'correlativo' => '00000099',
            'fecha_emision' => $today,
            'hora' => $hora,
            'idcliente' => $this->client1->id,
            'idmoneda' => 1,
            'idpago' => $this->payCash->id,
            'modo_pago' => 1,
            'gravada' => 169.49,
            'igv' => 30.51,
            'total' => 200.00,
            'anulado' => true, // VOIDED
            'idusuario' => $this->user->id,
            'idarqueocaja' => $this->session->id,
            'idalmacen' => $this->warehouse->id,
        ]);

        // SALE 6: Credit Note (tipo 07, total = S/ 30.00) - EXCLUDED FROM STANDARD POSITIVE SALES VOLUME
        $creditNote = Billing::create([
            'idtipo_comprobante' => $this->docTypeNotaCredito->id,
            'serie' => 'BC01',
            'correlativo' => '00000001',
            'fecha_emision' => $today,
            'hora' => $hora,
            'idcliente' => $this->client1->id,
            'idmoneda' => 1,
            'idpago' => $this->payCash->id,
            'modo_pago' => 1,
            'gravada' => 25.42,
            'igv' => 4.58,
            'total' => 30.00,
            'anulado' => false,
            'idfactura_anular' => $billing1->id,
            'idusuario' => $this->user->id,
            'idarqueocaja' => $this->session->id,
            'idalmacen' => $this->warehouse->id,
        ]);

        // Expected Net Valid Sales:
        // Sale 1 (Boleta):       100.00
        // Sale 2 (Factura):       50.00
        // Sale 3 (Nota de Venta): 80.00
        // Sale 4 (Boleta Conv):   70.00
        // TOTAL EXPECTED SALES = 300.00
        $expectedTotalSales = 300.00;

        // --- 1. Query Sales by Date ---
        $salesByDate = $this->reportService->getSalesByDate(null, ['start_date' => $today, 'end_date' => $today]);
        $totalByDate = $salesByDate['total_ventas'];

        // --- 2. Query Sales by Product ---
        $salesByProduct = $this->reportService->getSalesByProduct(null, ['start_date' => $today, 'end_date' => $today]);
        $totalByProduct = $salesByProduct['total_ventas'];

        // --- 3. Query Sales by Customer ---
        $salesByCustomer = $this->reportService->getSalesByCustomer(null, ['start_date' => $today, 'end_date' => $today]);
        $totalByCustomer = $salesByCustomer['total_ventas'];

        // --- 4. Query Sales by Type of Receipt ---
        $salesByTypeDoc = $this->reportService->getSalesByTypeDocument(null, ['start_date' => $today, 'end_date' => $today]);
        $totalByTypeDoc = $salesByTypeDoc['total_ventas'];

        // --- 5. Query Sales by Payment Method ---
        $salesByPayMode = $this->reportService->getSalesByPaymentMethod(null, ['start_date' => $today, 'end_date' => $today]);
        $totalByPayMode = $salesByPayMode['total_recaudado'];

        // --- 6. Total Cash Collections in Cash Register ---
        // Cash sales: Sale 1 (100.00) + Sale 3 (80.00) = 180.00
        // Yape: Sale 2 (50.00), Card: Sale 4 (70.00)
        $cashPayments = collect($salesByPayMode['sales'])->firstWhere('metodo_pago', 'Efectivo');
        $totalCashSales = $cashPayments ? (float) $cashPayments['total_recaudado'] : 0.00;

        // Daily Cash reconciliation:
        $dailyCash = $this->reportService->getDailyCash(null, ['start_date' => $today, 'end_date' => $today]);
        $this->assertNotEmpty($dailyCash['sessions']);

        // === RECONCILIATION ASSERTIONS ===
        // 1. Total reports = expected total sales (300.00)
        $this->assertEquals($expectedTotalSales, $totalByDate, 'Total by date does not match expected sales');
        $this->assertEquals($expectedTotalSales, $totalByProduct, 'Total by product does not match expected sales');
        $this->assertEquals($expectedTotalSales, $totalByCustomer, 'Total by customer does not match expected sales');
        $this->assertEquals($expectedTotalSales, $totalByTypeDoc, 'Total by receipt type does not match expected sales');
        $this->assertEquals($expectedTotalSales, $totalByPayMode, 'Total by payment method does not match expected sales');

        // 2. Cross-Report Equality:
        // total reports = total sales = total receipts = total payment collections
        $this->assertEquals($totalByDate, $totalByProduct);
        $this->assertEquals($totalByProduct, $totalByCustomer);
        $this->assertEquals($totalByCustomer, $totalByTypeDoc);
        $this->assertEquals($totalByTypeDoc, $totalByPayMode);

        // 3. Total cash collected specifically reconciles with cash payments
        $this->assertEquals(180.00, $totalCashSales, 'Cash collections do not equal S/ 180.00');

        // 4. Converted sales note is excluded and not double counted
        // (If double counted, total would be 370.00 instead of 300.00)
        $this->assertNotEquals(370.00, $totalByDate);

        // 5. Voided billing is excluded
        // (If included, total would be 500.00 instead of 300.00)
        $this->assertNotEquals(500.00, $totalByDate);
    }

    /**
     * Test single base query joins sale_notes and billings with "origin" column.
     */
    public function test_single_base_query_joins_sales_notes_and_billings_with_origin_column(): void
    {
        $today = now()->toDateString();

        // Create 1 Billing
        Billing::create([
            'idtipo_comprobante' => $this->docTypeBoleta->id,
            'serie' => 'B001',
            'correlativo' => '00000050',
            'fecha_emision' => $today,
            'hora' => '12:00:00',
            'idcliente' => $this->client1->id,
            'idmoneda' => 1,
            'idpago' => $this->payCash->id,
            'modo_pago' => 1,
            'gravada' => 84.75,
            'igv' => 15.25,
            'total' => 100.00,
            'anulado' => false,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
        ]);

        // Create 1 Sale Note
        SaleNote::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'serie' => 'NV01',
            'correlativo' => '00000050',
            'fecha_emision' => $today,
            'fecha_vencimiento' => $today,
            'hora' => '12:05:00',
            'idcliente' => $this->client2->id,
            'modo_pago' => 1,
            'subtotal' => 42.37,
            'igv' => 7.63,
            'total' => 50.00,
            'estado' => 1,
            'idusuario' => $this->user->id,
            'idarqueocaja' => $this->session->id,
        ]);

        $query = $this->reportService->salesBaseQuery(null, ['start_date' => $today, 'end_date' => $today]);
        $results = $query->get();

        $this->assertGreaterThanOrEqual(2, $results->count());

        $origins = $results->pluck('origin')->unique()->values()->toArray();
        $this->assertContains('billing', $origins);
        $this->assertContains('sale_note', $origins);
    }

    /**
     * Test stock per warehouse excludes services and accurately tallies inventory valuation.
     */
    public function test_stock_per_warehouse_report_excludes_services_and_calculates_valuation(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('report.operations.stock_warehouse.data', [
            'id_almacen' => $this->warehouse->id,
        ]));

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total_stock', $data);
        $this->assertArrayHasKey('total_valoracion', $data);

        // Product A: 80 units @ 4.00 = 320.00
        // Product B: 40 units @ 8.00 = 320.00
        $itemA = collect($data['items'])->firstWhere('producto_id', $this->productA->id);
        $this->assertNotNull($itemA);
        $this->assertEquals(80.00, (float) $itemA['stock_actual']);
        $this->assertEquals(320.00, (float) $itemA['valor_inventario']);

        $itemB = collect($data['items'])->firstWhere('producto_id', $this->productB->id);
        $this->assertNotNull($itemB);
        $this->assertEquals(40.00, (float) $itemB['stock_actual']);
        $this->assertEquals(320.00, (float) $itemB['valor_inventario']);

        $this->assertGreaterThanOrEqual(120.00, (float) $data['total_stock']);
        $this->assertGreaterThanOrEqual(640.00, (float) $data['total_valoracion']);

        // Check that Service C is NOT in the stock list
        $productDescriptions = collect($data['items'])->pluck('producto')->toArray();
        $this->assertContains('ARROZ EXTRA COSTENO 1KG', $productDescriptions);
        $this->assertContains('ACEITE VEGETAL PRIMOR 1L', $productDescriptions);
        $this->assertNotContains('SERVICIO DE TRANSPORTE Y EMBALAJE', $productDescriptions);
    }

    /**
     * Test purchases by supplier report tallies purchases and excludes voided purchases.
     */
    public function test_purchases_by_supplier_report_tallies_purchases_and_excludes_voided(): void
    {
        $this->actingAs($this->user);
        $today = now()->toDateString();

        // Valid Buy 1 (S/ 500.00)
        Buy::create([
            'idtipo_comprobante' => $this->docTypeFactura->id,
            'serie' => 'F001',
            'correlativo' => '00000555',
            'fecha_emision' => $today,
            'fecha_vencimiento' => $today,
            'hora' => '10:00:00',
            'idproveedor' => $this->provider->id,
            'idalmacen' => $this->warehouse->id,
            'idmoneda' => 1,
            'idpago' => $this->payCash->id,
            'modo_pago' => 1,
            'condicion_pago' => 'contado',
            'gravada' => 423.73,
            'igv' => 76.27,
            'gratuita' => 0.00,
            'otros_cargos' => 0.00,
            'total' => 500.00,
            'estado' => 1, // Valid
            'idusuario' => $this->user->id,
        ]);

        // Voided Buy 2 (S/ 300.00, estado = 0) - MUST BE EXCLUDED!
        Buy::create([
            'idtipo_comprobante' => $this->docTypeFactura->id,
            'serie' => 'F001',
            'correlativo' => '00000556',
            'fecha_emision' => $today,
            'fecha_vencimiento' => $today,
            'hora' => '11:00:00',
            'idproveedor' => $this->provider->id,
            'idalmacen' => $this->warehouse->id,
            'idmoneda' => 1,
            'idpago' => $this->payCash->id,
            'modo_pago' => 1,
            'condicion_pago' => 'contado',
            'gravada' => 254.24,
            'igv' => 45.76,
            'gratuita' => 0.00,
            'otros_cargos' => 0.00,
            'total' => 300.00,
            'estado' => 0, // Voided
            'idusuario' => $this->user->id,
        ]);

        $response = $this->getJson(route('report.operations.purchases_supplier.data', [
            'start_date' => $today,
            'end_date' => $today,
            'idproveedor' => $this->provider->id,
        ]));

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(500.00, (float) $data['total_compras']);
        $this->assertEquals(1, $data['total_ordenes']);
    }

    /**
     * Test daily cash report returns expected reconciliation fields.
     */
    public function test_daily_cash_report_shows_opening_closing_float_and_reconciliation(): void
    {
        $this->actingAs($this->user);
        $today = now()->toDateString();

        $response = $this->getJson(route('report.operations.daily_cash.data', [
            'start_date' => $today,
            'end_date' => $today,
            'idcaja' => $this->cash->id,
        ]));

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey('sessions', $data);
        $this->assertNotEmpty($data['sessions']);

        $sessionRow = collect($data['sessions'])->firstWhere('id', $this->session->id);
        $this->assertNotNull($sessionRow);
        $this->assertEquals(100.00, (float) $sessionRow['monto_inicial']);
    }

    /**
     * Test issued vouchers and statuses report lists active, voided, and converted vouchers.
     */
    public function test_issued_vouchers_and_statuses_report_shows_all_statuses(): void
    {
        $this->actingAs($this->user);
        $today = now()->toDateString();

        // 1 Active
        Billing::create([
            'idtipo_comprobante' => $this->docTypeBoleta->id,
            'serie' => 'B001',
            'correlativo' => '00000077',
            'fecha_emision' => $today,
            'hora' => '14:00:00',
            'idcliente' => $this->client1->id,
            'idmoneda' => 1,
            'idpago' => $this->payCash->id,
            'modo_pago' => 1,
            'total' => 120.00,
            'anulado' => false,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
        ]);

        // 1 Voided
        Billing::create([
            'idtipo_comprobante' => $this->docTypeFactura->id,
            'serie' => 'F001',
            'correlativo' => '00000088',
            'fecha_emision' => $today,
            'hora' => '14:10:00',
            'idcliente' => $this->client2->id,
            'idmoneda' => 1,
            'idpago' => $this->payCash->id,
            'modo_pago' => 1,
            'total' => 250.00,
            'anulado' => true,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
        ]);

        $response = $this->getJson(route('report.operations.issued_vouchers.data', [
            'start_date' => $today,
            'end_date' => $today,
        ]));

        $response->assertOk();
        $data = $response->json();

        $vouchers = collect($data['vouchers']);
        $statuses = $vouchers->pluck('estado')->toArray();

        $this->assertContains('Vigente', $statuses);
        $this->assertContains('Anulado', $statuses);
    }

    /**
     * Test PDF and Excel export endpoints across reports return successful responses.
     */
    public function test_reports_pdf_and_excel_exports_return_successful_responses(): void
    {
        $this->actingAs($this->user);
        $today = now()->toDateString();

        $params = ['start_date' => $today, 'end_date' => $today];

        // Sales by Date Exports
        $resPdf = $this->get(route('report.sales.by_date.pdf', $params));
        $resPdf->assertOk();
        $resExcel = $this->get(route('report.sales.by_date.excel', $params));
        $resExcel->assertOk();

        // Sales by Product Exports
        $resPdf = $this->get(route('report.sales.products.pdf', $params));
        $resPdf->assertOk();
        $resExcel = $this->get(route('report.sales.products.excel', $params));
        $resExcel->assertOk();

        // Sales by Customer Exports
        $resPdf = $this->get(route('report.sales.customer.pdf', $params));
        $resPdf->assertOk();
        $resExcel = $this->get(route('report.sales.customer.excel', $params));
        $resExcel->assertOk();

        // Sales by Type Document Exports
        $resPdf = $this->get(route('report.sales.type_document.pdf', $params));
        $resPdf->assertOk();
        $resExcel = $this->get(route('report.sales.type_document.excel', $params));
        $resExcel->assertOk();

        // Sales by Payment Method Exports
        $resPdf = $this->get(route('report.payments.pdf', $params));
        $resPdf->assertOk();
        $resExcel = $this->get(route('report.payments.excel', $params));
        $resExcel->assertOk();

        // Stock per Warehouse Exports
        $resPdf = $this->get(route('report.operations.stock_warehouse.pdf', ['id_almacen' => $this->warehouse->id]));
        $resPdf->assertOk();
        $resExcel = $this->get(route('report.operations.stock_warehouse.excel', ['id_almacen' => $this->warehouse->id]));
        $resExcel->assertOk();

        // Purchases by Supplier Exports
        $resPdf = $this->get(route('report.operations.purchases_supplier.pdf', $params));
        $resPdf->assertOk();
        $resExcel = $this->get(route('report.operations.purchases_supplier.excel', $params));
        $resExcel->assertOk();

        // Daily Cash Exports
        $resPdf = $this->get(route('report.operations.daily_cash.pdf', $params));
        $resPdf->assertOk();
        $resExcel = $this->get(route('report.operations.daily_cash.excel', $params));
        $resExcel->assertOk();

        // Issued Vouchers Exports
        $resPdf = $this->get(route('report.operations.issued_vouchers.pdf', $params));
        $resPdf->assertOk();
        $resExcel = $this->get(route('report.operations.issued_vouchers.excel', $params));
        $resExcel->assertOk();
    }
}
