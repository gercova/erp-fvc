<?php

namespace Tests\Feature;

use App\Http\Controllers\PosController;
use App\Models\Business;
use App\Models\Cash;
use App\Models\Category;
use App\Models\Client;
use App\Models\DetailQuote;
use App\Models\IdentityDocumentType;
use App\Models\IgvTypeAffection;
use App\Models\PayMode;
use App\Models\Product;
use App\Models\Quote;
use App\Models\StockProduct;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuoteManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Warehouse $warehouse;
    protected Cash $cash;
    protected Client $anonymousClient;
    protected Client $identifiedClientDni;
    protected Client $identifiedClientRuc;
    protected PayMode $payContado;
    protected Product $productGravado;
    protected Product $productExonerado;
    protected Product $serviceProduct;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->warehouse = Warehouse::find(1) ?? Warehouse::create([
            'descripcion' => 'ALMACEN PRINCIPAL FVC',
            'direccion' => 'CALLE PRINCIPAL 100',
        ]);

        $this->cash = Cash::find(1) ?? Cash::create([
            'descripcion' => 'CAJA PRINCIPAL 01',
            'idalmacen' => $this->warehouse->id,
            'estado' => 1,
        ]);

        $this->user = User::where('user', 'admin')->first() ?? User::factory()->create([
            'user' => 'admin',
            'estado' => 1,
            'idcaja' => $this->cash->id,
            'idalmacen' => $this->warehouse->id,
        ]);
        $this->user->update([
            'idcaja' => $this->cash->id,
            'idalmacen' => $this->warehouse->id,
        ]);

        if (! $this->user->hasRole('SUPERADMIN')) {
            $role = Role::firstOrCreate(['name' => 'SUPERADMIN']);
            $this->user->assignRole($role);
        }

        foreach (['admin.pos', 'admin.sale_notes', 'admin.quotes'] as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName]);
            $this->user->givePermissionTo($perm);
        }

        $this->payContado = PayMode::where('descripcion', 'Efectivo')->first() ?? PayMode::create(['descripcion' => 'Efectivo']);

        $docSinDoc = IdentityDocumentType::where('codigo', '0')->first() ?? IdentityDocumentType::create(['codigo' => '0', 'descripcion' => 'DOC.TRIB.NO.DOM.SIN.RUC', 'estado' => 1]);
        $docDni = IdentityDocumentType::where('codigo', '1')->first() ?? IdentityDocumentType::create(['codigo' => '1', 'descripcion' => 'DNI', 'estado' => 1]);
        $docRuc = IdentityDocumentType::where('codigo', '6')->first() ?? IdentityDocumentType::create(['codigo' => '6', 'descripcion' => 'RUC', 'estado' => 1]);

        $this->anonymousClient = Client::firstOrCreate(
            ['nro_documento' => '00000000'],
            [
                'iddoc' => $docSinDoc->id,
                'nombres' => 'CLIENTES VARIOS',
                'direccion' => '-',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
                'telefono' => '999999999',
            ]
        );

        $this->identifiedClientDni = Client::firstOrCreate(
            ['nro_documento' => '45678912'],
            [
                'iddoc' => $docDni->id,
                'nombres' => 'MARIA FERNANDA LOPEZ',
                'direccion' => 'AV. LARCO 456',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
                'telefono' => '988887777',
            ]
        );

        $this->identifiedClientRuc = Client::firstOrCreate(
            ['nro_documento' => '20609988771'],
            [
                'iddoc' => $docRuc->id,
                'nombres' => 'DISTRIBUIDORA INDUSTRIAL DEL SUR SAC',
                'direccion' => 'AV. INDUSTRIAL 789',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
                'telefono' => '955554444',
            ]
        );

        $category = Category::firstOrCreate(['descripcion' => 'GENERAL'], ['estado' => 1]);
        $unit = Unit::firstOrCreate(['codigo' => 'NIU'], ['descripcion' => 'UNIDAD', 'estado' => 1]);
        $igv10 = IgvTypeAffection::firstOrCreate(['codigo' => '10'], ['descripcion' => 'GRAVADO', 'tipo' => 'GRAVADA', 'estado' => 1]);
        $igv20 = IgvTypeAffection::firstOrCreate(['codigo' => '20'], ['descripcion' => 'EXONERADO', 'tipo' => 'EXONERADA', 'estado' => 1]);

        // Physical Gravado (S/ 118.00 unit -> S/ 100 base + S/ 18 IGV)
        $this->productGravado = Product::create([
            'codigo_interno' => 'PROD-Q-GRAV',
            'descripcion' => 'PRODUCTO GRAVADO Q',
            'idcategoria' => $category->id,
            'idunidad' => $unit->id,
            'idcodigo_igv' => $igv10->id,
            'precio_compra' => 50.00,
            'precio_venta' => 118.00,
            'opcion' => 1,
            'estado' => 1,
            'igv' => 18,
        ]);

        StockProduct::updateOrCreate(
            ['idproducto' => $this->productGravado->id, 'idalmacen' => $this->warehouse->id],
            ['stock_actual' => 100, 'stock_minimo' => 5, 'precio_compra' => 50.00, 'precio_venta' => 118.00]
        );

        // Physical Exonerado (S/ 50.00 unit -> S/ 50 subtotal + S/ 0 IGV)
        $this->productExonerado = Product::create([
            'codigo_interno' => 'PROD-Q-EXON',
            'descripcion' => 'PRODUCTO EXONERADO Q',
            'idcategoria' => $category->id,
            'idunidad' => $unit->id,
            'idcodigo_igv' => $igv20->id,
            'precio_compra' => 25.00,
            'precio_venta' => 50.00,
            'opcion' => 1,
            'estado' => 1,
            'igv' => 0,
        ]);

        StockProduct::updateOrCreate(
            ['idproducto' => $this->productExonerado->id, 'idalmacen' => $this->warehouse->id],
            ['stock_actual' => 100, 'stock_minimo' => 5, 'precio_compra' => 25.00, 'precio_venta' => 50.00]
        );

        // Catalog Service (S/ 80.00 unit, Gravado 18%)
        $this->serviceProduct = Product::create([
            'codigo_interno' => 'SERV-Q-01',
            'descripcion' => 'SERVICIO CONSULTORIA Q',
            'idcategoria' => $category->id,
            'idunidad' => $unit->id,
            'idcodigo_igv' => $igv10->id,
            'precio_compra' => 0.00,
            'precio_venta' => 80.00,
            'opcion' => 2,
            'estado' => 1,
            'igv' => 18,
        ]);
    }

    public function test_quote_requires_identified_customer_and_rejects_anonymous(): void
    {
        // 1. Attempting to save with anonymous client fails with 422
        $responseAnon = $this->actingAs($this->user)
            ->withSession([
                'selected_warehouse_id' => $this->warehouse->id,
                'quote' => [
                    'products' => [
                        [
                            'id' => $this->productGravado->id,
                            'descripcion' => $this->productGravado->descripcion,
                            'idunidad' => $this->productGravado->idunidad,
                            'unidad' => 'NIU',
                            'igv' => 18,
                            'idcodigo_igv' => $this->productGravado->idcodigo_igv,
                            'id_afectacion_igv' => $this->productGravado->idcodigo_igv,
                            'precio_compra' => 50.00,
                            'precio_venta' => 118.00,
                            'stock' => 100,
                            'opcion' => 1,
                            'cantidad' => 1,
                            'idalmacen' => $this->warehouse->id,
                        ],
                    ],
                ],
            ])
            ->postJson(route('admin.save_quote'), [
                'dni_ruc' => $this->anonymousClient->id,
                'fecha_emision' => now()->toDateString(),
                'modo_pago' => $this->payContado->id,
            ]);

        $responseAnon->assertStatus(422);
        $responseAnon->assertJson([
            'status' => false,
            'msg' => 'La cotización requiere un cliente identificado con documento de identidad (DNI, RUC, etc.).',
        ]);

        // 2. Saving with identified DNI customer succeeds
        $responseIdentified = $this->actingAs($this->user)
            ->withSession([
                'selected_warehouse_id' => $this->warehouse->id,
                'quote' => [
                    'products' => [
                        [
                            'id' => $this->productGravado->id,
                            'descripcion' => $this->productGravado->descripcion,
                            'idunidad' => $this->productGravado->idunidad,
                            'unidad' => 'NIU',
                            'igv' => 18,
                            'idcodigo_igv' => $this->productGravado->idcodigo_igv,
                            'id_afectacion_igv' => $this->productGravado->idcodigo_igv,
                            'precio_compra' => 50.00,
                            'precio_venta' => 118.00,
                            'stock' => 100,
                            'opcion' => 1,
                            'cantidad' => 1,
                            'idalmacen' => $this->warehouse->id,
                        ],
                    ],
                ],
            ])
            ->postJson(route('admin.save_quote'), [
                'dni_ruc' => $this->identifiedClientDni->id,
                'fecha_emision' => now()->toDateString(),
                'modo_pago' => $this->payContado->id,
            ]);

        $responseIdentified->assertStatus(200);
        $responseIdentified->assertJson(['status' => true]);

        $this->assertDatabaseHas('quotes', [
            'idcliente' => $this->identifiedClientDni->id,
            'total' => 118.00,
        ]);
    }

    public function test_quote_supports_products_and_services_from_catalog(): void
    {
        // 1. Check get_product_idwarehouse includes catalog services
        $listResponse = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->postJson(route('admin.get_products_by_idwarehouse'), [
                'idalmacen' => $this->warehouse->id,
            ]);

        $listResponse->assertStatus(200);
        $products = collect($listResponse->json('productos'));
        $this->assertTrue($products->contains('idproducto', $this->productGravado->id));
        $this->assertTrue($products->contains('idproducto', $this->serviceProduct->id));

        // 2. Add physical product to quote cart via add_product endpoint
        $addProd = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->postJson(route('admin.add_product_quote'), [
                'id' => $this->productGravado->id,
                'cantidad' => 2,
                'precio' => 118.00,
                'idalmacen' => $this->warehouse->id,
            ]);
        $addProd->assertStatus(200);
        $addProd->assertJson(['status' => true]);

        // 3. Add service to quote cart via add_product endpoint (opcion = 2, no warehouse stock)
        $addServ = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->postJson(route('admin.add_product_quote'), [
                'id' => $this->serviceProduct->id,
                'cantidad' => 1,
                'precio' => 80.00,
                'idalmacen' => $this->warehouse->id,
            ]);
        $addServ->assertStatus(200);
        $addServ->assertJson(['status' => true]);

        // 4. Save Quote with both product and service
        $saveResponse = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->postJson(route('admin.save_quote'), [
                'dni_ruc' => $this->identifiedClientRuc->id,
                'fecha_emision' => now()->toDateString(),
                'modo_pago' => $this->payContado->id,
            ]);

        $saveResponse->assertStatus(200);
        $quoteId = $saveResponse->json('idcotizacion');

        $this->assertDatabaseHas('detail_quotes', [
            'idcotizacion' => $quoteId,
            'idproducto' => $this->productGravado->id,
            'cantidad' => 2,
            'precio_unitario' => 118.00,
            'precio_total' => 236.00,
        ]);

        $this->assertDatabaseHas('detail_quotes', [
            'idcotizacion' => $quoteId,
            'idproducto' => $this->serviceProduct->id,
            'cantidad' => 1,
            'precio_unitario' => 80.00,
            'precio_total' => 80.00,
        ]);
    }

    public function test_quote_calculates_igv_using_shared_tax_calculator(): void
    {
        $taxCalculator = app(TaxCalculator::class);

        // Product 1: Gravado 2 units @ S/ 118 = S/ 236 (Subtotal: 200, IGV: 36)
        // Product 2: Exonerado 1 unit @ S/ 50 = S/ 50 (Subtotal: 50, IGV: 0)
        // Service: Gravado 1 unit @ S/ 80 = S/ 80 (Subtotal: 67.80, IGV: 12.20)
        $items = [
            [
                'id' => $this->productGravado->id,
                'descripcion' => $this->productGravado->descripcion,
                'idunidad' => $this->productGravado->idunidad,
                'unidad' => 'NIU',
                'igv' => 18,
                'idcodigo_igv' => $this->productGravado->idcodigo_igv,
                'id_afectacion_igv' => $this->productGravado->idcodigo_igv,
                'precio_compra' => 50.00,
                'precio_venta' => 118.00,
                'stock' => 100,
                'opcion' => 1,
                'cantidad' => 2,
                'idalmacen' => $this->warehouse->id,
            ],
            [
                'id' => $this->productExonerado->id,
                'descripcion' => $this->productExonerado->descripcion,
                'idunidad' => $this->productExonerado->idunidad,
                'unidad' => 'NIU',
                'igv' => 0,
                'idcodigo_igv' => $this->productExonerado->idcodigo_igv,
                'id_afectacion_igv' => $this->productExonerado->idcodigo_igv,
                'precio_compra' => 25.00,
                'precio_venta' => 50.00,
                'stock' => 100,
                'opcion' => 1,
                'cantidad' => 1,
                'idalmacen' => $this->warehouse->id,
            ],
            [
                'id' => $this->serviceProduct->id,
                'descripcion' => $this->serviceProduct->descripcion,
                'idunidad' => $this->serviceProduct->idunidad,
                'unidad' => 'NIU',
                'igv' => 18,
                'idcodigo_igv' => $this->serviceProduct->idcodigo_igv,
                'id_afectacion_igv' => $this->serviceProduct->idcodigo_igv,
                'precio_compra' => 0.00,
                'precio_venta' => 80.00,
                'stock' => null,
                'opcion' => 2,
                'cantidad' => 1,
                'idalmacen' => null,
            ],
        ];

        $expectedBreakdown = $taxCalculator->calculate($items);

        $saveResponse = $this->actingAs($this->user)
            ->withSession([
                'selected_warehouse_id' => $this->warehouse->id,
                'quote' => [
                    'products' => $items,
                ],
            ])
            ->postJson(route('admin.save_quote'), [
                'dni_ruc' => $this->identifiedClientRuc->id,
                'fecha_emision' => now()->toDateString(),
                'modo_pago' => $this->payContado->id,
            ]);

        $saveResponse->assertStatus(200);
        $quote = Quote::findOrFail($saveResponse->json('idcotizacion'));

        $this->assertEquals($expectedBreakdown['subtotal'], number_format((float) $quote->subtotal, 2, '.', ''));
        $this->assertEquals($expectedBreakdown['igv'], number_format((float) $quote->igv, 2, '.', ''));
        $this->assertEquals($expectedBreakdown['total'], number_format((float) $quote->total, 2, '.', ''));
    }

    public function test_one_click_conversion_to_sale_pos_copies_items_and_prices_yielding_identical_totals(): void
    {
        $taxCalculator = app(TaxCalculator::class);

        $items = [
            [
                'id' => $this->productGravado->id,
                'descripcion' => $this->productGravado->descripcion,
                'idunidad' => $this->productGravado->idunidad,
                'unidad' => 'NIU',
                'igv' => 18,
                'idcodigo_igv' => $this->productGravado->idcodigo_igv,
                'id_afectacion_igv' => $this->productGravado->idcodigo_igv,
                'precio_compra' => 50.00,
                'precio_venta' => 118.00,
                'stock' => 100,
                'opcion' => 1,
                'cantidad' => 3,
                'idalmacen' => $this->warehouse->id,
            ],
            [
                'id' => $this->serviceProduct->id,
                'descripcion' => $this->serviceProduct->descripcion,
                'idunidad' => $this->serviceProduct->idunidad,
                'unidad' => 'NIU',
                'igv' => 18,
                'idcodigo_igv' => $this->serviceProduct->idcodigo_igv,
                'id_afectacion_igv' => $this->serviceProduct->idcodigo_igv,
                'precio_compra' => 0.00,
                'precio_venta' => 150.00,
                'stock' => null,
                'opcion' => 2,
                'cantidad' => 2,
                'idalmacen' => null,
            ],
        ];

        $calculated = $taxCalculator->calculate($items);

        // 1. Create original quote
        $quote = Quote::create([
            'serie' => 'C001',
            'correlativo' => '00000007',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'hora' => '10:00:00',
            'idcliente' => $this->identifiedClientRuc->id,
            'idpago' => $this->payContado->id,
            'subtotal' => $calculated['subtotal'],
            'igv' => $calculated['igv'],
            'total' => $calculated['total'],
            'estado' => 1,
            'idusuario' => $this->user->id,
            'idcaja' => $this->cash->id,
        ]);

        foreach ($items as $item) {
            DetailQuote::create([
                'idcotizacion' => $quote->id,
                'idproducto' => $item['id'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio_venta'],
                'precio_total' => round($item['precio_venta'] * $item['cantidad'], 2),
                'idalmacen' => (int) $item['opcion'] === 1 ? $this->warehouse->id : null,
            ]);
        }

        // 2. Perform one-click conversion to sale
        $convertResponse = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->get(route('admin.convert_quote_to_sale', $quote->id));

        $convertResponse->assertRedirect(route('admin.pos.create'));
        $convertResponse->assertSessionHas('pos_from_quote', true);
        $convertResponse->assertSessionHas('pos_client_id', $this->identifiedClientRuc->id);
        $convertResponse->assertSessionHas('pos.products');

        $posProducts = session('pos.products');
        $this->assertCount(2, $posProducts);

        // 3. PosController creates cart from converted quote items
        $posController = app(PosController::class);
        $posCart = $posController->create_cart();

        // ACCEPTANCE CRITERIA: A converted quote yields the EXACT SAME totals as the original quote
        $this->assertEquals((float) $quote->subtotal, (float) $posCart['subtotal'], 'Subtotal must match exactly');
        $this->assertEquals((float) $quote->igv, (float) $posCart['igv'], 'IGV must match exactly');
        $this->assertEquals((float) $quote->total, (float) $posCart['total'], 'Total must match exactly');

        // Check each product was copied with exact price and quantity
        $this->assertEquals(3, $posProducts[0]['cantidad']);
        $this->assertEquals(118.00, $posProducts[0]['precio_venta']);
        $this->assertEquals(2, $posProducts[1]['cantidad']);
        $this->assertEquals(150.00, $posProducts[1]['precio_venta']);
    }

    public function test_quote_listing_and_detail_view(): void
    {
        $quote = Quote::create([
            'serie' => 'C001',
            'correlativo' => '00000008',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'hora' => '10:00:00',
            'idcliente' => $this->identifiedClientDni->id,
            'idpago' => $this->payContado->id,
            'subtotal' => 100.00,
            'igv' => 18.00,
            'total' => 118.00,
            'estado' => 1,
            'idusuario' => $this->user->id,
            'idcaja' => $this->cash->id,
        ]);

        DetailQuote::create([
            'idcotizacion' => $quote->id,
            'idproducto' => $this->productGravado->id,
            'cantidad' => 1,
            'precio_unitario' => 118.00,
            'precio_total' => 118.00,
            'idalmacen' => $this->warehouse->id,
        ]);

        // 1. DataTables query
        $listResponse = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->getJson(route('quotes.get'));

        $listResponse->assertStatus(200);
        $listResponse->assertJsonStructure(['data']);

        // 2. Detail query
        $detailResponse = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->postJson(route('quotes.detail'), [
                'id' => $quote->id,
            ]);

        $detailResponse->assertStatus(200);
        $detailResponse->assertJson(['status' => true]);
        $this->assertEquals($quote->id, $detailResponse->json('quote.id'));
        $this->assertCount(1, $detailResponse->json('detail'));
    }
}
