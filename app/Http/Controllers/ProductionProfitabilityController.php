<?php

namespace App\Http\Controllers;

use App\Models\DetailBilling;
use App\Models\DetailSaleNote;
use App\Models\ProducedItem;
use App\Models\ProductiveActivity;
use App\Models\ProductionHarvest;
use App\Models\ProductionInputMovement;
use App\Models\ProductionLaborCost;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductionProfitabilityController extends Controller
{
    public function index(Request $request): View
    {
        $activities = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id');
        $selectedProductId = $request->input('product_id');

        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));

        $producedItemsQuery = ProducedItem::with('activity');
        if ($selectedActivityId) {
            $producedItemsQuery->where('productive_activity_id', $selectedActivityId);
        }
        if ($selectedProductId) {
            $producedItemsQuery->where('id', $selectedProductId);
        }
        $producedItems = $producedItemsQuery->get();

        // Calcular métricas por producto
        $reportData = [];
        $totalGlobalVolume = 0;
        $totalGlobalCost = 0;
        $totalGlobalRevenue = 0;

        foreach ($producedItems as $item) {
            // 1. Volumen de cosecha / producción
            $volume = ProductionHarvest::where('produced_item_id', $item->id)
                ->whereBetween('harvest_date', [$startDate, $endDate])
                ->sum('quantity');

            // 2. Costos de insumos aplicados en la actividad del producto
            $inputCost = ProductionInputMovement::where('productive_activity_id', $item->productive_activity_id)
                ->where('movement_type', 'outflow_consumption')
                ->whereBetween('movement_date', [$startDate, $endDate])
                ->sum('total_cost');

            // 3. Costos de mano de obra registrados en lotes/fases
            $laborCost = ProductionLaborCost::whereHas('batch.campaign', function ($q) use ($item) {
                $q->where('productive_activity_id', $item->productive_activity_id);
            })->whereBetween('task_date', [$startDate, $endDate])->sum('labor_cost');

            // 4. Gastos operativos generales / Overhead de la actividad
            $overheadCost = 0;
            if (\Illuminate\Support\Facades\Schema::hasTable('activity_transactions')) {
                $overheadCost = (float) \App\Models\ActivityTransaction::where('productive_activity_id', $item->productive_activity_id)
                    ->where('transaction_type', 'EXPENSE')
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->sum('amount');
            }

            // Prorrateo si hay varios productos en la misma actividad
            $siblingItemsCount = max(1, ProducedItem::where('productive_activity_id', $item->productive_activity_id)->count());
            $allocatedInputCost = $inputCost / $siblingItemsCount;
            $allocatedLaborCost = $laborCost / $siblingItemsCount;
            $allocatedOverheadCost = $overheadCost / $siblingItemsCount;
            $totalCost = $allocatedInputCost + $allocatedLaborCost + $allocatedOverheadCost;

            // 5. Ingresos por ventas reales (Comprobantes y Notas de venta del catálogo central)
            $salesRevenue = 0;
            $salesQuantity = 0;

            if ($item->product_id) {
                // Ventas en Comprobantes (Facturas / Boletas)
                $billingSales = DetailBilling::where('idproducto', $item->product_id)
                    ->whereHas('billing', function ($b) use ($startDate, $endDate) {
                        $b->whereBetween('fecha_emision', [$startDate, $endDate])
                          ->where('anulado', false);
                    })->selectRaw('SUM(cantidad) as qty, SUM(precio_total) as total')->first();

                // Ventas en Notas de Venta
                $saleNoteSales = DetailSaleNote::where('idproducto', $item->product_id)
                    ->whereHas('saleNote', function ($sn) use ($startDate, $endDate) {
                        $sn->whereBetween('fecha_emision', [$startDate, $endDate])
                           ->where('estado', '!=', 0);
                    })->selectRaw('SUM(cantidad) as qty, SUM(precio_total) as total')->first();

                $salesQuantity = ($billingSales->qty ?? 0) + ($saleNoteSales->qty ?? 0);
                $salesRevenue = ($billingSales->total ?? 0) + ($saleNoteSales->total ?? 0);
            }

            $unitCost = $volume > 0 ? ($totalCost / $volume) : 0;
            $grossMargin = $salesRevenue - $totalCost;
            $marginPercentage = $salesRevenue > 0 ? (($grossMargin / $salesRevenue) * 100) : 0;
            $roiPercentage = $totalCost > 0 ? (($grossMargin / $totalCost) * 100) : 0;

            $reportData[] = [
                'activity_name'    => $item->activity->name ?? 'General',
                'product_name'     => $item->name,
                'unit'             => $item->unit_of_measurement,
                'volume_harvested' => $volume,
                'sales_quantity'   => $salesQuantity,
                'input_cost'       => $allocatedInputCost,
                'labor_cost'       => $allocatedLaborCost,
                'overhead_cost'    => $allocatedOverheadCost,
                'total_cost'       => $totalCost,
                'unit_cost'        => $unitCost,
                'sales_revenue'    => $salesRevenue,
                'gross_margin'     => $grossMargin,
                'margin_percent'   => $marginPercentage,
                'roi_percent'      => $roiPercentage,
                'is_published'     => $item->is_published_to_sales,
            ];

            $totalGlobalVolume += $volume;
            $totalGlobalCost += $totalCost;
            $totalGlobalRevenue += $salesRevenue;
        }

        $totalGlobalMargin = $totalGlobalRevenue - $totalGlobalCost;
        $totalGlobalRoi = $totalGlobalCost > 0 ? (($totalGlobalMargin / $totalGlobalCost) * 100) : 0;

        return view('admin.production.profitability.index', compact(
            'activities',
            'producedItems',
            'selectedActivityId',
            'selectedProductId',
            'startDate',
            'endDate',
            'reportData',
            'totalGlobalVolume',
            'totalGlobalCost',
            'totalGlobalRevenue',
            'totalGlobalMargin',
            'totalGlobalRoi'
        ));
    }
}
