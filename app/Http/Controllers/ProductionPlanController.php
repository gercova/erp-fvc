<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductionPlanValidate;
use App\Models\ProductiveActivity;
use App\Models\ProductionCampaign;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionPlanController extends Controller
{
    public function index(Request $request): View
    {
        $activities = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id');

        $query = ProductionCampaign::query();
        if ($selectedActivityId) {
            $query->where('productive_activity_id', $selectedActivityId);
        }

        $totalPlans = (clone $query)->count();
        $plannedCount = (clone $query)->where('status', 'planned')->count();
        $inProgressCount = (clone $query)->where('status', 'in_progress')->count();
        $closedCount = (clone $query)->where('status', 'closed')->count();

        return view('admin.production.plans.index', compact(
            'activities',
            'selectedActivityId',
            'totalPlans',
            'plannedCount',
            'inProgressCount',
            'closedCount'
        ));
    }

    public function get(Request $request): JsonResponse
    {
        $query = ProductionCampaign::with(['activity', 'creator', 'batches']);

        if ($request->filled('activity_id')) {
            $query->where('productive_activity_id', $request->input('activity_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search_term')) {
            $term = trim($request->input('search_term'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('campaign_code', 'like', "%{$term}%")
                  ->orWhere('production_targets', 'like', "%{$term}%");
            });
        }

        $query->orderBy('created_at', 'desc');

        return datatables()->of($query)
            ->addColumn('code_col', function (ProductionCampaign $campaign) {
                return '<span class="font-monospace fw-bold text-primary">' . e($campaign->campaign_code) . '</span>';
            })
            ->addColumn('activity_col', function (ProductionCampaign $campaign) {
                return '<span class="badge bg-light text-dark border">' . e($campaign->activity->name ?? 'General') . '</span>';
            })
            ->addColumn('name_col', function (ProductionCampaign $campaign) {
                $html = '<div class="fw-semibold text-dark">' . e($campaign->name) . '</div>';
                if ($campaign->production_targets) {
                    $html .= '<small class="text-muted text-truncate d-block" style="max-width: 250px;">' . e($campaign->production_targets) . '</small>';
                }
                return $html;
            })
            ->addColumn('period_col', function (ProductionCampaign $campaign) {
                $start = $campaign->start_date ? $campaign->start_date->format('d/m/Y') : '-';
                $end = $campaign->end_date ? $campaign->end_date->format('d/m/Y') : 'Abierto';
                return '<small class="text-muted"><i class="far fa-calendar-alt me-1"></i>' . $start . ' al ' . $end . '</small>';
            })
            ->addColumn('target_col', function (ProductionCampaign $campaign) {
                return '<span class="fw-bold">' . number_format($campaign->target_quantity, 2) . '</span> <small class="text-muted">' . e($campaign->target_unit) . '</small>';
            })
            ->addColumn('status_col', function (ProductionCampaign $campaign) {
                return $campaign->status_badge;
            })
            ->addColumn('batches_count', function (ProductionCampaign $campaign) {
                return '<span class="badge bg-secondary">' . $campaign->batches->count() . ' fases</span>';
            })
            ->addColumn('actions', function (ProductionCampaign $campaign) {
                return '
                    <div class="d-flex justify-content-end gap-1">
                        <a href="' . route('production.plans.show', $campaign->id) . '" class="btn btn-sm btn-outline-primary" title="Ver Detalle y Fases">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="' . route('production.plans.edit', $campaign->id) . '" class="btn btn-sm btn-outline-secondary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-plan" data-id="' . $campaign->id . '" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['code_col', 'activity_col', 'name_col', 'period_col', 'target_col', 'status_col', 'batches_count', 'actions'])
            ->make(true);
    }

    public function create(Request $request): View
    {
        $activities = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id', $activities->first()?->id);

        return view('admin.production.plans.create', compact('activities', 'selectedActivityId'));
    }

    public function store(ProductionPlanValidate $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by_user_id'] = Auth::id() ?? 1;

        $campaign = ProductionCampaign::create($data);

        return redirect()->route('production.plans.show', $campaign->id)
            ->with('success', 'Plan de producción y campaña creado exitosamente.');
    }

    public function show($id): View
    {
        $campaign = ProductionCampaign::with(['activity', 'creator', 'batches.laborCosts', 'inputMovements.rawMaterial', 'harvests.producedItem'])
            ->findOrFail($id);

        $totalLaborCost = $campaign->batches->flatMap->laborCosts->sum('labor_cost');
        $totalInputCost = $campaign->inputMovements->sum('total_cost');
        $totalCost = $totalLaborCost + $totalInputCost;

        $totalHarvestQuantity = $campaign->harvests->sum('quantity');

        return view('admin.production.plans.show', compact(
            'campaign',
            'totalLaborCost',
            'totalInputCost',
            'totalCost',
            'totalHarvestQuantity'
        ));
    }

    public function edit($id): View
    {
        $campaign = ProductionCampaign::findOrFail($id);
        $activities = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();

        return view('admin.production.plans.edit', compact('campaign', 'activities'));
    }

    public function update(ProductionPlanValidate $request, $id): RedirectResponse
    {
        $campaign = ProductionCampaign::findOrFail($id);
        $campaign->update($request->validated());

        return redirect()->route('production.plans.show', $campaign->id)
            ->with('success', 'Plan de producción actualizado correctamente.');
    }

    public function delete(Request $request): JsonResponse
    {
        $campaign = ProductionCampaign::findOrFail($request->input('id'));
        $campaign->delete();

        return response()->json(['success' => true, 'message' => 'Campaña eliminada correctamente.']);
    }
}
