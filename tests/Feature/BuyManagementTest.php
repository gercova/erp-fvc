<?php

namespace Tests\Feature;

use App\Models\AccountPayable;
use App\Models\Buy;
use App\Models\DetailBuy;
use App\Models\IdentityDocumentType;
use App\Models\PayMode;
use App\Models\Product;
use App\Models\Provider;
use App\Models\StockProduct;
use App\Models\TypeDocument;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BuyManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Warehouse $warehousePrincipal;
    protected Warehouse $warehouseSecundario;
    protected Provider $formalProvider;
    protected TypeDocument $invoiceType;
    protected PayMode $cashPayMode;
    protected Product $physicalProduct;
    protected Product $serviceProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('user', 'admin')->first() ?? User::factory()->create([
            'user' => 'admin',
            'estado' => 1,
            'idcaja' => 1,
            'idalmacen' => 1,
        ]);

        if (! $this->adminUser->hasRole('SUPERADMIN')) {
            $role = Role::firstOrCreate(['name' => 'SUPERADMIN']);
            $this->adminUser->assignRole($role);
        }

        $permission = Permission::firstOrCreate(['name' => 'admin.buys']);
        $this->adminUser->givePermissionTo($permission);

        $this->warehousePrincipal = Warehouse::find(1) ?? Warehouse::create([
            'descripcion' => 'ALMACEN PRINCIPAL FVC',
            'direccion' => 'CALLE PRINCIPAL 123',
        ]);

        $this->warehouseSecundario = Warehouse::where('id', '!=', $this->warehousePrincipal->id)->first() ?? Warehouse::create([
            'descripcion' => 'ALMACEN SECUNDARIO FVC',
            'direccion' => 'CALLE SECUNDARIA 456',
        ]);

        $docTypeRuc = IdentityDocumentType::where('codigo', '6')->firstOrFail();
        $uniqueRuc = '20' . str_pad((string) mt_rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        $this->formalProvider = Provider::create([
            'iddoc' => $docTypeRuc->id,
            'nro_documento' => $uniqueRuc,
            'nombres' => 'DISTRIBUIDORA AGRICOLA CENTRAL SAC',
            'direccion' => 'AV. ARGENTINA 1050',
            'idubigeo' => '150101',
            'telefono' => '987654321',
            'correo' => 'ventas@agricolacentral.com',
            'estado' => 1,
        ]);

        $this->invoiceType = TypeDocument::where('codigo', '01')->first() ?? TypeDocument::firstOrCreate(
            ['codigo' => '01'],
            ['descripcion' => 'FACTURA', 'estado' => 1]
        );

        $this->cashPayMode = PayMode::first() ?? PayMode::create([
            'descripcion' => 'Efectivo',
        ]);

        $this->physicalProduct = Product::create([
            'codigo_interno' => 'FERT-' . mt_rand(1000, 9999),
            'codigo_barras' => '775' . mt_rand(100000000, 999999999),
            'descripcion' => 'FERTILIZANTE FOLIAR NITRO PLUS',
            'idunidad' => 1,
            'idcategoria' => 1,
            'igv' => 18,
            'idcodigo_igv' => 1,
            'precio_compra' => 0.00,
            'precio_venta' => 25.00,
            'opcion' => 1, // Physical Product
            'stock_actual' => 0,
        ]);

        $this->serviceProduct = Product::create([
            'codigo_interno' => 'SERV-' . mt_rand(1000, 9999),
            'codigo_barras' => 'SRV' . mt_rand(100000, 999999),
            'descripcion' => 'SERVICIO DE TRANSPORTE Y FLETE',
            'idunidad' => 2,
            'idcategoria' => 1,
            'igv' => 18,
            'idcodigo_igv' => 1,
            'precio_compra' => 0.00,
            'precio_venta' => 100.00,
            'opcion' => 2, // Service
            'stock_actual' => null,
        ]);
    }

    public function test_destination_warehouse_is_selected_and_respects_ensure_warehouse_selection(): void
    {
        // 1. Assign user strictly to warehousePrincipal
        $this->adminUser->warehouses()->sync([$this->warehousePrincipal->id]);

        // Visit create page
        $createResponse = $this->actingAs($this->adminUser)
            ->withSession(['selected_warehouse_id' => $this->warehousePrincipal->id])
            ->get(route('admin.create_buy'));

        $createResponse->assertOk();
        $createResponse->assertViewHas('default_warehouse_id', $this->warehousePrincipal->id);

        // 2. Attempt to save a purchase targeting an unauthorized warehouse (warehouseSecundario)
        $unauthorizedPayload = [
            'idtipo_comprobante' => $this->invoiceType->id,
            'serie' => 'F001',
            'correlativo' => '00001001',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'dni_ruc' => $this->formalProvider->id,
            'idalmacen' => $this->warehouseSecundario->id,
            'modo_pago' => $this->cashPayMode->id,
            'condicion_pago' => 'Contado',
        ];

        $cart = [
            'products' => [
                [
                    'id' => $this->physicalProduct->id,
                    'cart_key' => $this->physicalProduct->id . '-' . $this->warehouseSecundario->id,
                    'descripcion' => $this->physicalProduct->descripcion,
                    'idunidad' => 1,
                    'unidad' => 'NIU',
                    'idcodigo_igv' => 1,
                    'codigo_igv' => 10,
                    'igv' => 18,
                    'precio_compra' => 15.00,
                    'impuesto' => 2.70,
                    'idalmacen' => $this->warehouseSecundario->id,
                    'cantidad' => 5,
                    'is_service' => false,
                ],
            ],
            'igv' => 13.50,
            'subtotal' => 75.00,
            'total' => 88.50,
        ];

        $rejectResponse = $this->actingAs($this->adminUser)
            ->withSession(['buy' => $cart])
            ->postJson(route('admin.save_buy'), $unauthorizedPayload);

        $rejectResponse->assertStatus(422);
        $rejectResponse->assertJson([
            'status' => false,
            'msg' => 'No tiene autorización para registrar compras en el almacén seleccionado.',
        ]);

        // 3. Purchase targeting the authorized warehouse succeeds and persists idalmacen
        $authorizedPayload = array_merge($unauthorizedPayload, [
            'correlativo' => '00001002',
            'idalmacen' => $this->warehousePrincipal->id,
        ]);

        $cart['products'][0]['idalmacen'] = $this->warehousePrincipal->id;
        $cart['products'][0]['cart_key'] = $this->physicalProduct->id . '-' . $this->warehousePrincipal->id;

        $successResponse = $this->actingAs($this->adminUser)
            ->withSession(['buy' => $cart])
            ->postJson(route('admin.save_buy'), $authorizedPayload);

        $successResponse->assertOk();
        $this->assertDatabaseHas('buys', [
            'idproveedor' => $this->formalProvider->id,
            'serie' => 'F001',
            'correlativo' => '00001002',
            'idalmacen' => $this->warehousePrincipal->id,
        ]);
    }

    public function test_product_purchase_increases_stock_in_correct_warehouse_while_service_does_not_affect_stock(): void
    {
        $this->adminUser->warehouses()->detach(); // Full access

        // Ensure physical product starts with 0 stock in warehousePrincipal
        StockProduct::where('idproducto', $this->physicalProduct->id)
            ->where('idalmacen', $this->warehousePrincipal->id)
            ->delete();

        // Ensure service product has no stock records anywhere
        StockProduct::where('idproducto', $this->serviceProduct->id)->delete();

        $cart = [
            'products' => [
                [
                    'id' => $this->physicalProduct->id,
                    'cart_key' => $this->physicalProduct->id . '-' . $this->warehousePrincipal->id,
                    'descripcion' => $this->physicalProduct->descripcion,
                    'idunidad' => 1,
                    'unidad' => 'NIU',
                    'idcodigo_igv' => 1,
                    'codigo_igv' => 10,
                    'igv' => 18,
                    'precio_compra' => 20.00,
                    'impuesto' => 3.60,
                    'idalmacen' => $this->warehousePrincipal->id,
                    'cantidad' => 12,
                    'is_service' => false,
                ],
                [
                    'id' => $this->serviceProduct->id,
                    'cart_key' => $this->serviceProduct->id . '-' . $this->warehousePrincipal->id,
                    'descripcion' => $this->serviceProduct->descripcion,
                    'idunidad' => 2,
                    'unidad' => 'ZZ',
                    'idcodigo_igv' => 1,
                    'codigo_igv' => 10,
                    'igv' => 18,
                    'precio_compra' => 50.00,
                    'impuesto' => 9.00,
                    'idalmacen' => $this->warehousePrincipal->id,
                    'cantidad' => 2,
                    'is_service' => true,
                ],
            ],
            'igv' => 61.20,
            'subtotal' => 340.00,
            'total' => 401.20,
        ];

        $payload = [
            'idtipo_comprobante' => $this->invoiceType->id,
            'serie' => 'F001',
            'correlativo' => '00002001',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'dni_ruc' => $this->formalProvider->id,
            'idalmacen' => $this->warehousePrincipal->id,
            'modo_pago' => $this->cashPayMode->id,
            'condicion_pago' => 'Contado',
        ];

        $response = $this->actingAs($this->adminUser)
            ->withSession(['buy' => $cart])
            ->postJson(route('admin.save_buy'), $payload);

        $response->assertOk();

        // 1. Verify physical product stock increased in warehousePrincipal
        $physicalStock = StockProduct::where('idproducto', $this->physicalProduct->id)
            ->where('idalmacen', $this->warehousePrincipal->id)
            ->first();

        $this->assertNotNull($physicalStock, 'Physical product must have a stock record created.');
        $this->assertEquals(12, $physicalStock->stock_actual);
        $this->assertEquals(20.00, (float) $physicalStock->precio_compra);

        // 2. Verify service product never has any stock row in stock_products
        $serviceStock = StockProduct::where('idproducto', $this->serviceProduct->id)->first();
        $this->assertNull($serviceStock, 'Services must never hold stock or have records in stock_products.');

        // 3. Verify detail buy records exist for both
        $buy = Buy::where('idproveedor', $this->formalProvider->id)
            ->where('serie', 'F001')
            ->where('correlativo', '00002001')
            ->firstOrFail();

        $this->assertDatabaseHas('detail_buys', [
            'idcompra' => $buy->id,
            'idproducto' => $this->physicalProduct->id,
            'cantidad' => 12,
        ]);

        $this->assertDatabaseHas('detail_buys', [
            'idcompra' => $buy->id,
            'idproducto' => $this->serviceProduct->id,
            'cantidad' => 2,
        ]);
    }

    public function test_resulting_average_cost_matches_manual_calculation_in_3_purchase_test(): void
    {
        $this->adminUser->warehouses()->detach();
        config(['inventory.cost_method' => 'weighted_average']);

        // Clear existing stock for this product in warehousePrincipal
        StockProduct::where('idproducto', $this->physicalProduct->id)
            ->where('idalmacen', $this->warehousePrincipal->id)
            ->delete();

        // -------------------------------------------------------------
        // Purchase 1: 10 units @ S/ 10.00
        // Expected Stock: 10, Expected Cost: 10.00
        // -------------------------------------------------------------
        $cart1 = [
            'products' => [
                [
                    'id' => $this->physicalProduct->id,
                    'cart_key' => $this->physicalProduct->id . '-' . $this->warehousePrincipal->id,
                    'descripcion' => $this->physicalProduct->descripcion,
                    'idunidad' => 1,
                    'unidad' => 'NIU',
                    'idcodigo_igv' => 1,
                    'codigo_igv' => 10,
                    'igv' => 18,
                    'precio_compra' => 10.00,
                    'impuesto' => 1.80,
                    'idalmacen' => $this->warehousePrincipal->id,
                    'cantidad' => 10,
                    'is_service' => false,
                ],
            ],
            'igv' => 18.00,
            'subtotal' => 100.00,
            'total' => 118.00,
        ];

        $res1 = $this->actingAs($this->adminUser)
            ->withSession(['buy' => $cart1])
            ->postJson(route('admin.save_buy'), [
                'idtipo_comprobante' => $this->invoiceType->id,
                'serie' => 'F001',
                'correlativo' => '00003001',
                'fecha_emision' => now()->toDateString(),
                'fecha_vencimiento' => now()->toDateString(),
                'dni_ruc' => $this->formalProvider->id,
                'idalmacen' => $this->warehousePrincipal->id,
                'modo_pago' => $this->cashPayMode->id,
                'condicion_pago' => 'Contado',
            ]);
        $res1->assertOk();

        $stockAfterP1 = StockProduct::where('idproducto', $this->physicalProduct->id)
            ->where('idalmacen', $this->warehousePrincipal->id)
            ->firstOrFail();
        $this->assertEquals(10, $stockAfterP1->stock_actual);
        $this->assertEquals(10.00, (float) $stockAfterP1->precio_compra);

        // -------------------------------------------------------------
        // Purchase 2: 10 units @ S/ 20.00
        // Manual Math: (10 units * 10.00 + 10 units * 20.00) / (10 + 10) = 300 / 20 = 15.00
        // Expected Stock: 20, Expected Cost: 15.00
        // -------------------------------------------------------------
        $cart2 = [
            'products' => [
                [
                    'id' => $this->physicalProduct->id,
                    'cart_key' => $this->physicalProduct->id . '-' . $this->warehousePrincipal->id,
                    'descripcion' => $this->physicalProduct->descripcion,
                    'idunidad' => 1,
                    'unidad' => 'NIU',
                    'idcodigo_igv' => 1,
                    'codigo_igv' => 10,
                    'igv' => 18,
                    'precio_compra' => 20.00,
                    'impuesto' => 3.60,
                    'idalmacen' => $this->warehousePrincipal->id,
                    'cantidad' => 10,
                    'is_service' => false,
                ],
            ],
            'igv' => 36.00,
            'subtotal' => 200.00,
            'total' => 236.00,
        ];

        $res2 = $this->actingAs($this->adminUser)
            ->withSession(['buy' => $cart2])
            ->postJson(route('admin.save_buy'), [
                'idtipo_comprobante' => $this->invoiceType->id,
                'serie' => 'F001',
                'correlativo' => '00003002',
                'fecha_emision' => now()->toDateString(),
                'fecha_vencimiento' => now()->toDateString(),
                'dni_ruc' => $this->formalProvider->id,
                'idalmacen' => $this->warehousePrincipal->id,
                'modo_pago' => $this->cashPayMode->id,
                'condicion_pago' => 'Contado',
            ]);
        $res2->assertOk();

        $stockAfterP2 = StockProduct::where('idproducto', $this->physicalProduct->id)
            ->where('idalmacen', $this->warehousePrincipal->id)
            ->firstOrFail();
        $this->assertEquals(20, $stockAfterP2->stock_actual);
        $this->assertEquals(15.00, (float) $stockAfterP2->precio_compra);

        // -------------------------------------------------------------
        // Purchase 3: 20 units @ S/ 30.00
        // Manual Math: (20 units * 15.00 + 20 units * 30.00) / (20 + 20) = (300 + 600) / 40 = 900 / 40 = 22.50
        // Expected Stock: 40, Expected Cost: 22.50
        // -------------------------------------------------------------
        $cart3 = [
            'products' => [
                [
                    'id' => $this->physicalProduct->id,
                    'cart_key' => $this->physicalProduct->id . '-' . $this->warehousePrincipal->id,
                    'descripcion' => $this->physicalProduct->descripcion,
                    'idunidad' => 1,
                    'unidad' => 'NIU',
                    'idcodigo_igv' => 1,
                    'codigo_igv' => 10,
                    'igv' => 18,
                    'precio_compra' => 30.00,
                    'impuesto' => 5.40,
                    'idalmacen' => $this->warehousePrincipal->id,
                    'cantidad' => 20,
                    'is_service' => false,
                ],
            ],
            'igv' => 108.00,
            'subtotal' => 600.00,
            'total' => 708.00,
        ];

        $res3 = $this->actingAs($this->adminUser)
            ->withSession(['buy' => $cart3])
            ->postJson(route('admin.save_buy'), [
                'idtipo_comprobante' => $this->invoiceType->id,
                'serie' => 'F001',
                'correlativo' => '00003003',
                'fecha_emision' => now()->toDateString(),
                'fecha_vencimiento' => now()->toDateString(),
                'dni_ruc' => $this->formalProvider->id,
                'idalmacen' => $this->warehousePrincipal->id,
                'modo_pago' => $this->cashPayMode->id,
                'condicion_pago' => 'Contado',
            ]);
        $res3->assertOk();

        $stockAfterP3 = StockProduct::where('idproducto', $this->physicalProduct->id)
            ->where('idalmacen', $this->warehousePrincipal->id)
            ->firstOrFail();
        $this->assertEquals(40, $stockAfterP3->stock_actual);
        $this->assertEquals(22.50, (float) $stockAfterP3->precio_compra);

        // Also verify master product precio_compra synchronized
        $freshProduct = Product::find($this->physicalProduct->id);
        $this->assertEquals(22.50, (float) $freshProduct->precio_compra);
    }

    public function test_cost_calculation_supports_last_cost_configuration(): void
    {
        $this->adminUser->warehouses()->detach();
        config(['inventory.cost_method' => 'last_cost']);

        StockProduct::where('idproducto', $this->physicalProduct->id)
            ->where('idalmacen', $this->warehousePrincipal->id)
            ->delete();

        // Purchase 1: 10 units @ S/ 10.00
        $cart1 = [
            'products' => [
                [
                    'id' => $this->physicalProduct->id,
                    'cart_key' => $this->physicalProduct->id . '-' . $this->warehousePrincipal->id,
                    'descripcion' => $this->physicalProduct->descripcion,
                    'idunidad' => 1,
                    'unidad' => 'NIU',
                    'idcodigo_igv' => 1,
                    'codigo_igv' => 10,
                    'igv' => 18,
                    'precio_compra' => 10.00,
                    'impuesto' => 1.80,
                    'idalmacen' => $this->warehousePrincipal->id,
                    'cantidad' => 10,
                    'is_service' => false,
                ],
            ],
            'igv' => 18.00,
            'subtotal' => 100.00,
            'total' => 118.00,
        ];

        $this->actingAs($this->adminUser)
            ->withSession(['buy' => $cart1])
            ->postJson(route('admin.save_buy'), [
                'idtipo_comprobante' => $this->invoiceType->id,
                'serie' => 'F001',
                'correlativo' => '00004001',
                'fecha_emision' => now()->toDateString(),
                'fecha_vencimiento' => now()->toDateString(),
                'dni_ruc' => $this->formalProvider->id,
                'idalmacen' => $this->warehousePrincipal->id,
                'modo_pago' => $this->cashPayMode->id,
                'condicion_pago' => 'Contado',
            ])->assertOk();

        // Purchase 2: 10 units @ S/ 25.00
        $cart2 = [
            'products' => [
                [
                    'id' => $this->physicalProduct->id,
                    'cart_key' => $this->physicalProduct->id . '-' . $this->warehousePrincipal->id,
                    'descripcion' => $this->physicalProduct->descripcion,
                    'idunidad' => 1,
                    'unidad' => 'NIU',
                    'idcodigo_igv' => 1,
                    'codigo_igv' => 10,
                    'igv' => 18,
                    'precio_compra' => 25.00,
                    'impuesto' => 4.50,
                    'idalmacen' => $this->warehousePrincipal->id,
                    'cantidad' => 10,
                    'is_service' => false,
                ],
            ],
            'igv' => 45.00,
            'subtotal' => 250.00,
            'total' => 295.00,
        ];

        $this->actingAs($this->adminUser)
            ->withSession(['buy' => $cart2])
            ->postJson(route('admin.save_buy'), [
                'idtipo_comprobante' => $this->invoiceType->id,
                'serie' => 'F001',
                'correlativo' => '00004002',
                'fecha_emision' => now()->toDateString(),
                'fecha_vencimiento' => now()->toDateString(),
                'dni_ruc' => $this->formalProvider->id,
                'idalmacen' => $this->warehousePrincipal->id,
                'modo_pago' => $this->cashPayMode->id,
                'condicion_pago' => 'Contado',
            ])->assertOk();

        $stock = StockProduct::where('idproducto', $this->physicalProduct->id)
            ->where('idalmacen', $this->warehousePrincipal->id)
            ->firstOrFail();

        $this->assertEquals(20, $stock->stock_actual);
        // Under 'last_cost', cost must equal the latest purchase price (25.00), not the weighted average (17.50)
        $this->assertEquals(25.00, (float) $stock->precio_compra);
    }

    public function test_retrying_the_same_document_fails_via_validation_not_sql_exception(): void
    {
        $this->adminUser->warehouses()->detach();

        $payload = [
            'idtipo_comprobante' => $this->invoiceType->id,
            'serie' => 'F001',
            'correlativo' => '00005001',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'dni_ruc' => $this->formalProvider->id,
            'idalmacen' => $this->warehousePrincipal->id,
            'modo_pago' => $this->cashPayMode->id,
            'condicion_pago' => 'Contado',
        ];

        $cart = [
            'products' => [
                [
                    'id' => $this->physicalProduct->id,
                    'cart_key' => $this->physicalProduct->id . '-' . $this->warehousePrincipal->id,
                    'descripcion' => $this->physicalProduct->descripcion,
                    'idunidad' => 1,
                    'unidad' => 'NIU',
                    'idcodigo_igv' => 1,
                    'codigo_igv' => 10,
                    'igv' => 18,
                    'precio_compra' => 10.00,
                    'impuesto' => 1.80,
                    'idalmacen' => $this->warehousePrincipal->id,
                    'cantidad' => 1,
                    'is_service' => false,
                ],
            ],
            'igv' => 1.80,
            'subtotal' => 10.00,
            'total' => 11.80,
        ];

        // First attempt: succeeds
        $firstAttempt = $this->actingAs($this->adminUser)
            ->withSession(['buy' => $cart])
            ->postJson(route('admin.save_buy'), $payload);

        $firstAttempt->assertOk();

        // Second attempt with exact same (provider, voucher_type, serie, correlativo):
        // Must fail with HTTP 422 JSON validation error, NOT 500 SQL exception!
        $retryAttempt = $this->actingAs($this->adminUser)
            ->withSession(['buy' => $cart])
            ->postJson(route('admin.save_buy'), $payload);

        $retryAttempt->assertStatus(422);
        $retryAttempt->assertJson([
            'status' => false,
            'msg' => 'Ya existe un comprobante registrado con este proveedor, tipo, serie y número.',
            'errors' => [
                'correlativo' => ['Ya existe un comprobante registrado con este proveedor, tipo, serie y número.'],
            ],
        ]);
    }

    public function test_credit_purchase_prepares_account_payable_record_while_cash_does_not(): void
    {
        $this->adminUser->warehouses()->detach();

        // 1. Credit Purchase
        $creditDueDate = now()->addDays(30)->toDateString();
        $creditPayload = [
            'idtipo_comprobante' => $this->invoiceType->id,
            'serie' => 'F001',
            'correlativo' => '00006001',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => $creditDueDate,
            'dni_ruc' => $this->formalProvider->id,
            'idalmacen' => $this->warehousePrincipal->id,
            'modo_pago' => $this->cashPayMode->id,
            'condicion_pago' => 'Credito',
            'monto_credito' => 100.00,
            'cuotas' => [
                ['numero' => 1, 'monto' => 100.00, 'fecha_vencimiento' => $creditDueDate],
            ],
        ];

        $cart = [
            'products' => [
                [
                    'id' => $this->physicalProduct->id,
                    'cart_key' => $this->physicalProduct->id . '-' . $this->warehousePrincipal->id,
                    'descripcion' => $this->physicalProduct->descripcion,
                    'idunidad' => 1,
                    'unidad' => 'NIU',
                    'idcodigo_igv' => 1,
                    'codigo_igv' => 10,
                    'igv' => 18,
                    'precio_compra' => 100.00,
                    'impuesto' => 15.25,
                    'idalmacen' => $this->warehousePrincipal->id,
                    'cantidad' => 1,
                    'is_service' => false,
                ],
            ],
            'igv' => 15.25,
            'subtotal' => 84.75,
            'total' => 100.00,
        ];

        $creditResponse = $this->actingAs($this->adminUser)
            ->withSession(['buy' => $cart])
            ->postJson(route('admin.save_buy'), $creditPayload);

        $creditResponse->assertOk();

        $creditBuy = Buy::where('idproveedor', $this->formalProvider->id)
            ->where('serie', 'F001')
            ->where('correlativo', '00006001')
            ->firstOrFail();

        $this->assertEquals('Credito', $creditBuy->condicion_pago);
        $this->assertEquals(100.00, (float) $creditBuy->monto_credito);

        // Assert AccountPayable liability record was created
        $ap = AccountPayable::where('idcompra', $creditBuy->id)->first();
        $this->assertNotNull($ap, 'AccountPayable record must be created for credit purchases.');
        $this->assertEquals($this->formalProvider->id, $ap->idproveedor);
        $this->assertEquals($this->warehousePrincipal->id, $ap->idalmacen);
        $this->assertEquals(100.00, (float) $ap->monto_total);
        $this->assertEquals(0.00, (float) $ap->monto_pagado);
        $this->assertEquals(100.00, (float) $ap->saldo);
        $this->assertEquals('PENDIENTE', $ap->estado);
        $this->assertEquals($creditDueDate, $ap->fecha_vencimiento->toDateString());
        $this->assertCount(1, $ap->cuotas);

        // 2. Cash Purchase
        $cashPayload = [
            'idtipo_comprobante' => $this->invoiceType->id,
            'serie' => 'F001',
            'correlativo' => '00006002',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'dni_ruc' => $this->formalProvider->id,
            'idalmacen' => $this->warehousePrincipal->id,
            'modo_pago' => $this->cashPayMode->id,
            'condicion_pago' => 'Contado',
        ];

        $cashResponse = $this->actingAs($this->adminUser)
            ->withSession(['buy' => $cart])
            ->postJson(route('admin.save_buy'), $cashPayload);

        $cashResponse->assertOk();

        $cashBuy = Buy::where('idproveedor', $this->formalProvider->id)
            ->where('serie', 'F001')
            ->where('correlativo', '00006002')
            ->firstOrFail();

        $this->assertEquals('Contado', $cashBuy->condicion_pago);
        $this->assertEquals(0.00, (float) $cashBuy->monto_credito);

        // Assert no AccountPayable record is created for cash purchases
        $cashAp = AccountPayable::where('idcompra', $cashBuy->id)->first();
        $this->assertNull($cashAp, 'Cash purchases must not create AccountPayable records.');
    }

    public function test_purchase_rolls_back_completely_on_error(): void
    {
        $this->adminUser->warehouses()->detach();

        // Malformed cart product with non-existent product ID causing exception inside transaction
        $cart = [
            'products' => [
                [
                    'id' => 99999999, // Does not exist
                    'cart_key' => '99999999-' . $this->warehousePrincipal->id,
                    'descripcion' => 'PRODUCTO INVALIDO',
                    'idunidad' => 1,
                    'unidad' => 'NIU',
                    'idcodigo_igv' => 1,
                    'codigo_igv' => 10,
                    'igv' => 18,
                    'precio_compra' => 10.00,
                    'impuesto' => 1.80,
                    'idalmacen' => $this->warehousePrincipal->id,
                    'cantidad' => 1,
                    'is_service' => false,
                ],
            ],
            'igv' => 1.80,
            'subtotal' => 10.00,
            'total' => 11.80,
        ];

        $payload = [
            'idtipo_comprobante' => $this->invoiceType->id,
            'serie' => 'F001',
            'correlativo' => '00007001',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'dni_ruc' => $this->formalProvider->id,
            'idalmacen' => $this->warehousePrincipal->id,
            'modo_pago' => $this->cashPayMode->id,
            'condicion_pago' => 'Credito',
        ];

        try {
            $this->actingAs($this->adminUser)
                ->withSession(['buy' => $cart])
                ->postJson(route('admin.save_buy'), $payload);
        } catch (\Throwable $e) {
            // Expected exception during transaction
        }

        // Verify that neither the buy nor any accounts payable were persisted
        $this->assertDatabaseMissing('buys', [
            'idproveedor' => $this->formalProvider->id,
            'serie' => 'F001',
            'correlativo' => '00007001',
        ]);

        $this->assertDatabaseMissing('accounts_payable', [
            'idproveedor' => $this->formalProvider->id,
        ]);
    }
}
