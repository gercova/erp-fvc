<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgriculturalPlantationValidate;
use App\Http\Requests\AgriculturalPlotValidate;
use App\Models\AgriculturalPlantation;
use App\Models\AgriculturalPlot;
use App\Models\ProductiveActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgroPlotController extends Controller
{
    public function index(Request $request): View {
        $activities         = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id');
        $queryBase          = AgriculturalPlot::query();
        if ($selectedActivityId) {
            $queryBase->where('productive_activity_id', $selectedActivityId);
        }

        $totalPlots     = (clone $queryBase)->count();
        $totalHectares  = (clone $queryBase)->sum('area_hectares');
        $activeHectares = (clone $queryBase)->where('status', 'IN_PRODUCTION')->sum('area_hectares');
        $fallowPlots    = (clone $queryBase)->where('status', 'FALLOW_REST')->count();

        return view('admin.agrolivestock.plots.index', compact(
            'activities',
            'selectedActivityId',
            'totalPlots',
            'totalHectares',
            'activeHectares',
            'fallowPlots'
        ));
    }

    public function get(Request $request): JsonResponse {
        $query = AgriculturalPlot::with(['activity', 'plantations']);

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
                  ->orWhere('code', 'like', "%{$term}%")
                  ->orWhere('sector_location', 'like', "%{$term}%");
            });
        }

        $query->orderBy('name');

        return datatables()->of($query)
            ->addColumn('code_col', function (AgriculturalPlot $plot) {
                return '<span class="font-monospace fw-bold text-primary">' . e($plot->code) . '</span>';
            })
            ->addColumn('name_col', function (AgriculturalPlot $plot) {
                $html = '<div class="fw-bold text-dark">' . e($plot->name) . '</div>';
                $html .= '<small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>' . e($plot->sector_location) . '</small>';
                return $html;
            })
            ->addColumn('activity_col', function (AgriculturalPlot $plot) {
                return '<span class="badge bg-light text-dark border">' . e($plot->activity->name ?? '-') . '</span>';
            })
            ->addColumn('area_col', function (AgriculturalPlot $plot) {
                return '<span class="fw-bold text-dark">' . number_format($plot->area_hectares, 2) . ' ha</span>';
            })
            ->addColumn('plantations_col', function (AgriculturalPlot $plot) {
                $count = $plot->plantations->count();
                if ($count === 0) {
                    return '<span class="text-muted small">Sin cultivos registrados</span>';
                }
                $list = $plot->plantations->take(2)->map(function ($p) {
                    return e($p->crop_species) . ' (' . e($p->variety ?? 'Genérico') . ')';
                })->implode(', ');
                if ($count > 2) {
                    $list .= ' y +' . ($count - 2) . ' más';
                }
                return '<small class="text-dark fw-semibold">' . $list . '</small>';
            })
            ->addColumn('status_col', function (AgriculturalPlot $plot) {
                return $plot->status_badge;
            })
            ->addColumn('actions', function (AgriculturalPlot $plot) {
                return '
                    <div class="d-flex justify-content-end gap-1">
                        <a href="' . route('agrolivestock.plots.show', $plot->id) . '" class="btn btn-sm btn-outline-primary" title="Ver Detalles y Cultivos">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-plot"
                            data-id="' . $plot->id . '"
                            data-code="' . e($plot->code) . '"
                            data-name="' . e($plot->name) . '"
                            data-activity="' . $plot->productive_activity_id . '"
                            data-location="' . e($plot->sector_location) . '"
                            data-area="' . $plot->area_hectares . '"
                            data-topography="' . e($plot->topography) . '"
                            data-soil="' . e($plot->soil_type) . '"
                            data-status="' . $plot->status . '"
                            data-notes="' . e($plot->notes) . '"
                            title="Editar Parcela">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-plot" data-id="' . $plot->id . '" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['code_col', 'name_col', 'activity_col', 'area_col', 'plantations_col', 'status_col', 'actions'])
            ->make(true);
    }

    public function show(int $id): View {
        $plot = AgriculturalPlot::with(['activity', 'plantations', 'harvests.producedItem', 'yieldLogs'])->findOrFail($id);

        $totalHarvestedTon  = $plot->harvests->sum('quantity');
        $plantationsCount   = $plot->plantations->count();
        $yieldLogsCount     = $plot->yieldLogs->count();

        return view('admin.agrolivestock.plots.show', compact(
            'plot',
            'totalHarvestedTon',
            'plantationsCount',
            'yieldLogsCount'
        ));
    }

    public function store(AgriculturalPlotValidate $request): RedirectResponse {
        AgriculturalPlot::create($request->validated());
        return redirect()->back()->with('success', 'Parcela agrícola registrada exitosamente.');
    }

    public function update(AgriculturalPlotValidate $request, int $id): RedirectResponse {
        $plot = AgriculturalPlot::findOrFail($id);
        $plot->update($request->validated());
        return redirect()->back()->with('success', 'Parcela agrícola actualizada exitosamente.');
    }

    public function delete(Request $request): JsonResponse {
        $plot = AgriculturalPlot::findOrFail($request->input('id'));
        $plot->delete();
        return response()->json(['success' => true, 'message' => 'Parcela eliminada correctamente.']);
    }

    public function storePlantation(AgriculturalPlantationValidate $request): RedirectResponse {
        AgriculturalPlantation::create($request->validated());
        return redirect()->back()->with('success', 'Cultivo / Plantación registrada en la parcela.');
    }

    public function deletePlantation(Request $request): JsonResponse {
        $plantation = AgriculturalPlantation::findOrFail($request->input('id'));
        $plantation->delete();
        return response()->json(['success' => true, 'message' => 'Cultivo / Plantación eliminada de la parcela.']);
    }
}
