<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TypeDocument;
use App\Models\Warehouse;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportSalesController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {
    }

    /**
     * Sales by Date - View
     */
    public function index()
    {
        $signo      = $this->signo_pais();
        $warehouses = Warehouse::orderBy('descripcion')->get();

        return view('admin.reports.sales.index', compact('signo', 'warehouses'));
    }

    /**
     * Sales by Date - Data Endpoint
     */
    public function getSalesReport(Request $request): JsonResponse
    {
        $data = $this->reportService->getSalesByDate($request);
        return response()->json($data);
    }

    /**
     * Sales by Date - PDF Export
     */
    public function salesByDatePdf(Request $request)
    {
        $data = $this->reportService->getSalesByDate($request);
        $headings = ['Fecha', 'Cant. Ventas', 'Subtotal', 'IGV', 'Total', 'Ticket Promedio'];

        $rows = array_map(function ($item) use ($data) {
            return [
                $item['fecha'],
                $item['cantidad_ventas'],
                $data['signo'] . ' ' . number_format($item['subtotal'], 2),
                $data['signo'] . ' ' . number_format($item['total_impuestos'], 2),
                $data['signo'] . ' ' . number_format($item['total'], 2),
                $data['signo'] . ' ' . number_format($item['ticket_promedio'], 2),
            ];
        }, $data['sales']);

        return $this->reportService->exportPdf(
            'Reporte de Ventas por Fecha',
            'reporte-ventas-fecha-' . now()->format('Ymd_His') . '.pdf',
            $headings,
            $rows
        );
    }

    /**
     * Sales by Date - Excel Export
     */
    public function salesByDateExcel(Request $request): BinaryFileResponse
    {
        $data = $this->reportService->getSalesByDate($request);
        $headings = ['Fecha', 'Cant. Ventas', 'Subtotal', 'IGV', 'Total', 'Ticket Promedio'];

        $rows = array_map(function ($item) {
            return [
                $item['fecha'],
                $item['cantidad_ventas'],
                $item['subtotal'],
                $item['total_impuestos'],
                $item['total'],
                $item['ticket_promedio'],
            ];
        }, $data['sales']);

        return $this->reportService->exportExcel(
            'Reporte de Ventas por Fecha',
            'reporte-ventas-fecha-' . now()->format('Ymd_His') . '.xlsx',
            $headings,
            $rows
        );
    }

    /**
     * Sales by Product - View
     */
    public function salesByProductIndex()
    {
        $signo      = $this->signo_pais();
        $warehouses = Warehouse::orderBy('descripcion')->get();

        return view('admin.reports.sales.sales_product', compact('signo', 'warehouses'));
    }

    /**
     * Sales by Product - Data Endpoint
     */
    public function getSalesByProduct(Request $request): JsonResponse
    {
        $data = $this->reportService->getSalesByProduct($request);
        return response()->json($data);
    }

    /**
     * Sales by Product - PDF Export
     */
    public function salesByProductPdf(Request $request)
    {
        $data = $this->reportService->getSalesByProduct($request);
        $headings = ['Código', 'Producto', 'Categoría', 'Unidad', 'Cant. Vendida', 'Total Ventas', 'Precio Promedio'];

        $rows = array_map(function ($item) use ($data) {
            return [
                $item['codigo'],
                $item['producto'],
                $item['categoria'],
                $item['unidad'],
                number_format($item['cantidad_vendida'], 2),
                $data['signo'] . ' ' . number_format($item['total_ventas'], 2),
                $data['signo'] . ' ' . number_format($item['precio_promedio'], 2),
            ];
        }, $data['sales']);

        return $this->reportService->exportPdf(
            'Reporte de Ventas por Producto',
            'reporte-ventas-producto-' . now()->format('Ymd_His') . '.pdf',
            $headings,
            $rows
        );
    }

    /**
     * Sales by Product - Excel Export
     */
    public function salesByProductExcel(Request $request): BinaryFileResponse
    {
        $data = $this->reportService->getSalesByProduct($request);
        $headings = ['Código', 'Producto', 'Categoría', 'Unidad', 'Cant. Vendida', 'Total Ventas', 'Precio Promedio'];

        $rows = array_map(function ($item) {
            return [
                $item['codigo'],
                $item['producto'],
                $item['categoria'],
                $item['unidad'],
                $item['cantidad_vendida'],
                $item['total_ventas'],
                $item['precio_promedio'],
            ];
        }, $data['sales']);

        return $this->reportService->exportExcel(
            'Reporte de Ventas por Producto',
            'reporte-ventas-producto-' . now()->format('Ymd_His') . '.xlsx',
            $headings,
            $rows
        );
    }

    /**
     * Sales by Customer - View
     */
    public function salesByCustomerIndex()
    {
        $signo      = $this->signo_pais();
        $clients    = Client::orderBy('nombres')->get();
        $warehouses = Warehouse::orderBy('descripcion')->get();

        return view('admin.reports.sales.sales_customer', compact('signo', 'clients', 'warehouses'));
    }

    /**
     * Sales by Customer - Data Endpoint
     */
    public function getSalesByCustomer(Request $request): JsonResponse
    {
        $data = $this->reportService->getSalesByCustomer($request);
        return response()->json($data);
    }

    /**
     * Sales by Customer - PDF Export
     */
    public function salesByCustomerPdf(Request $request)
    {
        $data = $this->reportService->getSalesByCustomer($request);
        $headings = ['Documento', 'Cliente', 'Cant. Compras', 'Subtotal', 'IGV', 'Total Ventas', 'Ticket Promedio'];

        $rows = array_map(function ($item) use ($data) {
            return [
                $item['cliente_documento'],
                $item['cliente'],
                $item['cantidad_compras'],
                $data['signo'] . ' ' . number_format($item['subtotal'], 2),
                $data['signo'] . ' ' . number_format($item['igv'], 2),
                $data['signo'] . ' ' . number_format($item['total_ventas'], 2),
                $data['signo'] . ' ' . number_format($item['ticket_promedio'], 2),
            ];
        }, $data['sales']);

        return $this->reportService->exportPdf(
            'Reporte de Ventas por Cliente',
            'reporte-ventas-cliente-' . now()->format('Ymd_His') . '.pdf',
            $headings,
            $rows
        );
    }

    /**
     * Sales by Customer - Excel Export
     */
    public function salesByCustomerExcel(Request $request): BinaryFileResponse
    {
        $data = $this->reportService->getSalesByCustomer($request);
        $headings = ['Documento', 'Cliente', 'Cant. Compras', 'Subtotal', 'IGV', 'Total Ventas', 'Ticket Promedio'];

        $rows = array_map(function ($item) {
            return [
                $item['cliente_documento'],
                $item['cliente'],
                $item['cantidad_compras'],
                $item['subtotal'],
                $item['igv'],
                $item['total_ventas'],
                $item['ticket_promedio'],
            ];
        }, $data['sales']);

        return $this->reportService->exportExcel(
            'Reporte de Ventas por Cliente',
            'reporte-ventas-cliente-' . now()->format('Ymd_His') . '.xlsx',
            $headings,
            $rows
        );
    }

    /**
     * Sales by Type of Document - View
     */
    public function salesByTypeDocumentIndex()
    {
        $signo         = $this->signo_pais();
        $typeDocuments = TypeDocument::where('estado', 1)->get();
        $warehouses    = Warehouse::orderBy('descripcion')->get();

        return view('admin.reports.sales.sales_type_document', compact('signo', 'typeDocuments', 'warehouses'));
    }

    /**
     * Sales by Type of Document - Data Endpoint
     */
    public function getSalesByTypeDocument(Request $request): JsonResponse
    {
        $data = $this->reportService->getSalesByTypeDocument($request);
        return response()->json($data);
    }

    /**
     * Sales by Type of Document - PDF Export
     */
    public function salesByTypeDocumentPdf(Request $request)
    {
        $data = $this->reportService->getSalesByTypeDocument($request);
        $headings = ['Código', 'Tipo de Comprobante', 'Cant. Documentos', 'Subtotal', 'IGV', 'Total'];

        $rows = array_map(function ($item) use ($data) {
            return [
                $item['codigo'],
                $item['tipo_comprobante'],
                $item['cantidad_documentos'],
                $data['signo'] . ' ' . number_format($item['subtotal'], 2),
                $data['signo'] . ' ' . number_format($item['igv'], 2),
                $data['signo'] . ' ' . number_format($item['total_ventas'], 2),
            ];
        }, $data['sales']);

        return $this->reportService->exportPdf(
            'Reporte de Ventas por Tipo de Comprobante',
            'reporte-ventas-tipo-comprobante-' . now()->format('Ymd_His') . '.pdf',
            $headings,
            $rows
        );
    }

    /**
     * Sales by Type of Document - Excel Export
     */
    public function salesByTypeDocumentExcel(Request $request): BinaryFileResponse
    {
        $data = $this->reportService->getSalesByTypeDocument($request);
        $headings = ['Código', 'Tipo de Comprobante', 'Cant. Documentos', 'Subtotal', 'IGV', 'Total'];

        $rows = array_map(function ($item) {
            return [
                $item['codigo'],
                $item['tipo_comprobante'],
                $item['cantidad_documentos'],
                $item['subtotal'],
                $item['igv'],
                $item['total_ventas'],
            ];
        }, $data['sales']);

        return $this->reportService->exportExcel(
            'Reporte de Ventas por Tipo de Comprobante',
            'reporte-ventas-tipo-comprobante-' . now()->format('Ymd_His') . '.xlsx',
            $headings,
            $rows
        );
    }
}
