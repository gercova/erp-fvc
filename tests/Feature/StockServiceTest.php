<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockProduct;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected StockService $stockService;
    protected Warehouse $warehouseA;
    protected Warehouse $warehouseB;
    protected Product $physicalProduct;
    protected Product $serviceProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);

        $this->warehouseA = Warehouse::find(1) ?? Warehouse::create([
            'descripcion' => 'ALMACEN PRINCIPAL TEST A',
            'direccion' => 'CALLE 1 TEST',
        ]);

        $this->warehouseB = Warehouse::find(2) ?? Warehouse::create([
            'descripcion' => 'ALMACEN SECUNDARIO TEST B',
            'direccion' => 'CALLE 2 TEST',
        ]);

        $this->physicalProduct = Product::where('opcion', 1)->first() ?? Product::create([
            'codigo_interno' => 'PHYS001',
            'codigo_barras' => '775000000001',
            'descripcion' => 'PRODUCTO FISICO TEST',
            'idunidad' => 1,
            'idcategoria' => 1,
            'igv' => 18,
            'idcodigo_igv' => 1,
            'precio_compra' => 10.00,
            'precio_venta' => 15.00,
            'opcion' => 1,
            'stock_actual' => 50,
        ]);

        $this->serviceProduct = Product::where('opcion', 2)->first() ?? Product::create([
            'codigo_interno' => 'SERV001',
            'codigo_barras' => 'SERV-001',
            'descripcion' => 'SERVICIO INTANGIBLE TEST',
            'idunidad' => 2,
            'idcategoria' => 1,
            'igv' => 18,
            'idcodigo_igv' => 1,
            'precio_compra' => 0.00,
            'precio_venta' => 100.00,
            'opcion' => 2,
            'stock_actual' => null,
        ]);
    }

    public function test_increase_stock_increases_stock_for_physical_product(): void
    {
        $this->stockService->setStock(
            $this->warehouseA->id,
            $this->physicalProduct->id,
            10,
            10.00,
            15.00,
            5
        );

        $updatedStock = $this->stockService->increase(
            $this->warehouseA->id,
            $this->physicalProduct->id,
            5
        );

        $this->assertNotNull($updatedStock);
        $this->assertEquals(15, $updatedStock->stock_actual);

        $this->assertDatabaseHas('stock_products', [
            'idproducto' => $this->physicalProduct->id,
            'idalmacen' => $this->warehouseA->id,
            'stock_actual' => 15,
        ]);
    }

    public function test_decrease_stock_decreases_stock_for_physical_product(): void
    {
        $this->stockService->setStock(
            $this->warehouseA->id,
            $this->physicalProduct->id,
            10
        );

        $updatedStock = $this->stockService->decrease(
            $this->warehouseA->id,
            $this->physicalProduct->id,
            4
        );

        $this->assertNotNull($updatedStock);
        $this->assertEquals(6, $updatedStock->stock_actual);

        $this->assertDatabaseHas('stock_products', [
            'idproducto' => $this->physicalProduct->id,
            'idalmacen' => $this->warehouseA->id,
            'stock_actual' => 6,
        ]);
    }

    public function test_decrease_stock_throws_exception_on_insufficient_stock_when_preventing_negative(): void
    {
        $this->stockService->setStock(
            $this->warehouseA->id,
            $this->physicalProduct->id,
            5
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stock insuficiente en el almacén');

        $this->stockService->decrease(
            $this->warehouseA->id,
            $this->physicalProduct->id,
            10,
            ['prevent_negative' => true]
        );
    }

    public function test_transfer_moves_stock_between_warehouses(): void
    {
        $this->stockService->setStock($this->warehouseA->id, $this->physicalProduct->id, 20);
        $this->stockService->setStock($this->warehouseB->id, $this->physicalProduct->id, 5);

        $result = $this->stockService->transfer(
            $this->warehouseA->id,
            $this->warehouseB->id,
            $this->physicalProduct->id,
            8
        );

        $this->assertNotNull($result);
        $this->assertEquals(12, $result['source']->stock_actual);
        $this->assertEquals(13, $result['destination']->stock_actual);

        $this->assertDatabaseHas('stock_products', [
            'idproducto' => $this->physicalProduct->id,
            'idalmacen' => $this->warehouseA->id,
            'stock_actual' => 12,
        ]);

        $this->assertDatabaseHas('stock_products', [
            'idproducto' => $this->physicalProduct->id,
            'idalmacen' => $this->warehouseB->id,
            'stock_actual' => 13,
        ]);
    }

    public function test_transfer_throws_exception_on_insufficient_source_stock(): void
    {
        $this->stockService->setStock($this->warehouseA->id, $this->physicalProduct->id, 5);
        $this->stockService->setStock($this->warehouseB->id, $this->physicalProduct->id, 5);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stock insuficiente en el almacén de origen');

        $this->stockService->transfer(
            $this->warehouseA->id,
            $this->warehouseB->id,
            $this->physicalProduct->id,
            10
        );
    }

    public function test_transfer_throws_exception_when_source_and_destination_are_same(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El almacén de origen y destino no pueden ser el mismo');

        $this->stockService->transfer(
            $this->warehouseA->id,
            $this->warehouseA->id,
            $this->physicalProduct->id,
            5
        );
    }

    public function test_service_never_creates_stock_products_or_inventory_movements(): void
    {
        $this->assertTrue($this->serviceProduct->isService());

        // 1. initializeStock returns null and creates no row
        $initResult = $this->stockService->initializeStock($this->warehouseA->id, $this->serviceProduct->id, 10, 0, 100, 5);
        $this->assertNull($initResult);

        // 2. setStock returns null and creates no row
        $setResult = $this->stockService->setStock($this->warehouseA->id, $this->serviceProduct->id, 20);
        $this->assertNull($setResult);

        // 3. increase returns null and creates no row
        $incResult = $this->stockService->increase($this->warehouseA->id, $this->serviceProduct->id, 5);
        $this->assertNull($incResult);

        // 4. decrease returns null and creates no row
        $decResult = $this->stockService->decrease($this->warehouseA->id, $this->serviceProduct->id, 2);
        $this->assertNull($decResult);

        // 5. transfer returns null source and destination and creates no row
        $transResult = $this->stockService->transfer($this->warehouseA->id, $this->warehouseB->id, $this->serviceProduct->id, 5);
        $this->assertNull($transResult['source']);
        $this->assertNull($transResult['destination']);

        // 6. updatePricingAndMinStock returns null and creates no row
        $pricingResult = $this->stockService->updatePricingAndMinStock($this->warehouseA->id, $this->serviceProduct->id, 0, 120, 0);
        $this->assertNull($pricingResult);

        // 7. Verify zero stock rows exist for service
        $serviceStockCount = StockProduct::where('idproducto', $this->serviceProduct->id)->count();
        $this->assertEquals(0, $serviceStockCount, 'Services must never have stock_products rows.');
    }

    public function test_set_stock_and_update_pricing_and_min_stock(): void
    {
        $stock = $this->stockService->setStock(
            $this->warehouseA->id,
            $this->physicalProduct->id,
            25,
            12.50,
            18.00,
            5
        );

        $this->assertNotNull($stock);
        $this->assertEquals(25, $stock->stock_actual);
        $this->assertEquals(12.50, (float) $stock->precio_compra);
        $this->assertEquals(18.00, (float) $stock->precio_venta);
        $this->assertEquals(5, $stock->stock_minimo);

        // Now update pricing and min stock without modifying current stock
        $updated = $this->stockService->updatePricingAndMinStock(
            $this->warehouseA->id,
            $this->physicalProduct->id,
            14.00,
            20.00,
            8
        );

        $this->assertNotNull($updated);
        $this->assertEquals(25, $updated->stock_actual);
        $this->assertEquals(14.00, (float) $updated->precio_compra);
        $this->assertEquals(20.00, (float) $updated->precio_venta);
        $this->assertEquals(8, $updated->stock_minimo);
    }

    public function test_remove_from_warehouse_prevents_removal_when_stock_exists_and_succeeds_at_zero(): void
    {
        $this->stockService->setStock($this->warehouseA->id, $this->physicalProduct->id, 10);

        try {
            $this->stockService->removeFromWarehouse($this->warehouseA->id, $this->physicalProduct->id);
            $this->fail('Should have thrown RuntimeException when removing stock > 0');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('No se puede retirar el producto mientras tenga stock', $e->getMessage());
        }

        // Set stock to 0 and remove
        $this->stockService->setStock($this->warehouseA->id, $this->physicalProduct->id, 0);
        $this->assertTrue($this->stockService->removeFromWarehouse($this->warehouseA->id, $this->physicalProduct->id));
        $this->assertDatabaseMissing('stock_products', [
            'idproducto' => $this->physicalProduct->id,
            'idalmacen' => $this->warehouseA->id,
        ]);

        // Test deleteProductStock
        $this->stockService->setStock($this->warehouseB->id, $this->physicalProduct->id, 5);
        $deletedCount = $this->stockService->deleteProductStock($this->physicalProduct->id);
        $this->assertGreaterThanOrEqual(1, $deletedCount);
        $this->assertDatabaseMissing('stock_products', [
            'idproducto' => $this->physicalProduct->id,
        ]);
    }
}
