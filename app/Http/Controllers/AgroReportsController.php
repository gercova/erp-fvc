<?php

namespace App\Http\Controllers;

use App\Models\AgriculturalPlot;
use App\Models\AgriculturalYieldLog;
use App\Models\Billing;
use App\Models\LivestockEvent;
use App\Models\ProductiveActivity;
use App\Models\ProductionHarvest;
use App\Models\ProductionInputMovement;
use App\Models\ProductionLaborCost;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgroReportsController extends Controller
{
    /**
     * Reporte 1: Comparativo Interanual de Producción (Year-over-Year)
     */
    public function yearOverYear(Request $request): View {
        $activities         = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id');
        $selectedPlotId     = $request->input('plot_id');

        $currentYear    = (int) $request->input('current_year', date('Y'));
        $previousYear   = $currentYear - 1;

        $plots = AgriculturalPlot::when($selectedActivityId, fn($q) => $q->where('productive_activity_id', $selectedActivityId))
            ->orderBy('name')
            ->get();

        // 12 meses
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        // Obtener cosechas de año actual y año anterior
        $queryBase = ProductionHarvest::query();
        if ($selectedActivityId) {
            $queryBase->whereHas('producedItem', fn($q) => $q->where('productive_activity_id', $selectedActivityId));
        }
        if ($selectedPlotId) {
            $queryBase->where('agricultural_plot_id', $selectedPlotId);
        }

        $currentYearData = (clone $queryBase)
            ->whereYear('harvest_date', $currentYear)
            ->selectRaw('MONTH(harvest_date) as month, SUM(quantity) as total_qty')
            ->groupBy('month')
            ->pluck('total_qty', 'month')
            ->toArray();

        $previousYearData = (clone $queryBase)
            ->whereYear('harvest_date', $previousYear)
            ->selectRaw('MONTH(harvest_date) as month, SUM(quantity) as total_qty')
            ->groupBy('month')
            ->pluck('total_qty', 'month')
            ->toArray();

        $monthlyComparison = [];
        $totalCurrent = 0;
        $totalPrevious = 0;
        $chartLabels = [];
        $chartCurrent = [];
        $chartPrevious = [];

        foreach ($months as $num => $name) {
            $curr = (float) ($currentYearData[$num] ?? 0);
            $prev = (float) ($previousYearData[$num] ?? 0);
            $diff = $curr - $prev;
            $variationPct = $prev > 0 ? (($diff / $prev) * 100) : ($curr > 0 ? 100 : 0);

            $monthlyComparison[] = [
                'month_num'      => $num,
                'month_name'     => $name,
                'current_qty'    => $curr,
                'previous_qty'   => $prev,
                'difference'     => $diff,
                'variation_pct'  => $variationPct,
            ];

            $totalCurrent += $curr;
            $totalPrevious += $prev;

            $chartLabels[] = substr($name, 0, 3);
            $chartCurrent[] = round($curr, 2);
            $chartPrevious[] = round($prev, 2);
        }

        $totalDiff = $totalCurrent - $totalPrevious;
        $totalVariationPct = $totalPrevious > 0 ? (($totalDiff / $totalPrevious) * 100) : ($totalCurrent > 0 ? 100 : 0);

        return view('admin.agrolivestock.reports.year_over_year', compact(
            'activities',
            'selectedActivityId',
            'plots',
            'selectedPlotId',
            'currentYear',
            'previousYear',
            'monthlyComparison',
            'totalCurrent',
            'totalPrevious',
            'totalDiff',
            'totalVariationPct',
            'chartLabels',
            'chartCurrent',
            'chartPrevious'
        ));
    }

    /**
     * Reporte 2: Desglose de Estructura de Costos por Categoría
     * (Replicando el cálculo del informe económico: Personal vs Alimentos vs Insumos)
     */
    public function costBreakdown(Request $request): View
    {
        $activities = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id');

        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));

        // 1. Costo de Personal / Mano de Obra (Jornales de campañas + eventos pecuarios)
        $laborCostQuery = ProductionLaborCost::query()->whereBetween('task_date', [$startDate, $endDate]);
        if ($selectedActivityId) {
            $laborCostQuery->whereHas('batch.campaign', fn($c) => $c->where('productive_activity_id', $selectedActivityId));
        }
        $personnelCost = (float) $laborCostQuery->sum('labor_cost');

        // Sumar mano de obra de eventos pecuarios
        $livestockLaborQuery = LivestockEvent::query()->whereBetween('event_date', [$startDate, $endDate]);
        if ($selectedActivityId) {
            $livestockLaborQuery->whereHas('livestockUnit', fn($u) => $u->where('productive_activity_id', $selectedActivityId));
        }
        $personnelCost += (float) $livestockLaborQuery->sum('labor_cost');

        // 2. Costo de Alimentos (Feed) para Ganado, Palma, Peces o Porcinos
        $feedCostQuery = ProductionInputMovement::where('movement_type', 'outflow_consumption')
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->whereHas('rawMaterial', fn($m) => $m->where('category', 'feed'));
        if ($selectedActivityId) {
            $feedCostQuery->where('productive_activity_id', $selectedActivityId);
        }
        $feedCost = (float) $feedCostQuery->sum('total_cost');

        // 3. Costo de Fertilizantes y Agroquímicos (Fertilizer, insecticide_fungicide)
        $agrochemicalCostQuery = ProductionInputMovement::where('movement_type', 'outflow_consumption')
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->whereHas('rawMaterial', fn($m) => $m->whereIn('category', ['fertilizer', 'insecticide_fungicide']));
        if ($selectedActivityId) {
            $agrochemicalCostQuery->where('productive_activity_id', $selectedActivityId);
        }
        $agrochemicalCost = (float) $agrochemicalCostQuery->sum('total_cost');

        // 4. Costo de Medicamentos y Sanidad Animal/Vegetal (medication)
        $medicationCostQuery = ProductionInputMovement::where('movement_type', 'outflow_consumption')
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->whereHas('rawMaterial', fn($m) => $m->where('category', 'medication'));
        if ($selectedActivityId) {
            $medicationCostQuery->where('productive_activity_id', $selectedActivityId);
        }
        $medicationCost = (float) $medicationCostQuery->sum('total_cost');

        // 5. Otros Insumos (Semillas, herramientas, otros)
        $otherInputsCostQuery = ProductionInputMovement::where('movement_type', 'outflow_consumption')
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->whereHas('rawMaterial', fn($m) => $m->whereIn('category', ['seed', 'other']));
        if ($selectedActivityId) {
            $otherInputsCostQuery->where('productive_activity_id', $selectedActivityId);
        }
        $otherInputsCost = (float) $otherInputsCostQuery->sum('total_cost');

        $totalCapturedCost = $personnelCost + $feedCost + $agrochemicalCost + $medicationCost + $otherInputsCost;

        // Porcentajes
        $calcPct = fn($val) => $totalCapturedCost > 0 ? round(($val / $totalCapturedCost) * 100, 1) : 0;

        $breakdown = [
            [
                'category'   => 'Personal y Jornales (Mano de Obra)',
                'amount'     => $personnelCost,
                'percentage' => $calcPct($personnelCost),
                'color'      => '#0061f2',
                'badge'      => 'bg-primary',
            ],
            [
                'category'   => 'Alimento Concentrado y Raciones (Feed)',
                'amount'     => $feedCost,
                'percentage' => $calcPct($feedCost),
                'color'      => '#e83e8c',
                'badge'      => 'bg-pink',
            ],
            [
                'category'   => 'Fertilizantes y Agroquímicos',
                'amount'     => $agrochemicalCost,
                'percentage' => $calcPct($agrochemicalCost),
                'color'      => '#00ac69',
                'badge'      => 'bg-success',
            ],
            [
                'category'   => 'Sanidad Animal y Medicamentos',
                'amount'     => $medicationCost,
                'percentage' => $calcPct($medicationCost),
                'color'      => '#f4a100',
                'badge'      => 'bg-warning',
            ],
            [
                'category'   => 'Semillas y Otros Insumos',
                'amount'     => $otherInputsCost,
                'percentage' => $calcPct($otherInputsCost),
                'color'      => '#6900c7',
                'badge'      => 'bg-purple',
            ],
        ];

        return view('admin.agrolivestock.reports.cost_breakdown', compact(
            'activities',
            'selectedActivityId',
            'startDate',
            'endDate',
            'breakdown',
            'totalCapturedCost'
        ));
    }

    /**
     * Reporte 3: Conciliación de Cosechas en Campo vs Facturación Oficial SUNAT
     * (Resuelve la discrepancia de tickets/email vs facturado señalada por Germán Cotrina)
     */
    public function reconciliation(Request $request): View
    {
        $activities = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id');
        $selectedStatus = $request->input('status');

        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));

        $query = AgriculturalYieldLog::with(['plot.activity', 'plantation', 'billing', 'saleNote', 'harvest'])
            ->whereBetween('harvest_date', [$startDate, $endDate]);

        if ($selectedActivityId) {
            $query->whereHas('plot', fn($p) => $p->where('productive_activity_id', $selectedActivityId));
        }

        if ($selectedStatus) {
            $query->where('reconciliation_status', $selectedStatus);
        }

        $records = $query->orderBy('harvest_date', 'desc')->get();

        $totalFieldTonnage = $records->sum('field_reported_tonnage');
        $totalInvoicedTonnage = $records->sum('invoiced_tonnage');
        $totalDiscrepancyTonnage = $totalFieldTonnage - $totalInvoicedTonnage;
        $globalDiscrepancyPct = $totalFieldTonnage > 0 ? round(($totalDiscrepancyTonnage / $totalFieldTonnage) * 100, 2) : 0;

        $matchedCount = $records->where('reconciliation_status', 'MATCHED')->count();
        $discrepancyCount = $records->where('reconciliation_status', 'DISCREPANCY')->count();
        $pendingCount = $records->where('reconciliation_status', 'PENDING_INVOICE')->count();

        // Comprobantes de venta recientes para modal de vinculación
        $recentBillings = Billing::where('anulado', false)->orderBy('fecha_emision', 'desc')->take(30)->get();

        return view('admin.agrolivestock.reports.reconciliation', compact(
            'activities',
            'selectedActivityId',
            'selectedStatus',
            'startDate',
            'endDate',
            'records',
            'totalFieldTonnage',
            'totalInvoicedTonnage',
            'totalDiscrepancyTonnage',
            'globalDiscrepancyPct',
            'matchedCount',
            'discrepancyCount',
            'pendingCount',
            'recentBillings'
        ));
    }

    /**
     * Vincular comprobante a registro de cosecha de campo para conciliación
     */
    public function linkInvoice(Request $request): JsonResponse
    {
        $request->validate([
            'yield_log_id'     => 'required|integer|exists:agricultural_yield_logs,id',
            'billing_id'       => 'nullable|integer|exists:billings,id',
            'invoiced_tonnage' => 'required|numeric|min:0',
            'notes'            => 'nullable|string|max:500',
        ]);

        $log = AgriculturalYieldLog::findOrFail($request->input('yield_log_id'));
        $invoicedTonnage = (float) $request->input('invoiced_tonnage');
        $fieldTonnage = (float) $log->field_reported_tonnage;

        $diff = round($fieldTonnage - $invoicedTonnage, 3);
        $pct = $fieldTonnage > 0 ? round(($diff / $fieldTonnage) * 100, 2) : 0;

        $status = abs($diff) < 0.05 ? 'MATCHED' : 'DISCREPANCY';

        $log->update([
            'billing_id'                => $request->input('billing_id'),
            'invoiced_tonnage'          => $invoicedTonnage,
            'weight_difference_tonnage' => $diff,
            'discrepancy_percent'       => $pct,
            'reconciliation_status'     => $status,
            'reconciliation_notes'      => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comprobante vinculado y liquidación conciliada correctamente. Diferencia: ' . $diff . ' TM (' . $pct . '%).',
        ]);
    }
}
