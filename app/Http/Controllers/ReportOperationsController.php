<?php

namespace App\Http\Controllers;

use App\Models\Cash;
use App\Models\Category;
use App\Models\Provider;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportOperationsController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {
    }

    /**
     * Stock per Warehouse - Index View
     */
    public function stockWarehouseIndex()
    {
        $warehouses = Warehouse::orderBy('descripcion')->get();
        $categories = Category::orderBy('descripcion')->get();
        $signo      = $this->signo_pais();

        return view('admin.reports.operations.stock_warehouse', compact('warehouses', 'categories', 'signo'));
    }

    /**
     * Stock per Warehouse - Data Endpoint
     */
    public function getStockWarehouse(Request $request): JsonResponse
    {
        $data = $this->reportService->getStockByWarehouse($request);
        return response()->json($data);
    }

    /**
     * Stock per Warehouse - PDF Export
     */
    public function stockWarehousePdf(Request $request)
    {
        $data = $this->reportService->getStockByWarehouse($request);
        $headings = ['Almacén', 'Código', 'Producto', 'Categoría', 'Unidad', 'Stock Actual', 'Stock Mín.', 'Precio Compra', 'Precio Venta', 'Valor Total'];
        
        $rows = array_map(function ($item) use ($data) {
            return [
                $item['almacen'],
                $item['codigo'],
                $item['producto'],
                $item['categoria'],
                $item['unidad'],
                number_format($item['stock_actual'], 2),
                number_format($item['stock_minimo'], 2),
                $data['signo'] . ' ' . number_format($item['precio_compra'], 2),
                $data['signo'] . ' ' . number_format($item['precio_venta'], 2),
                $data['signo'] . ' ' . number_format($item['valor_inventario'], 2),
            ];
        }, $data['items']);

        return $this->reportService->exportPdf(
            'Reporte de Stock por Almacén',
            'reporte-stock-almacen-' . now()->format('Ymd_His') . '.pdf',
            $headings,
            $rows
        );
    }

    /**
     * Stock per Warehouse - Excel Export
     */
    public function stockWarehouseExcel(Request $request): BinaryFileResponse
    {
        $data = $this->reportService->getStockByWarehouse($request);
        $headings = ['Almacén', 'Código', 'Producto', 'Categoría', 'Unidad', 'Stock Actual', 'Stock Mín.', 'Precio Compra', 'Precio Venta', 'Valor Total'];
        
        $rows = array_map(function ($item) use ($data) {
            return [
                $item['almacen'],
                $item['codigo'],
                $item['producto'],
                $item['categoria'],
                $item['unidad'],
                $item['stock_actual'],
                $item['stock_minimo'],
                $item['precio_compra'],
                $item['precio_venta'],
                $item['valor_inventario'],
            ];
        }, $data['items']);

        return $this->reportService->exportExcel(
            'Reporte de Stock por Almacén',
            'reporte-stock-almacen-' . now()->format('Ymd_His') . '.xlsx',
            $headings,
            $rows
        );
    }

    /**
     * Purchases by Supplier - Index View
     */
    public function purchasesSupplierIndex()
    {
        $providers  = Provider::orderBy('nombres')->get();
        $warehouses = Warehouse::orderBy('descripcion')->get();
        $signo      = $this->signo_pais();

        return view('admin.reports.operations.purchases_supplier', compact('providers', 'warehouses', 'signo'));
    }

    /**
     * Purchases by Supplier - Data Endpoint
     */
    public function getPurchasesSupplier(Request $request): JsonResponse
    {
        $data = $this->reportService->getPurchasesBySupplier($request);
        return response()->json($data);
    }

    /**
     * Purchases by Supplier - PDF Export
     */
    public function purchasesSupplierPdf(Request $request)
    {
        $data = $this->reportService->getPurchasesBySupplier($request);
        $headings = ['Documento', 'Proveedor', 'Cant. Compras', 'Base Gravada', 'IGV', 'Total Compras'];

        $rows = array_map(function ($item) use ($data) {
            return [
                $item['documento'],
                $item['proveedor'],
                $item['cantidad_compras'],
                $data['signo'] . ' ' . number_format($item['gravada'], 2),
                $data['signo'] . ' ' . number_format($item['igv'], 2),
                $data['signo'] . ' ' . number_format($item['total_compras'], 2),
            ];
        }, $data['suppliers']);

        return $this->reportService->exportPdf(
            'Reporte de Compras por Proveedor',
            'reporte-compras-proveedor-' . now()->format('Ymd_His') . '.pdf',
            $headings,
            $rows
        );
    }

    /**
     * Purchases by Supplier - Excel Export
     */
    public function purchasesSupplierExcel(Request $request): BinaryFileResponse
    {
        $data = $this->reportService->getPurchasesBySupplier($request);
        $headings = ['Documento', 'Proveedor', 'Cant. Compras', 'Base Gravada', 'IGV', 'Total Compras'];

        $rows = array_map(function ($item) {
            return [
                $item['documento'],
                $item['proveedor'],
                $item['cantidad_compras'],
                $item['gravada'],
                $item['igv'],
                $item['total_compras'],
            ];
        }, $data['suppliers']);

        return $this->reportService->exportExcel(
            'Reporte de Compras por Proveedor',
            'reporte-compras-proveedor-' . now()->format('Ymd_His') . '.xlsx',
            $headings,
            $rows
        );
    }

    /**
     * Daily Cash - Index View
     */
    public function dailyCashIndex()
    {
        $cashes = Cash::orderBy('descripcion')->get();
        $users  = User::orderBy('nombres')->get();
        $signo  = $this->signo_pais();

        return view('admin.reports.operations.daily_cash', compact('cashes', 'users', 'signo'));
    }

    /**
     * Daily Cash - Data Endpoint
     */
    public function getDailyCash(Request $request): JsonResponse
    {
        $data = $this->reportService->getDailyCash($request);
        return response()->json($data);
    }

    /**
     * Daily Cash - PDF Export
     */
    public function dailyCashPdf(Request $request)
    {
        $data = $this->reportService->getDailyCash($request);
        $headings = ['Caja', 'Cajero/Usuario', 'Apertura', 'Cierre', 'Monto Inicial', 'Ventas', 'Ingresos', 'Egresos', 'Esperado', 'Real', 'Diferencia', 'Estado'];

        $rows = array_map(function ($item) use ($data) {
            $item = (object) $item;
            return [
                $item->caja,
                $item->usuario,
                $item->fecha_inicio,
                $item->fecha_fin ?: '-',
                $data['signo'] . ' ' . number_format($item->monto_inicial, 2),
                $data['signo'] . ' ' . number_format($item->total_ventas, 2),
                $data['signo'] . ' ' . number_format($item->total_ingresos, 2),
                $data['signo'] . ' ' . number_format($item->total_egresos, 2),
                $data['signo'] . ' ' . number_format($item->monto_estimado, 2),
                $data['signo'] . ' ' . number_format($item->monto_final, 2),
                $data['signo'] . ' ' . number_format($item->diferencia, 2),
                $item->estado_label,
            ];
        }, $data['sessions']);

        return $this->reportService->exportPdf(
            'Reporte de Caja Diaria y Arqueos',
            'reporte-caja-diaria-' . now()->format('Ymd_His') . '.pdf',
            $headings,
            $rows
        );
    }

    /**
     * Daily Cash - Excel Export
     */
    public function dailyCashExcel(Request $request): BinaryFileResponse
    {
        $data = $this->reportService->getDailyCash($request);
        $headings = ['Caja', 'Cajero/Usuario', 'Apertura', 'Cierre', 'Monto Inicial', 'Ventas', 'Ingresos', 'Egresos', 'Esperado', 'Real', 'Diferencia', 'Estado'];

        $rows = array_map(function ($item) {
            $item = (object) $item;
            return [
                $item->caja,
                $item->usuario,
                $item->fecha_inicio,
                $item->fecha_fin ?: '-',
                $item->monto_inicial,
                $item->total_ventas,
                $item->total_ingresos,
                $item->total_egresos,
                $item->monto_estimado,
                $item->monto_final,
                $item->diferencia,
                $item->estado_label,
            ];
        }, $data['sessions']);

        return $this->reportService->exportExcel(
            'Reporte de Caja Diaria y Arqueos',
            'reporte-caja-diaria-' . now()->format('Ymd_His') . '.xlsx',
            $headings,
            $rows
        );
    }

    /**
     * Issued Vouchers and Statuses - Index View
     */
    public function issuedVouchersIndex()
    {
        $warehouses = Warehouse::orderBy('descripcion')->get();
        $signo      = $this->signo_pais();

        return view('admin.reports.operations.issued_vouchers', compact('warehouses', 'signo'));
    }

    /**
     * Issued Vouchers and Statuses - Data Endpoint
     */
    public function getIssuedVouchers(Request $request): JsonResponse
    {
        $data = $this->reportService->getIssuedVouchers($request);
        return response()->json($data);
    }

    /**
     * Issued Vouchers and Statuses - PDF Export
     */
    public function issuedVouchersPdf(Request $request)
    {
        $data = $this->reportService->getIssuedVouchers($request);
        $headings = ['Origen', 'Tipo', 'Documento', 'Fecha Emisión', 'Cliente', 'Doc. Cliente', 'Almacén', 'Subtotal', 'IGV', 'Total', 'Estado'];

        $rows = array_map(function ($item) use ($data) {
            return [
                $item['origin'] === 'billing' ? 'Comprobante' : 'Nota de Venta',
                $item['tipo_comprobante'],
                $item['numero_documento'],
                $item['fecha_emision'],
                $item['cliente'],
                $item['cliente_documento'],
                $item['almacen'],
                $data['signo'] . ' ' . number_format($item['subtotal'], 2),
                $data['signo'] . ' ' . number_format($item['igv'], 2),
                $data['signo'] . ' ' . number_format($item['total'], 2),
                $item['estado'],
            ];
        }, $data['vouchers']);

        return $this->reportService->exportPdf(
            'Reporte de Comprobantes Emitidos y Estados',
            'reporte-comprobantes-estados-' . now()->format('Ymd_His') . '.pdf',
            $headings,
            $rows
        );
    }

    /**
     * Issued Vouchers and Statuses - Excel Export
     */
    public function issuedVouchersExcel(Request $request): BinaryFileResponse
    {
        $data = $this->reportService->getIssuedVouchers($request);
        $headings = ['Origen', 'Tipo', 'Documento', 'Fecha Emisión', 'Cliente', 'Doc. Cliente', 'Almacén', 'Subtotal', 'IGV', 'Total', 'Estado'];

        $rows = array_map(function ($item) {
            return [
                $item['origin'] === 'billing' ? 'Comprobante' : 'Nota de Venta',
                $item['tipo_comprobante'],
                $item['numero_documento'],
                $item['fecha_emision'],
                $item['cliente'],
                $item['cliente_documento'],
                $item['almacen'],
                $item['subtotal'],
                $item['igv'],
                $item['total'],
                $item['estado'],
            ];
        }, $data['vouchers']);

        return $this->reportService->exportExcel(
            'Reporte de Comprobantes Emitidos y Estados',
            'reporte-comprobantes-estados-' . now()->format('Ymd_His') . '.xlsx',
            $headings,
            $rows
        );
    }
}
