<?php

namespace Tests\Feature;

use App\Events\SaleCompleted;
use App\Models\ArchingCash;
use App\Models\Billing;
use App\Models\Business;
use App\Models\Cash;
use App\Models\Client;
use App\Models\DetailBilling;
use App\Models\DetailPayment;
use App\Models\DetailSaleNote;
use App\Models\IdentityDocumentType;
use App\Models\IgvTypeAffection;
use App\Models\PayMode;
use App\Models\Product;
use App\Models\SaleNote;
use App\Models\Serie;
use App\Models\StockProduct;
use App\Models\TypeDocument;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Warehouse $warehousePrincipal;
    protected Warehouse $warehouseSecundario;
    protected Cash $cash;
    protected ArchingCash $archingCash;
    protected TypeDocument $docTypeFactura;
    protected TypeDocument $docTypeBoleta;
    protected TypeDocument $docTypeNotaVenta;
    protected Serie $serieFactura;
    protected Serie $serieBoleta;
    protected Serie $serieNotaVenta;
    protected PayMode $payCash;
    protected PayMode $payYape;
    protected PayMode $payCard;
    protected Client $anonymousClient;
    protected Client $rucClient;
    protected Client $dniClient;
    protected Product $taxableProduct;
    protected Product $exemptProduct;
    protected Product $inafectoProduct;
    protected Product $gratuitoProduct;
    protected Product $serviceProduct;
    protected StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);

        // 1. Business
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

        // 2. Warehouses
        $this->warehousePrincipal = Warehouse::find(1) ?? Warehouse::create([
            'descripcion' => 'ALMACEN PRINCIPAL FVC',
            'direccion' => 'CALLE PRINCIPAL 100',
        ]);

        $this->warehouseSecundario = Warehouse::where('id', '!=', $this->warehousePrincipal->id)->first() ?? Warehouse::create([
            'descripcion' => 'ALMACEN SECUNDARIO FVC',
            'direccion' => 'CALLE SECUNDARIA 200',
        ]);

        // 3. Cash
        $this->cash = Cash::find(1) ?? Cash::create([
            'descripcion' => 'CAJA PRINCIPAL 01',
            'idalmacen' => $this->warehousePrincipal->id,
            'estado' => 1,
        ]);

        // 4. User
        $this->adminUser = User::where('user', 'admin')->first() ?? User::factory()->create([
            'user' => 'admin',
            'estado' => 1,
            'idcaja' => $this->cash->id,
            'idalmacen' => $this->warehousePrincipal->id,
        ]);
        $this->adminUser->update([
            'idcaja' => $this->cash->id,
            'idalmacen' => $this->warehousePrincipal->id,
        ]);

        if (! $this->adminUser->hasRole('SUPERADMIN')) {
            $role = Role::firstOrCreate(['name' => 'SUPERADMIN']);
            $this->adminUser->assignRole($role);
        }

        $perm = Permission::firstOrCreate(['name' => 'admin.pos']);
        $this->adminUser->givePermissionTo($perm);

        // 5. Open Cash Register Session (ArchingCash)
        ArchingCash::where('idcaja', $this->cash->id)
            ->where('idusuario', $this->adminUser->id)
            ->where('estado', 1)
            ->update(['estado' => 0]);

        $this->archingCash = ArchingCash::create([
            'idcaja' => $this->cash->id,
            'idusuario' => $this->adminUser->id,
            'idalmacen' => $this->warehousePrincipal->id,
            'fecha_inicio' => now()->toDateString(),
            'monto_inicial' => 100.00,
            'monto_final' => 0.00,
            'total_ventas' => 0.00,
            'estado' => 1,
        ]);

        // 6. Document Types
        $this->docTypeFactura = TypeDocument::firstOrCreate(['codigo' => '01'], ['descripcion' => 'FACTURA ELECTRONICA', 'estado' => 1]);
        $this->docTypeBoleta = TypeDocument::firstOrCreate(['codigo' => '03'], ['descripcion' => 'BOLETA DE VENTA ELECTRONICA', 'estado' => 1]);
        $this->docTypeNotaVenta = TypeDocument::firstOrCreate(['codigo' => '02'], ['descripcion' => 'NOTA DE VENTA', 'estado' => 1]);

        // 7. Series with warehouse association
        $this->serieFactura = Serie::firstOrCreate(
            ['idtipo_documento' => $this->docTypeFactura->id, 'serie' => 'F001'],
            ['correlativo' => '00000001', 'idcaja' => $this->cash->id, 'idalmacen' => $this->warehousePrincipal->id, 'estado' => 1]
        );
        $this->serieFactura->update(['idalmacen' => $this->warehousePrincipal->id, 'idcaja' => $this->cash->id, 'estado' => 1]);

        $this->serieBoleta = Serie::firstOrCreate(
            ['idtipo_documento' => $this->docTypeBoleta->id, 'serie' => 'B001'],
            ['correlativo' => '00000001', 'idcaja' => $this->cash->id, 'idalmacen' => $this->warehousePrincipal->id, 'estado' => 1]
        );
        $this->serieBoleta->update(['idalmacen' => $this->warehousePrincipal->id, 'idcaja' => $this->cash->id, 'estado' => 1]);

        $this->serieNotaVenta = Serie::firstOrCreate(
            ['idtipo_documento' => $this->docTypeNotaVenta->id, 'serie' => 'NV01'],
            ['correlativo' => '00000001', 'idcaja' => $this->cash->id, 'idalmacen' => $this->warehousePrincipal->id, 'estado' => 1]
        );
        $this->serieNotaVenta->update(['idalmacen' => $this->warehousePrincipal->id, 'idcaja' => $this->cash->id, 'estado' => 1]);

        // 8. Payment Modes
        $this->payCash = PayMode::where('descripcion', 'Efectivo')->first() ?? PayMode::create(['descripcion' => 'Efectivo']);
        $this->payYape = PayMode::where('descripcion', 'Yape')->first() ?? PayMode::create(['descripcion' => 'Yape']);
        $this->payCard = PayMode::where('descripcion', 'Tarjeta de crédito')->first() ?? PayMode::create(['descripcion' => 'Tarjeta de crédito']);

        // 9. Identity Document Types & Clients
        $docDni = IdentityDocumentType::where('codigo', '1')->first() ?? IdentityDocumentType::create(['codigo' => '1', 'descripcion' => 'DNI', 'estado' => 1]);
        $docRuc = IdentityDocumentType::where('codigo', '6')->first() ?? IdentityDocumentType::create(['codigo' => '6', 'descripcion' => 'RUC', 'estado' => 1]);

        $this->anonymousClient = Client::firstOrCreate(
            ['nro_documento' => '00000000'],
            [
                'iddoc' => $docDni->id,
                'nombres' => 'CLIENTES VARIOS',
                'direccion' => '-',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
                'telefono' => '999999999',
            ]
        );

        $this->rucClient = Client::firstOrCreate(
            ['nro_documento' => '20556677889'],
            [
                'iddoc' => $docRuc->id,
                'nombres' => 'COMERCIALIZADORA DEL NORTE SAC',
                'direccion' => 'AV. PERU 456',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
                'telefono' => '988888888',
            ]
        );

        $this->dniClient = Client::firstOrCreate(
            ['nro_documento' => '47852369'],
            [
                'iddoc' => $docDni->id,
                'nombres' => 'JUAN CARLOS PEREZ GOMEZ',
                'direccion' => 'JR. LIMA 789',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
                'telefono' => '977777777',
            ]
        );

        // 10. IGV Type Affections
        $igv10 = IgvTypeAffection::firstOrCreate(['codigo' => '10'], ['descripcion' => 'Gravado - Operación Onerosa', 'tipo' => 'GRAVADA']);
        $igv20 = IgvTypeAffection::firstOrCreate(['codigo' => '20'], ['descripcion' => 'Exonerado - Operación Onerosa', 'tipo' => 'EXONERADA']);
        $igv30 = IgvTypeAffection::firstOrCreate(['codigo' => '30'], ['descripcion' => 'Inafecto - Operación Onerosa', 'tipo' => 'INAFECTA']);
        $igv11 = IgvTypeAffection::firstOrCreate(['codigo' => '11'], ['descripcion' => 'Gravado - Retiro por premio', 'tipo' => 'GRATUITA']);

        // 11. Products
        $this->taxableProduct = Product::create([
            'codigo_interno' => 'TAX-' . mt_rand(1000, 9999),
            'codigo_barras' => '775' . mt_rand(100000000, 999999999),
            'descripcion' => 'ABONO ORGANICO GRAVADO 10',
            'idunidad' => 1,
            'idcategoria' => 1,
            'igv' => 18,
            'idcodigo_igv' => $igv10->id,
            'precio_compra' => 50.00,
            'precio_venta' => 118.00,
            'opcion' => 1,
            'stock_actual' => 50,
        ]);
        $this->stockService->setStock($this->warehousePrincipal->id, $this->taxableProduct->id, 50, 50.00, 118.00);

        $this->exemptProduct = Product::create([
            'codigo_interno' => 'EXO-' . mt_rand(1000, 9999),
            'codigo_barras' => '775' . mt_rand(100000000, 999999999),
            'descripcion' => 'SEMILLA CERTIFICADA EXONERADA 20',
            'idunidad' => 1,
            'idcategoria' => 1,
            'igv' => 0,
            'idcodigo_igv' => $igv20->id,
            'precio_compra' => 30.00,
            'precio_venta' => 50.00,
            'opcion' => 1,
            'stock_actual' => 50,
        ]);
        $this->stockService->setStock($this->warehousePrincipal->id, $this->exemptProduct->id, 50, 30.00, 50.00);

        $this->inafectoProduct = Product::create([
            'codigo_interno' => 'INA-' . mt_rand(1000, 9999),
            'codigo_barras' => '775' . mt_rand(100000000, 999999999),
            'descripcion' => 'AGUA PURA INAFECTA 30',
            'idunidad' => 1,
            'idcategoria' => 1,
            'igv' => 0,
            'idcodigo_igv' => $igv30->id,
            'precio_compra' => 15.00,
            'precio_venta' => 30.00,
            'opcion' => 1,
            'stock_actual' => 50,
        ]);
        $this->stockService->setStock($this->warehousePrincipal->id, $this->inafectoProduct->id, 50, 15.00, 30.00);

        $this->gratuitoProduct = Product::create([
            'codigo_interno' => 'GRA-' . mt_rand(1000, 9999),
            'codigo_barras' => '775' . mt_rand(100000000, 999999999),
            'descripcion' => 'MUESTRA GRATUITA BONIFICACION 11',
            'idunidad' => 1,
            'idcategoria' => 1,
            'igv' => 0,
            'idcodigo_igv' => $igv11->id,
            'precio_compra' => 20.00,
            'precio_venta' => 40.00,
            'opcion' => 1,
            'stock_actual' => 50,
        ]);
        $this->stockService->setStock($this->warehousePrincipal->id, $this->gratuitoProduct->id, 50, 20.00, 40.00);

        $this->serviceProduct = Product::create([
            'codigo_interno' => 'SRV-' . mt_rand(1000, 9999),
            'codigo_barras' => '775' . mt_rand(100000000, 999999999),
            'descripcion' => 'SERVICIO DE ASESORIA TECNICA AGRICOLA',
            'idunidad' => 1,
            'idcategoria' => 1,
            'igv' => 18,
            'idcodigo_igv' => $igv10->id,
            'precio_compra' => 0.00,
            'precio_venta' => 80.00,
            'opcion' => 2, // SERVICE
            'stock_actual' => 0,
        ]);
    }

    protected function formatProductForCart(Product $product, float|int $quantity, ?float $overridePrice = null, ?int $warehouseId = null): array
    {
        $price = $overridePrice !== null ? $overridePrice : (float) $product->precio_venta;
        $wh = $warehouseId ?? $this->warehousePrincipal->id;

        return [
            'id' => $product->id,
            'descripcion' => $product->descripcion,
            'idunidad' => $product->idunidad,
            'unidad' => 'NIU',
            'igv' => (int) $product->igv,
            'idcodigo_igv' => $product->idcodigo_igv,
            'precio_compra' => (float) $product->precio_compra,
            'precio_venta' => number_format($price, 2, '.', ''),
            'stock' => $product->isProduct() ? 50 : null,
            'opcion' => (int) $product->opcion,
            'cantidad' => $quantity,
            'idalmacen' => $product->isProduct() ? $wh : null,
        ];
    }

    public function test_sale_concludes_as_sales_note_in_sale_notes_without_tax_rules_or_sunat(): void
    {
        $cartProduct = $this->formatProductForCart($this->taxableProduct, 2, 118.00);
        $total = 236.00;

        $initialStock = (int) StockProduct::where('idalmacen', $this->warehousePrincipal->id)
            ->where('idproducto', $this->taxableProduct->id)
            ->value('stock_actual');

        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$cartProduct]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->anonymousClient->id,
                'document_type_id' => $this->docTypeNotaVenta->id,
                'serie_id' => $this->serieNotaVenta->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => $total],
                ],
            ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['status']);
        $this->assertEquals('sale_note', $data['document_kind']);

        // Assert recorded in sale_notes and NOT in billings
        $this->assertDatabaseHas('sale_notes', [
            'id' => $data['id'],
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'serie' => $this->serieNotaVenta->serie,
            'idcliente' => $this->anonymousClient->id,
            'total' => $total,
        ]);
        $this->assertDatabaseMissing('billings', [
            'serie' => $this->serieNotaVenta->serie,
            'idcliente' => $this->anonymousClient->id,
        ]);

        // Assert detail_sale_notes
        $this->assertDatabaseHas('detail_sale_notes', [
            'idnotaventa' => $data['id'],
            'idproducto' => $this->taxableProduct->id,
            'cantidad' => 2,
        ]);

        // Assert stock deduction
        $finalStock = (int) StockProduct::where('idalmacen', $this->warehousePrincipal->id)
            ->where('idproducto', $this->taxableProduct->id)
            ->value('stock_actual');
        $this->assertEquals($initialStock - 2, $finalStock);
    }

    public function test_sale_concludes_as_boleta_in_billings(): void
    {
        $cartProduct = $this->formatProductForCart($this->taxableProduct, 1, 118.00);
        $total = 118.00;

        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$cartProduct]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->dniClient->id,
                'document_type_id' => $this->docTypeBoleta->id,
                'serie_id' => $this->serieBoleta->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => $total],
                ],
            ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['status']);
        $this->assertEquals('billing', $data['document_kind']);

        // Assert in billings
        $this->assertDatabaseHas('billings', [
            'id' => $data['id'],
            'idtipo_comprobante' => $this->docTypeBoleta->id,
            'serie' => $this->serieBoleta->serie,
            'idcliente' => $this->dniClient->id,
            'total' => $total,
            'gravada' => 100.00,
            'igv' => 18.00,
        ]);
        $this->assertDatabaseMissing('sale_notes', [
            'serie' => $this->serieBoleta->serie,
            'idcliente' => $this->dniClient->id,
        ]);

        // Assert in detail_billings
        $this->assertDatabaseHas('detail_billings', [
            'idfacturacion' => $data['id'],
            'idproducto' => $this->taxableProduct->id,
            'cantidad' => 1,
            'precio_total' => $total,
        ]);
    }

    public function test_sale_concludes_as_factura_in_billings(): void
    {
        $cartProduct = $this->formatProductForCart($this->taxableProduct, 1, 118.00);
        $total = 118.00;

        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$cartProduct]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->rucClient->id,
                'document_type_id' => $this->docTypeFactura->id,
                'serie_id' => $this->serieFactura->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => $total],
                ],
            ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['status']);
        $this->assertEquals('billing', $data['document_kind']);

        $this->assertDatabaseHas('billings', [
            'id' => $data['id'],
            'idtipo_comprobante' => $this->docTypeFactura->id,
            'serie' => $this->serieFactura->serie,
            'idcliente' => $this->rucClient->id,
            'total' => $total,
        ]);
    }

    public function test_factura_requires_valid_customer_ruc(): void
    {
        $cartProduct = $this->formatProductForCart($this->taxableProduct, 1, 118.00);

        // Attempt factura with DNI client (8 digits, not RUC)
        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$cartProduct]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->dniClient->id,
                'document_type_id' => $this->docTypeFactura->id,
                'serie_id' => $this->serieFactura->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => 118.00],
                ],
            ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('status'));
        $this->assertStringContainsString('RUC valido (11 dígitos)', $response->json('msg'));

        // Attempt factura with anonymous client
        $responseAnon = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$cartProduct]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->anonymousClient->id,
                'document_type_id' => $this->docTypeFactura->id,
                'serie_id' => $this->serieFactura->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => 118.00],
                ],
            ]);

        $responseAnon->assertStatus(422);
        $this->assertFalse($responseAnon->json('status'));
    }

    public function test_boleta_allows_general_customer_within_configurable_limit(): void
    {
        config(['inventory.pos_boleta_anonymous_limit' => 700.00]);

        // S/ 118.00 is <= 700.00
        $cartProduct = $this->formatProductForCart($this->taxableProduct, 1, 118.00);

        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$cartProduct]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->anonymousClient->id,
                'document_type_id' => $this->docTypeBoleta->id,
                'serie_id' => $this->serieBoleta->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => 118.00],
                ],
            ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('status'));
    }

    public function test_boleta_rejects_general_customer_exceeding_configurable_limit(): void
    {
        // Set regulatory limit to 500.00 to verify it is NOT hardcoded
        config(['inventory.pos_boleta_anonymous_limit' => 500.00]);

        // 5 units x 118.00 = 590.00 > 500.00 limit
        $cartProduct = $this->formatProductForCart($this->taxableProduct, 5, 118.00);

        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$cartProduct]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->anonymousClient->id,
                'document_type_id' => $this->docTypeBoleta->id,
                'serie_id' => $this->serieBoleta->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => 590.00],
                ],
            ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('status'));
        $this->assertStringContainsString('500.00', $response->json('msg'));
        $this->assertStringContainsString('identificar al cliente', $response->json('msg'));
    }

    public function test_boleta_allows_identified_customer_exceeding_limit(): void
    {
        config(['inventory.pos_boleta_anonymous_limit' => 700.00]);

        // 8 units x 118.00 = 944.00 > 700.00, but client is identified with DNI
        $cartProduct = $this->formatProductForCart($this->taxableProduct, 8, 118.00);

        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$cartProduct]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->dniClient->id,
                'document_type_id' => $this->docTypeBoleta->id,
                'serie_id' => $this->serieBoleta->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => 944.00],
                ],
            ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('status'));
    }

    public function test_igv_and_totals_calculation_for_taxable_exempt_inafecta_and_gratuita(): void
    {
        // 1 taxable (118.00 => gravada 100.00, igv 18.00)
        $item1 = $this->formatProductForCart($this->taxableProduct, 1, 118.00);
        // 1 exempt (50.00 => exonerada 50.00, igv 0.00)
        $item2 = $this->formatProductForCart($this->exemptProduct, 1, 50.00);
        // 1 inafecto (30.00 => inafecta 30.00, igv 0.00)
        $item3 = $this->formatProductForCart($this->inafectoProduct, 1, 30.00);
        // 1 gratuito (40.00 => gratuita 40.00, igv 0.00, to pay 0.00)
        $item4 = $this->formatProductForCart($this->gratuitoProduct, 1, 40.00);

        // Expected total payable: 118 + 50 + 30 + 0 = 198.00
        $expectedTotal = 198.00;

        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$item1, $item2, $item3, $item4]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->dniClient->id,
                'document_type_id' => $this->docTypeBoleta->id,
                'serie_id' => $this->serieBoleta->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => $expectedTotal],
                ],
            ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['status']);

        $billing = Billing::findOrFail($data['id']);
        $this->assertEquals(100.00, (float) $billing->gravada);
        $this->assertEquals(50.00, (float) $billing->exonerada);
        $this->assertEquals(30.00, (float) $billing->inafecta);
        $this->assertEquals(40.00, (float) $billing->gratuita);
        $this->assertEquals(18.00, (float) $billing->igv);
        $this->assertEquals($expectedTotal, (float) $billing->total);

        // Acceptance check: Total = sum of items + IGV, identical to tax catalog
        $expectedSubtotal = (float) $billing->gravada + (float) $billing->exonerada + (float) $billing->inafecta;
        $this->assertEquals($expectedTotal, round($expectedSubtotal + (float) $billing->igv, 2));

        // Check DetailBilling rows have their respective tax affection IDs
        $detailGratuita = DetailBilling::where('idfacturacion', $billing->id)
            ->where('idproducto', $this->gratuitoProduct->id)
            ->first();
        $this->assertNotNull($detailGratuita);
        $this->assertEquals(0.00, (float) $detailGratuita->precio_total);
    }

    public function test_multiple_payment_methods_recorded_and_linked_to_active_cash_register(): void
    {
        $cartProduct = $this->formatProductForCart($this->taxableProduct, 1, 118.00);
        $total = 118.00;

        // Pay 50.00 Cash and 68.00 Yape
        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$cartProduct]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->dniClient->id,
                'document_type_id' => $this->docTypeBoleta->id,
                'serie_id' => $this->serieBoleta->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => 50.00],
                    ['method_id' => $this->payYape->id, 'amount' => 68.00],
                ],
            ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['status']);

        // Check DetailPayment has both methods with active cash register
        $detailPayments = DetailPayment::where('idfactura', $data['id'])
            ->where('idtipo_comprobante', $this->docTypeBoleta->id)
            ->get();

        $this->assertCount(2, $detailPayments);

        $cashPayment = $detailPayments->firstWhere('idpago', $this->payCash->id);
        $this->assertNotNull($cashPayment);
        $this->assertEquals(50.00, (float) $cashPayment->monto);
        $this->assertEquals($this->archingCash->id, $cashPayment->idarqueocaja);

        $yapePayment = $detailPayments->firstWhere('idpago', $this->payYape->id);
        $this->assertNotNull($yapePayment);
        $this->assertEquals(68.00, (float) $yapePayment->monto);
        $this->assertEquals($this->archingCash->id, $yapePayment->idarqueocaja);
    }

    public function test_stock_deduction_only_affects_products_via_stock_service_and_ignores_services(): void
    {
        $physicalItem = $this->formatProductForCart($this->taxableProduct, 3, 118.00);
        $serviceItem = $this->formatProductForCart($this->serviceProduct, 2, 80.00);
        $total = (3 * 118.00) + (2 * 80.00); // 354 + 160 = 514.00

        $initialPhysicalStock = (int) StockProduct::where('idalmacen', $this->warehousePrincipal->id)
            ->where('idproducto', $this->taxableProduct->id)
            ->value('stock_actual');

        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$physicalItem, $serviceItem]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->dniClient->id,
                'document_type_id' => $this->docTypeBoleta->id,
                'serie_id' => $this->serieBoleta->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => $total],
                ],
            ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('status'));

        // Physical product stock decremented by 3
        $finalPhysicalStock = (int) StockProduct::where('idalmacen', $this->warehousePrincipal->id)
            ->where('idproducto', $this->taxableProduct->id)
            ->value('stock_actual');
        $this->assertEquals($initialPhysicalStock - 3, $finalPhysicalStock);

        // Service product has NO stock records
        $serviceStockCount = StockProduct::where('idproducto', $this->serviceProduct->id)->count();
        $this->assertEquals(0, $serviceStockCount);
    }

    public function test_sale_completed_domain_event_is_dispatched(): void
    {
        Event::fake([SaleCompleted::class]);

        $cartProduct = $this->formatProductForCart($this->taxableProduct, 1, 118.00);

        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$cartProduct]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->dniClient->id,
                'document_type_id' => $this->docTypeBoleta->id,
                'serie_id' => $this->serieBoleta->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => 118.00],
                ],
            ]);

        $response->assertStatus(200);

        Event::assertDispatched(SaleCompleted::class, function (SaleCompleted $event) {
            return $event->documentKind === 'billing'
                && $event->sale instanceof Billing
                && $event->warehouseId === $this->warehousePrincipal->id
                && $event->archingCashId === $this->archingCash->id
                && count($event->paymentBreakdown) > 0;
        });
    }

    public function test_series_selection_based_on_document_type_and_warehouse(): void
    {
        // Create an alternative series for warehouseSecundario
        $serieSecundaria = Serie::create([
            'idtipo_documento' => $this->docTypeBoleta->id,
            'serie' => 'B002',
            'correlativo' => '00000001',
            'idcaja' => $this->cash->id,
            'idalmacen' => $this->warehouseSecundario->id,
            'estado' => 1,
        ]);

        $cartProduct = $this->formatProductForCart($this->taxableProduct, 1, 118.00);

        // Explicitly choose $serieSecundaria via serie_id
        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$cartProduct]]])
            ->postJson(route('pos.save_sale'), [
                'client_id' => $this->dniClient->id,
                'document_type_id' => $this->docTypeBoleta->id,
                'serie_id' => $serieSecundaria->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => 118.00],
                ],
            ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['status']);

        $this->assertDatabaseHas('billings', [
            'id' => $data['id'],
            'serie' => 'B002',
            'correlativo' => '00000001',
        ]);

        // Correlativo advanced to 00000002
        $this->assertEquals('00000002', $serieSecundaria->fresh()->correlativo);
    }

    public function test_get_series_endpoint_returns_series_filtered_by_document_type_and_warehouse(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('pos.get_series'), [
                'document_type_id' => $this->docTypeBoleta->id,
                'warehouse_id' => $this->warehousePrincipal->id,
            ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('status'));
        $series = collect($response->json('series'));
        $this->assertTrue($series->contains('serie', $this->serieBoleta->serie));
    }

    public function test_open_modal_endpoint_returns_tax_breakdown_and_series(): void
    {
        $cartProduct = $this->formatProductForCart($this->taxableProduct, 2, 118.00);

        $response = $this->actingAs($this->adminUser)
            ->withSession(['pos' => ['products' => [$cartProduct]]])
            ->postJson(route('pos.open_modal_confirm'));

        $response->assertStatus(200);
        $this->assertTrue($response->json('status'));
        $this->assertArrayHasKey('gravada', $response->json());
        $this->assertArrayHasKey('exonerada', $response->json());
        $this->assertArrayHasKey('inafecta', $response->json());
        $this->assertArrayHasKey('gratuita', $response->json());
        $this->assertArrayHasKey('series', $response->json());
        $this->assertArrayHasKey('boleta_anonymous_limit', $response->json());
        $this->assertEquals(700.00, (float) $response->json('boleta_anonymous_limit'));
    }
}
