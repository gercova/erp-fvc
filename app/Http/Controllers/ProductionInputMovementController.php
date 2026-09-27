<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductionInputMovementValidate;
use App\Models\Buy;
use App\Models\DetailBuy;
use App\Models\ProductiveActivity;
use App\Models\ProductionCampaign;
use App\Models\ProductionInputMovement;
use App\Models\ProductionRawMaterial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionInputMovementController extends Controller
{
    public function index(Request $request): View
    {
        $activities = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id', $activities->first()?->id);

        $rawMaterials = ProductionRawMaterial::where('is_active', true)->orderBy('name')->get();
        $campaigns = ProductionCampaign::where('status', '!=', 'closed')
            ->when($selectedActivityId, fn($q) => $q->where('productive_activity_id', $selectedActivityId))
            ->orderBy('name')
            ->get();

        // Compras recientes para enlace opcional
        $recentBuys = Buy::with(['detailBuys.product'])->orderBy('fecha_emision', 'desc')->take(20)->get();

        // Métricas filtradas por proyecto/actividad
        $queryBase = ProductionInputMovement::query();
        if ($selectedActivityId) {
            $queryBase->where('productive_activity_id', $selectedActivityId);
        }

        $totalInflowCost = (clone $queryBase)->where('movement_type', 'inflow_purchase')->sum('total_cost');
        $totalOutflowCost = (clone $queryBase)->where('movement_type', 'outflow_consumption')->sum('total_cost');
        $movementsCount = (clone $queryBase)->count();

        return view('admin.production.input_movements.index', compact(
            'activities',
            'selectedActivityId',
            'rawMaterials',
            'campaigns',
            'recentBuys',
            'totalInflowCost',
            'totalOutflowCost',
            'movementsCount'
        ));
    }

    public function get(Request $request): JsonResponse
    {
        $query = ProductionInputMovement::with([
            'rawMaterial',
            'activity',
            'campaign',
            'batch',
            'buy',
            'registeredByUser'
        ]);

        if ($request->filled('activity_id')) {
            $query->where('productive_activity_id', $request->input('activity_id'));
        }

        if ($request->filled('campaign_id')) {
            $query->where('production_campaign_id', $request->input('campaign_id'));
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->input('movement_type'));
        }

        if ($request->filled('search_term')) {
            $term = trim($request->input('search_term'));
            $query->where(function ($q) use ($term) {
                $q->whereHas('rawMaterial', fn($m) => $m->where('name', 'like', "%{$term}%"))
                  ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        $query->orderBy('movement_date', 'desc');

        return datatables()->of($query)
            ->addColumn('date_col', function (ProductionInputMovement $mov) {
                return '<span class="font-monospace">' . $mov->movement_date->format('d/m/Y') . '</span>';
            })
            ->addColumn('type_col', function (ProductionInputMovement $mov) {
                return $mov->movement_type_badge;
            })
            ->addColumn('material_col', function (ProductionInputMovement $mov) {
                $html = '<div class="fw-bold text-dark">' . e($mov->rawMaterial->name ?? 'Insumo') . '</div>';
                if ($mov->rawMaterial) {
                    $html .= '<small class="text-muted">' . e($mov->rawMaterial->category_label) . '</small>';
                }
                return $html;
            })
            ->addColumn('activity_col', function (ProductionInputMovement $mov) {
                $html = '<span class="badge bg-light text-dark border">' . e($mov->activity->name ?? '-') . '</span>';
                if ($mov->campaign) {
                    $html .= '<div class="small text-muted mt-1">' . e($mov->campaign->name) . '</div>';
                }
                if ($mov->batch) {
                    $html .= '<div class="small text-primary font-monospace">' . e($mov->batch->batch_code) . '</div>';
                }
                return $html;
            })
            ->addColumn('quantity_col', function (ProductionInputMovement $mov) {
                return '<span class="fw-bold">' . number_format($mov->quantity, 2) . '</span> <small class="text-muted">' . e($mov->rawMaterial->unit_of_measurement ?? '') . '</small>';
            })
            ->addColumn('unit_cost_col', function (ProductionInputMovement $mov) {
                return 'S/ ' . number_format($mov->unit_cost, 2);
            })
            ->addColumn('total_cost_col', function (ProductionInputMovement $mov) {
                return '<span class="fw-bold text-dark">S/ ' . number_format($mov->total_cost, 2) . '</span>';
            })
            ->addColumn('source_col', function (ProductionInputMovement $mov) {
                if ($mov->buy_id) {
                    return '<span class="badge bg-info text-white"><i class="fas fa-truck me-1"></i>Compra #' . $mov->buy_id . '</span>';
                }
                return '<span class="text-muted small">Directo / Campo</span>';
            })
            ->addColumn('actions', function (ProductionInputMovement $mov) {
                return '
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-movement" data-id="' . $mov->id . '" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['date_col', 'type_col', 'material_col', 'activity_col', 'quantity_col', 'unit_cost_col', 'total_cost_col', 'source_col', 'actions'])
            ->make(true);
    }

    public function store(ProductionInputMovementValidate $request): RedirectResponse
    {
        $data = $request->validated();
        $data['registered_by_user_id'] = Auth::id() ?? 1;

        if (empty($data['total_cost']) && isset($data['quantity'], $data['unit_cost'])) {
            $data['total_cost'] = round($data['quantity'] * $data['unit_cost'], 2);
        }

        ProductionInputMovement::create($data);

        return redirect()->back()->with('success', 'Movimiento de insumo registrado exitosamente.');
    }

    public function delete(Request $request): JsonResponse
    {
        $movement = ProductionInputMovement::findOrFail($request->input('id'));
        $movement->delete();

        return response()->json(['success' => true, 'message' => 'Movimiento de insumo eliminado correctamente.']);
    }
}
