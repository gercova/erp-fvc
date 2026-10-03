<?php

namespace App\Http\Controllers;

use App\Http\Requests\LivestockEventValidate;
use App\Http\Requests\LivestockUnitValidate;
use App\Models\LivestockEvent;
use App\Models\LivestockUnit;
use App\Models\ProductiveActivity;
use App\Models\ProductiveUnit;
use App\Models\ProductionInputMovement;
use App\Models\ProductionRawMaterial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgroLivestockController extends Controller
{
    public function index(Request $request): View {
        $activities         = ProductiveActivity::where('status', 'ACTIVA')->orderBy('order_index')->get();
        $selectedActivityId = $request->input('activity_id');
        $selectedSpecies    = $request->input('species');

        $rawMaterials       = ProductionRawMaterial::where('is_active', true)->orderBy('name')->get();
        $productiveUnits    = ProductiveUnit::where('status', 'ACTIVE')->orderBy('name')->get();

        $queryBase = LivestockUnit::query();
        if ($selectedActivityId) {
            $queryBase->where('productive_activity_id', $selectedActivityId);
        }
        if ($selectedSpecies) {
            $queryBase->where('species', $selectedSpecies);
        }

        $totalUnits         = (clone $queryBase)->count();
        $totalHeadsActive   = (clone $queryBase)->where('status', 'ACTIVE')->sum('batch_head_count');
        $cattleCount        = (clone $queryBase)->where('species', 'CATTLE')->where('status', 'ACTIVE')->sum('batch_head_count');
        $pigsCount          = (clone $queryBase)->where('species', 'PIG')->where('status', 'ACTIVE')->sum('batch_head_count');
        $guineaPigsCount    = (clone $queryBase)->where('species', 'GUINEA_PIG')->where('status', 'ACTIVE')->sum('batch_head_count');
        $poultryCount       = (clone $queryBase)->where('species', 'POULTRY')->where('status', 'ACTIVE')->sum('batch_head_count');

        return view('admin.agrolivestock.livestock.index', compact(
            'activities',
            'selectedActivityId',
            'selectedSpecies',
            'rawMaterials',
            'productiveUnits',
            'totalUnits',
            'totalHeadsActive',
            'cattleCount',
            'pigsCount',
            'guineaPigsCount',
            'poultryCount'
        ));
    }

    public function get(Request $request): JsonResponse {
        $query = LivestockUnit::with(['activity', 'productiveUnit']);

        if ($request->filled('activity_id')) {
            $query->where('productive_activity_id', $request->input('activity_id'));
        }

        if ($request->filled('species')) {
            $query->where('species', $request->input('species'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search_term')) {
            $term = trim($request->input('search_term'));
            $query->where(function ($q) use ($term) {
                $q->where('identifier_code', 'like', "%{$term}%")
                    ->orWhere('breed', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        $query->orderBy('birth_or_entry_date', 'desc');

        return datatables()->of($query)
            ->addColumn('identifier_col', function (LivestockUnit $unit) {
                $html = '<div class="fw-bold font-monospace text-primary">' . e($unit->identifier_code) . '</div>';
                $html .= '<small class="text-muted">' . e($unit->breed ?? 'Criollo / Común') . '</small>';
                return $html;
            })
            ->addColumn('species_col', function (LivestockUnit $unit) {
                $badgeClass = match ($unit->species) {
                    'CATTLE'     => 'bg-dark text-white',
                    'PIG'        => 'bg-pink text-white" style="background:#e83e8c;',
                    'GUINEA_PIG' => 'bg-warning text-dark',
                    'POULTRY'    => 'bg-info text-white',
                    'FISH_POND'  => 'bg-primary text-white',
                    default      => 'bg-secondary text-white',
                };
                return '<span class="badge ' . $badgeClass . '">' . e($unit->species_label) . '</span>';
            })
            ->addColumn('type_heads_col', function (LivestockUnit $unit) {
                if ($unit->tracking_type === 'BATCH') {
                    return '<strong class="text-dark">' . number_format($unit->batch_head_count) . ' animales</strong> <small class="text-muted d-block">(Lote/Poza)</small>';
                }
                return '<span class="badge bg-light text-dark border">Individual</span>';
            })
            ->addColumn('weight_col', function (LivestockUnit $unit) {
                $entry = $unit->entry_weight_kg ? number_format($unit->entry_weight_kg, 1) . ' kg' : '-';
                $current = $unit->current_weight_kg ? number_format($unit->current_weight_kg, 1) . ' kg' : '-';
                return '<div><small class="text-muted">Ingreso: ' . $entry . '</small></div><div><strong class="text-dark">Actual: ' . $current . '</strong></div>';
            })
            ->addColumn('activity_col', function (LivestockUnit $unit) {
                return '<span class="badge bg-light text-dark border">' . e($unit->activity->name ?? '-') . '</span>';
            })
            ->addColumn('status_col', function (LivestockUnit $unit) {
                return $unit->status_badge;
            })
            ->addColumn('actions', function (LivestockUnit $unit) {
                return '
                    <div class="d-flex justify-content-end gap-1">
                        <a href="' . route('agrolivestock.livestock.show', $unit->id) . '" class="btn btn-sm btn-outline-primary" title="Historial Sanitario / Eventos">
                            <i class="fas fa-notes-medical"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-unit"
                            data-id="' . $unit->id . '"
                            data-activity="' . $unit->productive_activity_id . '"
                            data-punit="' . $unit->productive_unit_id . '"
                            data-species="' . $unit->species . '"
                            data-breed="' . e($unit->breed) . '"
                            data-type="' . $unit->tracking_type . '"
                            data-code="' . e($unit->identifier_code) . '"
                            data-heads="' . $unit->batch_head_count . '"
                            data-sex="' . $unit->sex . '"
                            data-date="' . ($unit->birth_or_entry_date ? $unit->birth_or_entry_date->format('Y-m-d') : '') . '"
                            data-entry-weight="' . $unit->entry_weight_kg . '"
                            data-curr-weight="' . $unit->current_weight_kg . '"
                            data-status="' . $unit->status . '"
                            data-notes="' . e($unit->notes) . '"
                            title="Editar Unidad">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-unit" data-id="' . $unit->id . '" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['identifier_col', 'species_col', 'type_heads_col', 'weight_col', 'activity_col', 'status_col', 'actions'])
            ->make(true);
    }

    public function show(int $id): View {
        $unit           = LivestockUnit::with(['activity', 'productiveUnit', 'events.rawMaterial', 'events.recordedByUser'])->findOrFail($id);
        $rawMaterials   = ProductionRawMaterial::where('is_active', true)->orderBy('name')->get();

        $totalEventCost     = $unit->events->sum('total_cost');
        $healthEventsCount  = $unit->events->whereIn('event_type', ['HEALTH_TREATMENT', 'VACCINATION'])->count();
        $feedEventsCount    = $unit->events->where('event_type', 'FEEDING_LOG')->count();

        return view('admin.agrolivestock.livestock.show', compact(
            'unit',
            'rawMaterials',
            'totalEventCost',
            'healthEventsCount',
            'feedEventsCount'
        ));
    }

    public function store(LivestockUnitValidate $request): RedirectResponse {
        LivestockUnit::create($request->validated());
        return redirect()->back()->with('success', 'Unidad pecuaria registrada correctamente.');
    }

    public function update(LivestockUnitValidate $request, int $id): RedirectResponse {
        $unit = LivestockUnit::findOrFail($id);
        $unit->update($request->validated());

        return redirect()->back()->with('success', 'Unidad pecuaria actualizada exitosamente.');
    }

    public function delete(Request $request): JsonResponse {
        $unit = LivestockUnit::findOrFail($request->input('id'));
        $unit->delete();
        return response()->json(['success' => true, 'message' => 'Unidad pecuaria eliminada correctamente.']);
    }

    public function storeEvent(LivestockEventValidate $request): RedirectResponse {
        $data                           = $request->validated();
        $data['recorded_by_user_id']    = Auth::id() ?? 1;
        $event                          = LivestockEvent::create($data);

        // Si se vinculó a un insumo de Bloque C con cantidad y costo, registrar consumo automático en la actividad
        if (!empty($data['production_raw_material_id']) && !empty($data['input_quantity_used'])) {
            $unit = LivestockUnit::find($data['livestock_unit_id']);
            if ($unit && $unit->productive_activity_id) {
                ProductionInputMovement::create([
                    'productive_activity_id'        => $unit->productive_activity_id,
                    'production_raw_material_id'    => $data['production_raw_material_id'],
                    'movement_type'                 => 'outflow_consumption',
                    'movement_date'                 => $data['event_date'],
                    'quantity'                      => $data['input_quantity_used'],
                    'unit_cost'                     => ($data['input_quantity_used'] > 0) ? round(($data['input_cost'] ?? 0) / $data['input_quantity_used'], 2) : 0,
                    'total_cost'                    => $data['input_cost'] ?? 0,
                    'notes'                         => 'Consumo pecuario: Evento ' . $event->event_type . ' en ' . $unit->identifier_code,
                    'registered_by_user_id'         => Auth::id() ?? 1,
                ]);
            }
        }

        // Si es control de peso, actualizar el peso actual de la unidad
        if ($data['event_type'] === 'WEIGHT_CONTROL' && !empty($request->input('new_weight_kg'))) {
            $unit = LivestockUnit::find($data['livestock_unit_id']);
            if ($unit) {
                $unit->update(['current_weight_kg' => $request->input('new_weight_kg')]);
            }
        }

        return redirect()->back()->with('success', 'Evento pecuario registrado exitosamente.');
    }

    public function deleteEvent(Request $request): JsonResponse {
        $event = LivestockEvent::findOrFail($request->input('id'));
        $event->delete();
        return response()->json([
            'success' => true, 
            'message' => 'Evento pecuario eliminado correctamente.'
        ]);
    }
}
