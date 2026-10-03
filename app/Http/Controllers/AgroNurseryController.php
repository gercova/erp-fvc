<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgriculturalNurseryValidate;
use App\Models\AgriculturalNursery;
use App\Models\ProductiveActivity;
use App\Models\ProductionBatch;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgroNurseryController extends Controller
{
    public function index(Request $request): View {
        $activities         = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id');
        $batches            = ProductionBatch::where('status', '!=', 'completed')->orderBy('batch_code')->get();
        $users              = User::orderBy('nombres')->get();
        $queryBase          = AgriculturalNursery::query();
        if ($selectedActivityId) {
            $queryBase->where('productive_activity_id', $selectedActivityId);
        }

        $totalNurseries        = (clone $queryBase)->count();
        $totalSeedlingsCurrent = (clone $queryBase)->sum('current_quantity');
        $readyForFieldCount    = (clone $queryBase)->where('stage', 'READY_FOR_FIELD')->sum('current_quantity');
        $avgSurvivalRate       = (clone $queryBase)->avg('survival_rate') ?? 100.00;
        return view('admin.agrolivestock.nurseries.index', compact(
            'activities',
            'selectedActivityId',
            'batches',
            'users',
            'totalNurseries',
            'totalSeedlingsCurrent',
            'readyForFieldCount',
            'avgSurvivalRate'
        ));
    }

    public function get(Request $request): JsonResponse {
        $query = AgriculturalNursery::with(['activity', 'batch', 'inChargeUser']);

        if ($request->filled('activity_id')) {
            $query->where('productive_activity_id', $request->input('activity_id'));
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->input('stage'));
        }

        if ($request->filled('search_term')) {
            $term = trim($request->input('search_term'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('species_name', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%");
            });
        }

        $query->orderBy('name');

        return datatables()->of($query)
            ->addColumn('species_col', function (AgriculturalNursery $nursery) {
                $html = '<div class="fw-bold text-dark"><i class="fas fa-seedling text-success me-1"></i>' . e($nursery->species_name) . '</div>';
                $html .= '<small class="text-muted">' . e($nursery->name) . '</small>';
                return $html;
            })
            ->addColumn('activity_col', function (AgriculturalNursery $nursery) {
                return '<span class="badge bg-light text-dark border">' . e($nursery->activity->name ?? '-') . '</span>';
            })
            ->addColumn('quantities_col', function (AgriculturalNursery $nursery) {
                $html = '<div><strong class="text-primary">' . number_format($nursery->current_quantity) . ' plántulas</strong></div>';
                $html .= '<small class="text-muted">Inicial: ' . number_format($nursery->initial_quantity) . ' | Superv: ' . number_format($nursery->survival_rate, 1) . '%</small>';
                return $html;
            })
            ->addColumn('stage_col', function (AgriculturalNursery $nursery) {
                return $nursery->stage_badge;
            })
            ->addColumn('dates_col', function (AgriculturalNursery $nursery) {
                $sowing = $nursery->sowing_date ? $nursery->sowing_date->format('d/m/Y') : '-';
                $dispatch = $nursery->estimated_dispatch_date ? $nursery->estimated_dispatch_date->format('d/m/Y') : 'Por definir';
                return '<small class="d-block text-muted">Siembra: ' . $sowing . '</small><small class="d-block text-muted">Despacho: ' . $dispatch . '</small>';
            })
            ->addColumn('in_charge_col', function (AgriculturalNursery $nursery) {
                return '<small class="text-dark">' . e($nursery->inChargeUser->nombres ?? 'No asignado') . '</small>';
            })
            ->addColumn('actions', function (AgriculturalNursery $nursery) {
                return '
                    <div class="d-flex justify-content-end gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-nursery"
                            data-id="' . $nursery->id . '"
                            data-name="' . e($nursery->name) . '"
                            data-activity="' . $nursery->productive_activity_id . '"
                            data-batch="' . $nursery->production_batch_id . '"
                            data-location="' . e($nursery->location) . '"
                            data-species="' . e($nursery->species_name) . '"
                            data-stage="' . $nursery->stage . '"
                            data-initial="' . $nursery->initial_quantity . '"
                            data-current="' . $nursery->current_quantity . '"
                            data-sowing="' . ($nursery->sowing_date ? $nursery->sowing_date->format('Y-m-d') : '') . '"
                            data-dispatch="' . ($nursery->estimated_dispatch_date ? $nursery->estimated_dispatch_date->format('Y-m-d') : '') . '"
                            data-user="' . $nursery->in_charge_user_id . '"
                            data-notes="' . e($nursery->notes) . '"
                            title="Editar Vivero">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-nursery" data-id="' . $nursery->id . '" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['species_col', 'activity_col', 'quantities_col', 'stage_col', 'dates_col', 'in_charge_col', 'actions'])
            ->make(true);
    }

    public function store(AgriculturalNurseryValidate $request): RedirectResponse {
        $data = $request->validated();
        if ($data['initial_quantity'] > 0) {
            $data['survival_rate'] = round(($data['current_quantity'] / $data['initial_quantity']) * 100, 2);
        }

        AgriculturalNursery::create($data);
        return redirect()->back()->with('success', 'Vivero registrado exitosamente.');
    }

    public function update(AgriculturalNurseryValidate $request, int $id): RedirectResponse {
        $nursery    = AgriculturalNursery::findOrFail($id);
        $data       = $request->validated();
        if ($data['initial_quantity'] > 0) {
            $data['survival_rate'] = round(($data['current_quantity'] / $data['initial_quantity']) * 100, 2);
        }

        $nursery->update($data);

        return redirect()->back()->with('success', 'Vivero actualizado exitosamente.');
    }

    public function delete(Request $request): JsonResponse {
        $nursery = AgriculturalNursery::findOrFail($request->input('id'));
        $nursery->delete();
        return response()->json(['success' => true, 'message' => 'Vivero eliminado correctamente.']);
    }
}
