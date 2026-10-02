<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgroHarvestQuickEntryValidate;
use App\Models\AgriculturalNursery;
use App\Models\AgriculturalPlot;
use App\Models\AgriculturalYieldLog;
use App\Models\ProducedItem;
use App\Models\ProductiveActivity;
use App\Models\ProductionBatch;
use App\Models\ProductionCampaign;
use App\Models\ProductionHarvest;
use App\Models\StockProduct;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AgroHarvestQuickEntryController extends Controller
{
    public function index(Request $request): View
    {
        $activities = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id', $activities->first()?->id);

        $plots = AgriculturalPlot::when($selectedActivityId, fn($q) => $q->where('productive_activity_id', $selectedActivityId))
            ->where('status', 'IN_PRODUCTION')
            ->orderBy('name')
            ->get();

        $nurseries = AgriculturalNursery::when($selectedActivityId, fn($q) => $q->where('productive_activity_id', $selectedActivityId))
            ->whereIn('stage', ['READY_FOR_FIELD', 'HARDENING_ACCLIMATIZATION'])
            ->orderBy('name')
            ->get();

        $producedItems = ProducedItem::when($selectedActivityId, fn($q) => $q->where('productive_activity_id', $selectedActivityId))
            ->orderBy('name')
            ->get();

        $campaigns = ProductionCampaign::when($selectedActivityId, fn($q) => $q->where('productive_activity_id', $selectedActivityId))
            ->where('status', '!=', 'closed')
            ->orderBy('name')
            ->get();

        $batches = ProductionBatch::when($selectedActivityId, function ($q) use ($selectedActivityId) {
            $q->whereHas('campaign', fn($c) => $c->where('productive_activity_id', $selectedActivityId));
        })->where('status', '!=', 'completed')->orderBy('batch_code')->get();

        $warehouses = Warehouse::orderBy('descripcion')->get();

        // Métricas
        $queryBase = ProductionHarvest::query();
        if ($selectedActivityId) {
            $queryBase->whereHas('producedItem', fn($q) => $q->where('productive_activity_id', $selectedActivityId));
        }

        $totalHarvestedVolume = (clone $queryBase)->sum('quantity');
        $harvestsCount = (clone $queryBase)->count();
        $recentHarvestsTonnage = (clone $queryBase)->where('harvest_date', '>=', Carbon::now()->subDays(30))->sum('quantity');

        return view('admin.agrolivestock.harvests.quick_entry', compact(
            'activities',
            'selectedActivityId',
            'plots',
            'nurseries',
            'producedItems',
            'campaigns',
            'batches',
            'warehouses',
            'totalHarvestedVolume',
            'harvestsCount',
            'recentHarvestsTonnage'
        ));
    }

    public function get(Request $request): JsonResponse
    {
        $query = ProductionHarvest::with([
            'producedItem.activity',
            'campaign',
            'batch',
            'plot',
            'nursery',
            'warehouse',
            'registeredByUser'
        ]);

        if ($request->filled('activity_id')) {
            $query->whereHas('producedItem', function ($q) use ($request) {
                $q->where('productive_activity_id', $request->input('activity_id'));
            });
        }

        if ($request->filled('plot_id')) {
            $query->where('agricultural_plot_id', $request->input('plot_id'));
        }

        if ($request->filled('search_term')) {
            $term = trim($request->input('search_term'));
            $query->where(function ($q) use ($term) {
                $q->where('field_ticket_code', 'like', "%{$term}%")
                  ->orWhereHas('producedItem', fn($p) => $p->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('plot', fn($pl) => $pl->where('name', 'like', "%{$term}%"));
            });
        }

        $query->orderBy('harvest_date', 'desc');

        return datatables()->of($query)
            ->addColumn('date_col', function (ProductionHarvest $harvest) {
                $html = '<div class="font-monospace fw-bold text-dark">' . $harvest->harvest_date->format('d/m/Y') . '</div>';
                if ($harvest->field_ticket_code) {
                    $html .= '<small class="text-primary font-monospace"><i class="fas fa-ticket-alt me-1"></i>' . e($harvest->field_ticket_code) . '</small>';
                }
                return $html;
            })
            ->addColumn('source_col', function (ProductionHarvest $harvest) {
                if ($harvest->plot) {
                    return '<span class="badge bg-light text-dark border"><i class="fas fa-mountain me-1 text-success"></i>' . e($harvest->plot->name) . '</span>';
                }
                if ($harvest->nursery) {
                    return '<span class="badge bg-light text-dark border"><i class="fas fa-seedling me-1 text-info"></i>' . e($harvest->nursery->name) . '</span>';
                }
                return '<span class="text-muted small">General / Campaña</span>';
            })
            ->addColumn('item_col', function (ProductionHarvest $harvest) {
                $html = '<div class="fw-bold text-dark">' . e($harvest->producedItem->name ?? 'Producto') . '</div>';
                $html .= '<small class="text-muted">' . e($harvest->campaign->name ?? '') . '</small>';
                return $html;
            })
            ->addColumn('quantity_col', function (ProductionHarvest $harvest) {
                $unit = $harvest->producedItem->unit_of_measurement ?? '';
                $html = '<div class="fw-bold text-success fs-6">' . number_format($harvest->quantity, 2) . ' ' . e($unit) . '</div>';
                if ($harvest->field_weight_kg > 0) {
                    $html .= '<small class="text-muted">Balanza campo: ' . number_format($harvest->field_weight_kg, 1) . ' kg</small>';
                }
                return $html;
            })
            ->addColumn('warehouse_col', function (ProductionHarvest $harvest) {
                if ($harvest->warehouse) {
                    return '<span class="badge bg-info text-white"><i class="fas fa-warehouse me-1"></i>' . e($harvest->warehouse->descripcion) . '</span>';
                }
                return '<span class="badge bg-secondary text-white">Sin Almacén</span>';
            })
            ->addColumn('actions', function (ProductionHarvest $harvest) {
                return '
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-harvest" data-id="' . $harvest->id . '" title="Eliminar Cosecha">
                        <i class="fas fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['date_col', 'source_col', 'item_col', 'quantity_col', 'warehouse_col', 'actions'])
            ->make(true);
    }

    public function store(AgroHarvestQuickEntryValidate $request): RedirectResponse
    {
        $data = $request->validated();
        $data['registered_by_user_id'] = Auth::id() ?? 1;

        DB::transaction(function () use ($data) {
            // 1. Crear el registro en Bloque C (production_harvests)
            $harvest = ProductionHarvest::create($data);

            // 2. Si el producto terminado está publicado al catálogo central y se seleccionó almacén, actualizar stock mediante StockService
            $producedItem = ProducedItem::find($data['produced_item_id']);
            if ($producedItem && $producedItem->product_id && !empty($data['warehouse_id'])) {
                $stock = app(\App\Services\StockService::class)->increase(
                    (int) $data['warehouse_id'],
                    (int) $producedItem->product_id,
                    (float) $data['quantity'],
                    [
                        'precio_compra' => (float) ($data['unit_cost_calculated'] ?? 0),
                        'precio_venta'  => (float) ($producedItem->standard_cost ?? 0),
                        'stock_minimo'  => 0,
                    ]
                );

                if ($stock) {
                    $harvest->update(['stock_product_id' => $stock->id]);
                }
            }

            // 3. Si viene de una parcela agrícola, registrar en agricultural_yield_logs para trazabilidad y conciliación
            if (!empty($data['agricultural_plot_id'])) {
                $plot = AgriculturalPlot::find($data['agricultural_plot_id']);
                $harvestDate = Carbon::parse($data['harvest_date']);
                $tonnage = ($data['field_weight_kg'] ?? 0) > 0 ? ($data['field_weight_kg'] / 1000) : $data['quantity'];

                AgriculturalYieldLog::create([
                    'agricultural_plot_id'   => $data['agricultural_plot_id'],
                    'production_campaign_id' => $data['production_campaign_id'],
                    'production_harvest_id'  => $harvest->id,
                    'harvest_date'           => $data['harvest_date'],
                    'period_year'            => $harvestDate->year,
                    'period_month'           => $harvestDate->month,
                    'field_ticket_code'      => $data['field_ticket_code'] ?? null,
                    'harvested_quantity'     => $data['quantity'],
                    'field_reported_tonnage' => $tonnage,
                    'invoiced_tonnage'       => 0,
                    'weight_difference_tonnage' => 0,
                    'discrepancy_percent'    => 0,
                    'unit_of_measurement'    => $producedItem->unit_of_measurement ?? 'TM',
                    'area_harvested_ha'      => $plot->area_hectares ?? 1,
                    'yield_per_hectare'      => ($plot && $plot->area_hectares > 0) ? round($tonnage / $plot->area_hectares, 3) : 0,
                    'quality_grade'          => $data['quality_grade'] ?? 'PRIMERA',
                    'reconciliation_status'  => 'PENDING_INVOICE',
                    'registered_by_user_id'  => Auth::id() ?? 1,
                    'notes'                  => 'Registro rápido de campo. Boleto: ' . ($data['field_ticket_code'] ?? 'S/N'),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Cosecha registrada con éxito. Se actualizó el producto terminado de Bloque C e inventario central.');
    }

    public function delete(Request $request): JsonResponse
    {
        $harvest = ProductionHarvest::findOrFail($request->input('id'));
        $harvest->delete();

        return response()->json(['success' => true, 'message' => 'Cosecha eliminada correctamente.']);
    }
}
