<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockProduct;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class StockService
{
    /**
     * Increment physical inventory in a warehouse using lockForUpdate.
     * Services never generate stock rows or inventory movements.
     */
    public function increase(int $warehouseId, int $productId, float|int $quantity, array $attributes = []): ?StockProduct
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad a incrementar debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($warehouseId, $productId, $quantity, $attributes) {
            $product = Product::find($productId);

            if (! $product) {
                throw new InvalidArgumentException('Producto no encontrado.');
            }

            if ($product->isService()) {
                return null;
            }

            $stock = StockProduct::where('idalmacen', $warehouseId)
                ->where('idproducto', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = new StockProduct([
                    'idalmacen' => $warehouseId,
                    'idproducto' => $productId,
                    'stock_actual' => (int) $quantity,
                    'stock_minimo' => $attributes['stock_minimo'] ?? 5,
                    'precio_compra' => $attributes['precio_compra'] ?? ($product->precio_compra ?? 0),
                    'precio_venta' => $attributes['precio_venta'] ?? ($product->precio_venta ?? 0),
                    'fecha_registro' => $attributes['fecha_registro'] ?? now()->toDateString(),
                    'stock_entrada' => $attributes['stock_entrada'] ?? (int) $quantity,
                ]);
            } else {
                $stock->stock_actual = (int) $stock->stock_actual + (int) $quantity;
                if (isset($attributes['precio_compra'])) {
                    $stock->precio_compra = $attributes['precio_compra'];
                }
                if (isset($attributes['precio_venta'])) {
                    $stock->precio_venta = $attributes['precio_venta'];
                }
            }

            $stock->save();

            return $stock;
        });
    }

    /**
     * Decrement physical inventory in a warehouse using lockForUpdate.
     * Services never generate stock rows or inventory movements.
     */
    public function decrease(int $warehouseId, int $productId, float|int $quantity, array $options = []): ?StockProduct
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad a descontar debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($warehouseId, $productId, $quantity, $options) {
            $product = Product::find($productId);

            if (! $product) {
                throw new InvalidArgumentException('Producto no encontrado.');
            }

            if ($product->isService()) {
                return null;
            }

            $stock = StockProduct::where('idalmacen', $warehouseId)
                ->where('idproducto', $productId)
                ->lockForUpdate()
                ->first();

            $allowNegative = $options['allow_negative'] ?? true;
            $preventNegative = $options['prevent_negative'] ?? false;

            if (! $stock) {
                if ($preventNegative || ! $allowNegative) {
                    throw new RuntimeException('El producto no cuenta con existencias en este almacén.');
                }

                $stock = new StockProduct([
                    'idalmacen' => $warehouseId,
                    'idproducto' => $productId,
                    'stock_actual' => -((int) $quantity),
                    'stock_minimo' => 5,
                    'precio_compra' => $product->precio_compra ?? 0,
                    'precio_venta' => $product->precio_venta ?? 0,
                    'fecha_registro' => now()->toDateString(),
                    'stock_entrada' => 0,
                ]);
            } else {
                if ($preventNegative && ((int) $stock->stock_actual < (int) $quantity)) {
                    throw new RuntimeException('Stock insuficiente en el almacén.');
                }

                $stock->stock_actual = (int) $stock->stock_actual - (int) $quantity;
            }

            $stock->save();

            return $stock;
        });
    }

    /**
     * Transfer physical inventory between two warehouses using lockForUpdate.
     * Services never generate stock rows or inventory movements.
     */
    public function transfer(int $sourceWarehouseId, int $destinationWarehouseId, int $productId, float|int $quantity): array
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad a transferir debe ser mayor a cero.');
        }

        if ($sourceWarehouseId === $destinationWarehouseId) {
            throw new InvalidArgumentException('El almacén de origen y destino no pueden ser el mismo.');
        }

        return DB::transaction(function () use ($sourceWarehouseId, $destinationWarehouseId, $productId, $quantity) {
            $product = Product::find($productId);

            if (! $product) {
                throw new InvalidArgumentException('Producto no encontrado.');
            }

            if ($product->isService()) {
                return ['source' => null, 'destination' => null];
            }

            // Lock warehouses in deterministic ID order to avoid deadlocks
            $warehouseIds = [$sourceWarehouseId, $destinationWarehouseId];
            sort($warehouseIds);
            StockProduct::whereIn('idalmacen', $warehouseIds)
                ->where('idproducto', $productId)
                ->lockForUpdate()
                ->get();

            $source = StockProduct::where('idalmacen', $sourceWarehouseId)
                ->where('idproducto', $productId)
                ->lockForUpdate()
                ->first();

            if (! $source || (int) $source->stock_actual < (int) $quantity) {
                throw new RuntimeException('Stock insuficiente en el almacén de origen.');
            }

            $destination = StockProduct::where('idalmacen', $destinationWarehouseId)
                ->where('idproducto', $productId)
                ->lockForUpdate()
                ->first();

            if (! $destination) {
                $destination = new StockProduct([
                    'idalmacen' => $destinationWarehouseId,
                    'idproducto' => $productId,
                    'stock_actual' => (int) $quantity,
                    'stock_minimo' => $source->stock_minimo ?? 5,
                    'precio_compra' => $source->precio_compra ?? ($product->precio_compra ?? 0),
                    'precio_venta' => $source->precio_venta ?? ($product->precio_venta ?? 0),
                    'fecha_registro' => now()->toDateString(),
                    'stock_entrada' => (int) $quantity,
                ]);
            } else {
                $destination->stock_actual = (int) $destination->stock_actual + (int) $quantity;
            }

            $source->stock_actual = (int) $source->stock_actual - (int) $quantity;

            $source->save();
            $destination->save();

            return ['source' => $source, 'destination' => $destination];
        });
    }

    /**
     * Initialize stock record for a product in a warehouse.
     * Services never generate stock rows or inventory movements.
     */
    public function initializeStock(
        int $warehouseId,
        int $productId,
        float|int $initialStock,
        ?float $purchasePrice = null,
        ?float $salePrice = null,
        ?int $minStock = 5
    ): ?StockProduct {
        if ($initialStock < 0) {
            throw new InvalidArgumentException('El stock inicial no puede ser negativo.');
        }

        return DB::transaction(function () use ($warehouseId, $productId, $initialStock, $purchasePrice, $salePrice, $minStock) {
            $product = Product::find($productId);

            if (! $product) {
                throw new InvalidArgumentException('Producto no encontrado.');
            }

            if ($product->isService()) {
                return null;
            }

            $stock = StockProduct::where('idalmacen', $warehouseId)
                ->where('idproducto', $productId)
                ->lockForUpdate()
                ->first();

            $pCompra = $purchasePrice !== null ? number_format((float) $purchasePrice, 2, '.', '') : number_format((float) ($product->precio_compra ?? 0), 2, '.', '');
            $pVenta = $salePrice !== null ? number_format((float) $salePrice, 2, '.', '') : number_format((float) ($product->precio_venta ?? 0), 2, '.', '');
            $pMin = $minStock ?? 5;

            if (! $stock) {
                $stock = new StockProduct([
                    'idalmacen' => $warehouseId,
                    'idproducto' => $productId,
                    'stock_actual' => (int) $initialStock,
                    'stock_minimo' => $pMin,
                    'precio_compra' => $pCompra,
                    'precio_venta' => $pVenta,
                    'fecha_registro' => now()->toDateString(),
                    'stock_entrada' => (int) $initialStock,
                ]);
            } else {
                if ($initialStock > 0 && ($stock->stock_actual === null || (int) $stock->stock_actual === 0)) {
                    $stock->stock_actual = (int) $initialStock;
                    $stock->stock_entrada = (int) $initialStock;
                }
                if ($purchasePrice !== null) {
                    $stock->precio_compra = $pCompra;
                }
                if ($salePrice !== null) {
                    $stock->precio_venta = $pVenta;
                }
                if ($minStock !== null) {
                    $stock->stock_minimo = $pMin;
                }
            }

            $stock->save();

            return $stock;
        });
    }

    /**
     * Set absolute stock level for a product in a warehouse.
     */
    public function setStock(
        int $warehouseId,
        int $productId,
        float|int $newStock,
        ?float $purchasePrice = null,
        ?float $salePrice = null,
        ?int $minStock = null
    ): ?StockProduct {
        if ($newStock < 0) {
            throw new InvalidArgumentException('El stock actual no puede ser negativo.');
        }

        return DB::transaction(function () use ($warehouseId, $productId, $newStock, $purchasePrice, $salePrice, $minStock) {
            $product = Product::find($productId);

            if (! $product || $product->isService()) {
                return null;
            }

            $stock = StockProduct::where('idalmacen', $warehouseId)
                ->where('idproducto', $productId)
                ->lockForUpdate()
                ->first();

            $pCompra = $purchasePrice !== null ? (float) $purchasePrice : (float) ($product->precio_compra ?? 0);
            $pVenta = $salePrice !== null ? (float) $salePrice : (float) ($product->precio_venta ?? 0);
            $pMin = $minStock ?? 5;

            if (! $stock) {
                $stock = new StockProduct([
                    'idalmacen' => $warehouseId,
                    'idproducto' => $productId,
                    'stock_actual' => (int) $newStock,
                    'stock_minimo' => $pMin,
                    'precio_compra' => number_format($pCompra, 2, '.', ''),
                    'precio_venta' => number_format($pVenta, 2, '.', ''),
                    'fecha_registro' => now()->toDateString(),
                    'stock_entrada' => (int) $newStock,
                ]);
            } else {
                $stock->stock_actual = (int) $newStock;
                if ($purchasePrice !== null) {
                    $stock->precio_compra = number_format($pCompra, 2, '.', '');
                }
                if ($salePrice !== null) {
                    $stock->precio_venta = number_format($pVenta, 2, '.', '');
                }
                if ($minStock !== null) {
                    $stock->stock_minimo = $pMin;
                }
            }

            $stock->save();

            return $stock;
        });
    }

    /**
     * Update pricing and minimum stock thresholds per warehouse.
     */
    public function updatePricingAndMinStock(
        int $warehouseId,
        int $productId,
        float $purchasePrice,
        float $salePrice,
        ?int $minStock = null
    ): ?StockProduct {
        return DB::transaction(function () use ($warehouseId, $productId, $purchasePrice, $salePrice, $minStock) {
            $product = Product::find($productId);

            if (! $product || $product->isService()) {
                return null;
            }

            $stock = StockProduct::where('idalmacen', $warehouseId)
                ->where('idproducto', $productId)
                ->lockForUpdate()
                ->first();

            $pCompra = number_format((float) $purchasePrice, 2, '.', '');
            $pVenta = number_format((float) $salePrice, 2, '.', '');

            if (! $stock) {
                $stock = new StockProduct([
                    'idalmacen' => $warehouseId,
                    'idproducto' => $productId,
                    'stock_actual' => 0,
                    'stock_minimo' => $minStock ?? 5,
                    'precio_compra' => $pCompra,
                    'precio_venta' => $pVenta,
                    'fecha_registro' => now()->toDateString(),
                    'stock_entrada' => 0,
                ]);
            } else {
                $stock->precio_compra = $pCompra;
                $stock->precio_venta = $pVenta;
                if ($minStock !== null) {
                    $stock->stock_minimo = $minStock;
                }
            }

            $stock->save();

            return $stock;
        });
    }

    /**
     * Remove a product from a warehouse if it has 0 stock.
     */
    public function removeFromWarehouse(int $warehouseId, int $productId): bool
    {
        return DB::transaction(function () use ($warehouseId, $productId) {
            $stock = StockProduct::where('idalmacen', $warehouseId)
                ->where('idproducto', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                return true;
            }

            if ((int) $stock->stock_actual > 0) {
                throw new RuntimeException('No se puede retirar el producto mientras tenga stock en el almacén.');
            }

            $stock->delete();

            return true;
        });
    }

    /**
     * Delete all stock records for a product across all warehouses.
     */
    public function deleteProductStock(int $productId): int
    {
        return DB::transaction(function () use ($productId) {
            return StockProduct::where('idproducto', $productId)->delete();
        });
    }
}
