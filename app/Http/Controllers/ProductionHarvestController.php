<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductionHarvestValidate;
use App\Models\ProducedItem;
use App\Models\ProductiveActivity;
use App\Models\ProductionCampaign;
use App\Models\ProductionHarvest;
use App\Models\StockProduct;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionHarvestController extends Controller
{
    public function index(Request $request): View
    {
        $activities = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id', $activities->first()?->id);

        $campaigns = ProductionCampaign::when($selectedActivityId, fn($q) => $q->where('productive_activity_id', $selectedActivityId))
            ->orderBy('name')
            ->get();

        $producedItems = ProducedItem::when($selectedActivityId, fn($q) => $q->where('productive_activity_id', $selectedActivityId))
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::orderBy('descripcion')->get();

        $queryBase = ProductionHarvest::query();
        if ($selectedActivityId) {
            $queryBase->whereHas('producedItem', fn($q) => $q->where('productive_activity_id', $selectedActivityId));
        }

        $totalHarvestsCount = (clone $queryBase)->count();
        $totalQuantity = (clone $queryBase)->sum('quantity');

        return view('admin.production.harvests.index', compact(
            'activities',
            'selectedActivityId',
            'campaigns',
            'producedItems',
            'warehouses',
            'totalHarvestsCount',
            'totalQuantity'
        ));
    }

    public function get(Request $request): JsonResponse
    {
        $query = ProductionHarvest::with(['producedItem.activity', 'campaign', 'batch', 'warehouse', 'registeredByUser']);

        if ($request->filled('activity_id')) {
            $activityId = $request->input('activity_id');
            $query->whereHas('producedItem', fn($q) => $q->where('productive_activity_id', $activityId));
        }

        if ($request->filled('campaign_id')) {
            $query->where('production_campaign_id', $request->input('campaign_id'));
        }

        if ($request->filled('search_term')) {
            $term = trim($request->input('search_term'));
            $query->where(function ($q) use ($term) {
                $q->whereHas('producedItem', fn($p) => $p->where('name', 'like', "%{$term}%"))
                  ->orWhere('quality_grade', 'like', "%{$term}%")
                  ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        $query->orderBy('harvest_date', 'desc');

        return datatables()->of($query)
            ->addColumn('date_col', function (ProductionHarvest $harvest) {
                return '<span class="font-monospace">' . $harvest->harvest_date->format('d/m/Y') . '</span>';
            })
            ->addColumn('item_col', function (ProductionHarvest $harvest) {
                $html = '<div class="fw-bold text-dark">' . e($harvest->producedItem->name ?? 'Producto') . '</div>';
                if ($harvest->producedItem?->activity) {
                    $html .= '<small class="text-muted">' . e($harvest->producedItem->activity->name) . '</small>';
                }
                return $html;
            })
            ->addColumn('campaign_col', function (ProductionHarvest $harvest) {
                $html = '<div class="small fw-semibold">' . e($harvest->campaign->name ?? '-') . '</div>';
                if ($harvest->batch) {
                    $html .= '<small class="badge bg-light text-primary border font-monospace">' . e($harvest->batch->batch_code) . '</small>';
                }
                return $html;
            })
            ->addColumn('quantity_col', function (ProductionHarvest $harvest) {
                return '<span class="fw-bold fs-6 text-success">' . number_format($harvest->quantity, 2) . '</span> <small class="text-muted">' . e($harvest->producedItem->unit_of_measurement ?? '') . '</small>';
            })
            ->addColumn('grade_col', function (ProductionHarvest $harvest) {
                return '<span class="badge bg-secondary">' . e($harvest->quality_grade) . '</span>';
            })
            ->addColumn('warehouse_col', function (ProductionHarvest $harvest) {
                return $harvest->warehouse ? '<span class="small text-muted"><i class="fas fa-warehouse me-1"></i>' . e($harvest->warehouse->descripcion) . '</span>' : '<span class="text-muted small">Sin almacén</span>';
            })
            ->addColumn('actions', function (ProductionHarvest $harvest) {
                return '
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-harvest" data-id="' . $harvest->id . '">
                        <i class="fas fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['date_col', 'item_col', 'campaign_col', 'quantity_col', 'grade_col', 'warehouse_col', 'actions'])
            ->make(true);
    }

    public function store(ProductionHarvestValidate $request): RedirectResponse
    {
        $data = $request->validated();
        $data['registered_by_user_id'] = Auth::id() ?? 1;

        $harvest = ProductionHarvest::create($data);

        // Si el producto está publicado en el catálogo central y se seleccionó un almacén, actualizar el stock
        $producedItem = ProducedItem::find($data['produced_item_id']);
        if ($producedItem && $producedItem->product_id && !empty($data['warehouse_id'])) {
            $stock = StockProduct::firstOrCreate(
                [
                    'idalmacen'  => $data['warehouse_id'],
                    'idproducto' => $producedItem->product_id,
                ],
                [
                    'stock_actual'  => 0,
                    'stock_minimo'  => 0,
                    'precio_compra' => $data['unit_cost_calculated'] ?? 0,
                    'precio_venta'  => $producedItem->standard_cost ?? 0,
                    'fecha_registro'=> now(),
                ]
            );

            $stock->increment('stock_actual', $data['quantity']);
            $harvest->update(['stock_product_id' => $stock->id]);
        }

        return redirect()->back()->with('success', 'Registro de cosecha / producción guardado correctamente.');
    }

    public function delete(Request $request): JsonResponse
    {
        $harvest = ProductionHarvest::findOrFail($request->input('id'));
        $harvest->delete();

        return response()->json(['success' => true, 'message' => 'Cosecha eliminada correctamente.']);
    }
}
