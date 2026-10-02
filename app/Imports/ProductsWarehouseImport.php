<?php

namespace App\Imports;

use App\Models\Product;
use App\Services\StockService;
use Exception;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class ProductsWarehouseImport implements ToModel, WithHeadingRow, WithEvents
{
    public function __construct(
        protected $idalmacen
    ) {
    }

    public function model(array $row)
    {
        $description = trim((string) ($row['descripcion'] ?? ''));
        if ($description === '' ||
            str_contains($description, 'Notas:') ||
            str_contains($description, 'Modifique todos los campos') ||
            str_contains($description, 'Este formato contiene') ||
            str_contains($description, 'servicios')) {
            return null;
        }

        $product = Product::query()
            ->where('descripcion', mb_strtoupper($description))
            ->first(['id', 'opcion']);

        if (! $product) {
            throw new Exception('Producto no encontrado para la descripción: ' . $description);
        }

        // SERVICES never hold stock nor appear in physical inventory
        if ((int) $product->opcion === 2) {
            return null;
        }

        $precioCompra = (float) ($row['precio_compra'] ?? 0);
        $precioVenta = (float) ($row['precio_venta'] ?? 0);
        $stockMinimo = ($row['stock_minimo'] === null || $row['stock_minimo'] === '') ? 5 : (int) $row['stock_minimo'];
        $stockActual = ($row['stock_actual'] === null || $row['stock_actual'] === '') ? 0 : (int) $row['stock_actual'];

        $stockService = app(StockService::class);
        $stockService->initializeStock(
            (int) $this->idalmacen,
            (int) $product->id,
            $stockActual,
            $precioCompra,
            $precioVenta,
            $stockMinimo
        );
        $stockService->setStock((int) $this->idalmacen, (int) $product->id, $stockActual);
        $stockService->updatePricingAndMinStock((int) $this->idalmacen, (int) $product->id, $precioCompra, $precioVenta, $stockMinimo);

        return null;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $richText = new RichText();
                $richText->createText('Modifique todos los campos, excepto la descripcion.');

                $event->sheet->getDelegate()->getComment('A1')
                    ->setAuthor('Sistema')
                    ->setText($richText);
            },
        ];
    }
}
