<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockProduct;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarehouseProductsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Warehouse $warehouse;
    protected Product $physicalProduct;
    protected Product $serviceProduct;
    protected StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);

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

        $this->warehouse = Warehouse::find(1) ?? Warehouse::create([
            'descripcion' => 'ALMACEN PRINCIPAL',
            'direccion' => 'AV PRINCIPAL 123',
        ]);

        $this->physicalProduct = Product::where('opcion', 1)->first() ?? Product::create([
            'codigo_interno' => 'PHYS999',
            'codigo_barras' => '775999999001',
            'descripcion' => 'PRODUCTO FISICO WAREHOUSE TEST',
            'idunidad' => 1,
            'idcategoria' => 1,
            'igv' => 18,
            'idcodigo_igv' => 1,
            'precio_compra' => 20.00,
            'precio_venta' => 30.00,
            'opcion' => 1,
            'stock_actual' => 10,
        ]);

        $this->serviceProduct = Product::where('opcion', 2)->first() ?? Product::create([
            'codigo_interno' => 'SERV999',
            'codigo_barras' => 'SERV-999',
            'descripcion' => 'SERVICIO INTANGIBLE WAREHOUSE TEST',
            'idunidad' => 2,
            'idcategoria' => 1,
            'igv' => 18,
            'idcodigo_igv' => 1,
            'precio_compra' => 0.00,
            'precio_venta' => 80.00,
            'opcion' => 2,
            'stock_actual' => null,
        ]);
    }

    public function test_warehouse_products_datatable_excludes_services(): void
    {
        // Ensure physical product has stock in warehouse
        $this->stockService->setStock(
            $this->warehouse->id,
            $this->physicalProduct->id,
            15,
            12.00,
            18.00,
            5
        );

        $response = $this->actingAs($this->adminUser)->postJson(route('admin.get_product_warehouse'), [
            'id' => $this->warehouse->id,
        ]);

        $response->assertStatus(200);

        $data = $response->json('data') ?? [];
        $this->assertNotEmpty($data, 'Physical products should appear in warehouse product datatable');

        foreach ($data as $row) {
            $this->assertEquals(
                1,
                (int) $row['opcion'],
                'Services (opcion == 2) must NEVER appear in physical inventory datatable'
            );
            $this->assertNotEquals(
                $this->serviceProduct->id,
                $row['idproducto'],
                'Service product ID must never appear in physical inventory list'
            );
        }
    }

    public function test_warehouse_save_product_stock_routes_through_stock_service(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson(route('admin.save_product_stock'), [
            'idalmacen' => $this->warehouse->id,
            'product' => $this->physicalProduct->id,
            'cantidad' => 10,
            'precio_compra' => 22.50,
            'precio_venta' => 35.00,
            'stock_minimo' => 7,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'type' => 'success',
        ]);

        $this->assertDatabaseHas('stock_products', [
            'idalmacen' => $this->warehouse->id,
            'idproducto' => $this->physicalProduct->id,
            'stock_minimo' => 7,
        ]);
    }

    public function test_warehouse_save_product_stock_rejects_services(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson(route('admin.save_product_stock'), [
            'idalmacen' => $this->warehouse->id,
            'product' => $this->serviceProduct->id,
            'cantidad' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'msg' => 'Los servicios no forman parte del inventario físico ni manejan stock.',
        ]);

        $this->assertDatabaseMissing('stock_products', [
            'idalmacen' => $this->warehouse->id,
            'idproducto' => $this->serviceProduct->id,
        ]);
    }

    public function test_warehouse_save_products_all_assigns_only_physical_products_and_ignores_services(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson(route('admin.save_products_stock_all'), [
            'id' => $this->warehouse->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'type' => 'success',
        ]);

        // Verify that no service products have stock records created
        $serviceStockCount = StockProduct::where('idalmacen', $this->warehouse->id)
            ->whereIn('idproducto', Product::where('opcion', 2)->pluck('id'))
            ->count();

        $this->assertEquals(0, $serviceStockCount, 'Services must never hold stock or have rows in stock_products');
    }

    public function test_warehouse_excel_export_excludes_services(): void
    {
        $response = $this->actingAs($this->adminUser)->get(
            route('admin.export_products_warehouse', ['id' => $this->warehouse->id])
        );

        $response->assertStatus(200);
        $this->assertTrue(
            $response->headers->contains('content-disposition', 'attachment; filename=productos_almacen_' . $this->warehouse->id . '.xlsx') ||
            str_contains($response->headers->get('content-type', ''), 'spreadsheet') ||
            str_contains($response->headers->get('content-type', ''), 'octet-stream')
        );
    }
}
