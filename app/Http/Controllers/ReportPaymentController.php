<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportPaymentController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {
    }

    /**
     * Sales by Payment Method - Index View
     */
    public function index()
    {
        $signo      = $this->signo_pais();
        $warehouses = Warehouse::orderBy('descripcion')->get();

        return view('admin.reports.payments.index', compact('signo', 'warehouses'));
    }

    /**
     * Sales by Payment Method - Data Endpoint
     */
    public function getSalesByPaymentMethod(Request $request): JsonResponse
    {
        $data = $this->reportService->getSalesByPaymentMethod($request);
        return response()->json($data);
    }

    /**
     * Sales by Payment Method - PDF Export
     */
    public function salesByPaymentMethodPdf(Request $request)
    {
        $data = $this->reportService->getSalesByPaymentMethod($request);
        $headings = ['Método de Pago', 'Cant. Transacciones', 'Total Recaudado'];

        $rows = array_map(function ($item) use ($data) {
            return [
                $item['metodo_pago'],
                $item['cantidad_transacciones'],
                $data['signo'] . ' ' . number_format($item['total_recaudado'], 2),
            ];
        }, $data['sales']);

        return $this->reportService->exportPdf(
            'Reporte de Ventas por Método de Pago',
            'reporte-ventas-metodo-pago-' . now()->format('Ymd_His') . '.pdf',
            $headings,
            $rows
        );
    }

    /**
     * Sales by Payment Method - Excel Export
     */
    public function salesByPaymentMethodExcel(Request $request): BinaryFileResponse
    {
        $data = $this->reportService->getSalesByPaymentMethod($request);
        $headings = ['Método de Pago', 'Cant. Transacciones', 'Total Recaudado'];

        $rows = array_map(function ($item) {
            return [
                $item['metodo_pago'],
                $item['cantidad_transacciones'],
                $item['total_recaudado'],
            ];
        }, $data['sales']);

        return $this->reportService->exportExcel(
            'Reporte de Ventas por Método de Pago',
            'reporte-ventas-metodo-pago-' . now()->format('Ymd_His') . '.xlsx',
            $headings,
            $rows
        );
    }
}
