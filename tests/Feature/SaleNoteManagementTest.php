<?php

namespace Tests\Feature;

use App\Models\ArchingCash;
use App\Models\Business;
use App\Models\Cash;
use App\Models\Category;
use App\Models\Client;
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
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaleNoteManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Warehouse $warehouse;
    protected Cash $cash;
    protected ArchingCash $archingCash;
    protected TypeDocument $docTypeNotaVenta;
    protected Serie $serieNotaVenta;
    protected PayMode $payCash;
    protected Client $anonymousClient;
    protected Client $formalClient;
    protected Product $productPhysical;
    protected Product $serviceProduct;
    protected StockService $stockService;

    protected function setUp(): void {
        parent::setUp();

        $this->stockService = app(StockService::class);

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

        ArchingCash::where('idcaja', $this->cash->id)
            ->where('idusuario', $this->user->id)
            ->where('estado', 1)
            ->update(['estado' => 0]);

        $this->archingCash = ArchingCash::create([
            'idcaja' => $this->cash->id,
            'idusuario' => $this->user->id,
            'idalmacen' => $this->warehouse->id,
            'fecha_inicio' => now()->toDateString(),
            'monto_inicial' => 100.00,
            'monto_final' => 0.00,
            'total_ventas' => 0.00,
            'estado' => 1,
        ]);

        $this->docTypeNotaVenta = TypeDocument::firstOrCreate(['codigo' => '02'], ['descripcion' => 'NOTA DE VENTA', 'estado' => 1]);

        $this->serieNotaVenta = Serie::firstOrCreate(
            ['idtipo_documento' => $this->docTypeNotaVenta->id, 'serie' => 'NV01'],
            ['correlativo' => '00000001', 'idcaja' => $this->cash->id, 'idalmacen' => $this->warehouse->id, 'estado' => 1]
        );
        $this->serieNotaVenta->update(['idalmacen' => $this->warehouse->id, 'idcaja' => $this->cash->id, 'estado' => 1]);

        $this->payCash = PayMode::where('descripcion', 'Efectivo')->first() ?? PayMode::create(['descripcion' => 'Efectivo']);

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

        $this->formalClient = Client::firstOrCreate(
            ['nro_documento' => '20698765432'],
            [
                'iddoc' => $docRuc->id,
                'nombres' => 'CLIENTE FORMAL CORP SAC',
                'direccion' => 'AV. COMERCIO 500',
                'codigo_pais' => 'PE',
                'ubigeo' => '150101',
                'telefono' => '988877766',
            ]
        );

        $category = Category::firstOrCreate(['descripcion' => 'GENERAL'], ['estado' => 1]);
        $unit = Unit::firstOrCreate(['codigo' => 'NIU'], ['descripcion' => 'UNIDAD', 'estado' => 1]);
        $igv10 = IgvTypeAffection::firstOrCreate(['codigo' => '10'], ['descripcion' => 'GRAVADO', 'tipo' => 'GRAVADA', 'estado' => 1]);

        $this->productPhysical = Product::create([
            'codigo_interno' => 'PROD-NV-01',
            'descripcion' => 'PRODUCTO FISICO NV',
            'idcategoria' => $category->id,
            'idunidad' => $unit->id,
            'idcodigo_igv' => $igv10->id,
            'precio_compra' => 50.00,
            'precio_venta' => 100.00,
            'opcion' => 1,
            'estado' => 1,
            'igv' => 18,
        ]);

        StockProduct::updateOrCreate(
            ['idproducto' => $this->productPhysical->id, 'idalmacen' => $this->warehouse->id],
            ['stock_actual' => 50, 'stock_minimo' => 5, 'precio_compra' => 50.00, 'precio_venta' => 100.00]
        );

        $this->serviceProduct = Product::create([
            'codigo_interno' => 'SERV-NV-01',
            'descripcion' => 'SERVICIO INSTALACION NV',
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

    public function test_sale_note_creation_supports_general_anonymous_customer_without_receipt_invoice_rules(): void
    {
        // Total is S/ 1000 (> S/ 700 threshold for boletas).
        // Sales note has NO tax rules (no sunat dispatch, allows general customer of any amount).
        $response = $this->actingAs($this->user)
            ->withSession([
                'selected_warehouse_id' => $this->warehouse->id,
                'pos' => [
                    'products' => [
                        [
                            'id' => $this->productPhysical->id,
                            'descripcion' => $this->productPhysical->descripcion,
                            'cantidad' => 10,
                            'precio_venta' => 100.00,
                            'idcodigo_igv' => $this->productPhysical->idcodigo_igv,
                            'opcion' => 1,
                            'idalmacen' => $this->warehouse->id,
                        ],
                    ],
                ],
            ])
            ->postJson(route('pos.save_sale'), [
                'document_type_id' => (string) $this->docTypeNotaVenta->id,
                'serie_id' => (string) $this->serieNotaVenta->id,
                'client_id' => $this->anonymousClient->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => 1000.00],
                ],
                'received_amount' => 1000.00,
                'change' => 0.00,
                'global_discount' => 0.00,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $this->assertDatabaseHas('sale_notes', [
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'idcliente' => $this->anonymousClient->id,
            'total' => 1000.00,
            'estado' => 1, // Pagado
        ]);
    }

    public function test_sale_note_creation_supports_formal_customer(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'selected_warehouse_id' => $this->warehouse->id,
                'pos' => [
                    'products' => [
                        [
                            'id' => $this->productPhysical->id,
                            'descripcion' => $this->productPhysical->descripcion,
                            'cantidad' => 2,
                            'precio_venta' => 100.00,
                            'idcodigo_igv' => $this->productPhysical->idcodigo_igv,
                            'opcion' => 1,
                            'idalmacen' => $this->warehouse->id,
                        ],
                    ],
                ],
            ])
            ->postJson(route('pos.save_sale'), [
                'document_type_id' => (string) $this->docTypeNotaVenta->id,
                'serie_id' => (string) $this->serieNotaVenta->id,
                'client_id' => $this->formalClient->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => 200.00],
                ],
                'received_amount' => 200.00,
                'change' => 0.00,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $this->assertDatabaseHas('sale_notes', [
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'idcliente' => $this->formalClient->id,
            'total' => 200.00,
        ]);
    }

    public function test_sale_note_deducts_stock_only_for_products_via_stock_service_not_services(): void
    {
        $initialProductStock = StockProduct::where('idalmacen', $this->warehouse->id)
            ->where('idproducto', $this->productPhysical->id)
            ->value('stock_actual');
        $this->assertEquals(50, $initialProductStock);

        $response = $this->actingAs($this->user)
            ->withSession([
                'selected_warehouse_id' => $this->warehouse->id,
                'pos' => [
                    'products' => [
                        [
                            'id' => $this->productPhysical->id,
                            'descripcion' => $this->productPhysical->descripcion,
                            'cantidad' => 5,
                            'precio_venta' => 100.00,
                            'idcodigo_igv' => $this->productPhysical->idcodigo_igv,
                            'opcion' => 1,
                            'idalmacen' => $this->warehouse->id,
                        ],
                        [
                            'id' => $this->serviceProduct->id,
                            'descripcion' => $this->serviceProduct->descripcion,
                            'cantidad' => 2,
                            'precio_venta' => 80.00,
                            'idcodigo_igv' => $this->serviceProduct->idcodigo_igv,
                            'opcion' => 2,
                            'idalmacen' => null,
                        ],
                    ],
                ],
            ])
            ->postJson(route('pos.save_sale'), [
                'document_type_id' => (string) $this->docTypeNotaVenta->id,
                'serie_id' => (string) $this->serieNotaVenta->id,
                'client_id' => $this->formalClient->id,
                'payment_condition' => 'contado',
                'payments' => [
                    ['method_id' => $this->payCash->id, 'amount' => 660.00],
                ],
                'received_amount' => 660.00,
                'change' => 0.00,
            ]);

        $response->assertStatus(200);

        // Physical product decreased by 5: 50 - 5 = 45
        $finalProductStock = StockProduct::where('idalmacen', $this->warehouse->id)
            ->where('idproducto', $this->productPhysical->id)
            ->value('stock_actual');
        $this->assertEquals(45, $finalProductStock);

        // Service does not have stock records
        $this->assertNull(StockProduct::where('idproducto', $this->serviceProduct->id)->first());
    }

    public function test_sale_note_internal_ticket_generation(): void
    {
        $saleNote = SaleNote::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'serie' => 'NV01',
            'correlativo' => '00000099',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'hora' => '12:00:00',
            'idcliente' => $this->formalClient->id,
            'modo_pago' => 1,
            'subtotal' => 84.75,
            'igv' => 15.25,
            'total' => 100.00,
            'estado' => 1,
            'idusuario' => $this->user->id,
            'idarqueocaja' => $this->archingCash->id,
        ]);

        DetailSaleNote::create([
            'idnotaventa' => $saleNote->id,
            'idproducto' => $this->productPhysical->id,
            'cantidad' => 1,
            'precio_unitario' => 100.00,
            'precio_total' => 100.00,
            'descuento' => 0,
            'igv' => 15.25,
            'opcion' => 1,
            'idalmacen' => $this->warehouse->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->postJson(route('admin.print_sale_note'), [
                'id' => $saleNote->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'pdf']);
        $this->assertTrue($response->json('status'));
    }

    public function test_sale_note_credit_terms_schedule_and_amortization_flow(): void
    {
        $installments = [
            ['amount' => 100.00, 'due_date' => now()->addDays(15)->toDateString()],
            ['amount' => 100.00, 'due_date' => now()->addDays(30)->toDateString()],
        ];

        // 1. Create Credit Sale Note via POS
        $response = $this->actingAs($this->user)
            ->withSession([
                'selected_warehouse_id' => $this->warehouse->id,
                'pos' => [
                    'products' => [
                        [
                            'id' => $this->productPhysical->id,
                            'descripcion' => $this->productPhysical->descripcion,
                            'cantidad' => 2,
                            'precio_venta' => 100.00,
                            'idcodigo_igv' => $this->productPhysical->idcodigo_igv,
                            'opcion' => 1,
                            'idalmacen' => $this->warehouse->id,
                        ],
                    ],
                ],
            ])
            ->postJson(route('pos.save_sale'), [
                'document_type_id' => (string) $this->docTypeNotaVenta->id,
                'serie_id' => (string) $this->serieNotaVenta->id,
                'client_id' => $this->formalClient->id,
                'payment_condition' => 'credito',
                'credit_amount' => 200.00,
                'installments' => $installments,
            ]);

        $response->assertStatus(200);
        $saleNote = SaleNote::where('idtipo_comprobante', $this->docTypeNotaVenta->id)
            ->where('idcliente', $this->formalClient->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertEquals(2, $saleNote->modo_pago);
        $this->assertEquals(0, $saleNote->estado); // 0 = Pendiente
        $this->assertEquals(200.00, (float) $saleNote->monto_credito);
        $this->assertCount(2, $saleNote->cuotas);

        // 2. Query initial payment status
        $statusResp = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->getJson(route('admin.payments_sale_note', $saleNote->id));

        $statusResp->assertStatus(200);
        $this->assertEquals(200.00, $statusResp->json('pending_debt'));
        $this->assertEquals(0.00, $statusResp->json('total_paid'));
        $this->assertEquals('Pendiente', $statusResp->json('sale_note.estado_label'));

        // 3. First amortization (S/ 80)
        $amortize1 = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->postJson(route('admin.amortize_sale_note', $saleNote->id), [
                'monto' => 80.00,
                'idpago' => $this->payCash->id,
            ]);

        $amortize1->assertStatus(200);
        $amortize1->assertJson([
            'status' => true,
            'total_paid' => 80.00,
            'pending_debt' => 120.00,
            'estado' => 0,
            'estado_label' => 'Pendiente',
        ]);

        $this->assertDatabaseHas('detail_payments', [
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'idfactura' => $saleNote->id,
            'monto' => 80.00,
            'estado' => 1,
        ]);

        // 4. Overpaying beyond pending debt fails with 422
        $invalidOverpay = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->postJson(route('admin.amortize_sale_note', $saleNote->id), [
                'monto' => 150.00, // exceeds remaining 120
                'idpago' => $this->payCash->id,
            ]);
        $invalidOverpay->assertStatus(422);

        // 5. Second and final amortization (S/ 120)
        $amortize2 = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->postJson(route('admin.amortize_sale_note', $saleNote->id), [
                'monto' => 120.00,
                'idpago' => $this->payCash->id,
            ]);

        $amortize2->assertStatus(200);
        $amortize2->assertJson([
            'status' => true,
            'total_paid' => 200.00,
            'pending_debt' => 0.00,
            'estado' => 1,
            'estado_label' => 'Pagado',
        ]);

        $saleNote->refresh();
        $this->assertEquals(1, $saleNote->estado); // Transitioned to Pagado!

        // 6. Further amortization on fully paid note is rejected
        $extraAmortize = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->postJson(route('admin.amortize_sale_note', $saleNote->id), [
                'monto' => 10.00,
                'idpago' => $this->payCash->id,
            ]);
        $extraAmortize->assertStatus(422);
    }

    public function test_sale_note_cancellation_restores_product_stock_and_cancels_payments(): void
    {
        $saleNote = SaleNote::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'serie' => 'NV01',
            'correlativo' => '00000088',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'hora' => '12:00:00',
            'idcliente' => $this->formalClient->id,
            'modo_pago' => 1,
            'subtotal' => 169.49,
            'igv' => 30.51,
            'total' => 200.00,
            'estado' => 1,
            'idusuario' => $this->user->id,
            'idarqueocaja' => $this->archingCash->id,
        ]);

        DetailSaleNote::create([
            'idnotaventa' => $saleNote->id,
            'idproducto' => $this->productPhysical->id,
            'cantidad' => 3,
            'precio_unitario' => 100.00,
            'precio_total' => 300.00,
            'descuento' => 0,
            'igv' => 45.76,
            'opcion' => 1,
            'idalmacen' => $this->warehouse->id,
        ]);

        // Payment record
        $payment = DetailPayment::create([
            'idtipo_comprobante' => $this->docTypeNotaVenta->id,
            'idfactura' => $saleNote->id,
            'idpago' => $this->payCash->id,
            'monto' => 200.00,
            'idarqueocaja' => $this->archingCash->id,
            'estado' => 1,
        ]);

        $stockBefore = StockProduct::where('idalmacen', $this->warehouse->id)
            ->where('idproducto', $this->productPhysical->id)
            ->value('stock_actual');

        $response = $this->actingAs($this->user)
            ->withSession(['selected_warehouse_id' => $this->warehouse->id])
            ->postJson(route('admin.anulled_sale_note'), [
                'id' => $saleNote->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        // Stock restored (+3)
        $stockAfter = StockProduct::where('idalmacen', $this->warehouse->id)
            ->where('idproducto', $this->productPhysical->id)
            ->value('stock_actual');
        $this->assertEquals($stockBefore + 3, $stockAfter);

        // Payments cancelled (estado = 2)
        $payment->refresh();
        $this->assertEquals(2, $payment->estado);

        // SaleNote cancelled (estado = 2)
        $saleNote->refresh();
        $this->assertEquals(2, $saleNote->estado);
    }
}
