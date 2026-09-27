<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductiveActivityValidate;
use App\Models\ActivityTrackingLog;
use App\Models\Area;
use App\Models\FundSource;
use App\Models\ProductiveActivity;
use App\Models\User;
use App\Services\DocumentApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ProductiveActivityController extends Controller
{
    protected DocumentApprovalService $approvalService;

    public function __construct(DocumentApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        $isGlobalSupervisor = $user->hasRole(['SUPERADMIN', 'ADMIN', 'DIRECTOR_GENERAL', 'ADMINISTRACION', 'JEFE_AREA', 'CONTABILIDAD']);

        // Áreas disponibles
        $areas = Area::orderBy('name')->get();
        $selectedAreaId = $request->input('area_id');
        $selectedType = $request->input('type', 'ALL');

        // Métricas rápidas
        $queryBase = ProductiveActivity::query();
        if ($selectedAreaId) {
            $queryBase->where('area_id', $selectedAreaId);
        }
        if ($selectedType && $selectedType !== 'ALL') {
            $queryBase->where('type', $selectedType);
        }

        $totalActivities = (clone $queryBase)->count();
        $activeCount = (clone $queryBase)->where('status', 'ACTIVA')->count();
        $closedCount = (clone $queryBase)->where('status', 'CERRADA')->count();
        $maintenanceCount = (clone $queryBase)->where('status', 'EN_MANTENIMIENTO')->count();
        $avgProgress = (clone $queryBase)->avg('execution_progress_percent') ?? 0;

        $types = [
            'ALL' => 'Todas las Actividades',
            'AGRICULTURAL' => 'Agrícola',
            'FORESTRY' => 'Forestal',
            'AQUACULTURE' => 'Piscícola',
            'LIVESTOCK' => 'Pecuario',
            'INSTITUTIONAL' => 'Institucional',
            'SERVICES' => 'Servicios / Idiomas'
        ];

        return view('admin.productive_activities.index', compact(
            'areas',
            'selectedAreaId',
            'selectedType',
            'types',
            'isGlobalSupervisor',
            'totalActivities',
            'activeCount',
            'closedCount',
            'maintenanceCount',
            'avgProgress'
        ));
    }

    public function get(Request $request): JsonResponse
    {
        $query = ProductiveActivity::with(['area', 'head', 'defaultFundSource', 'approvals']);

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->filled('type') && $request->type !== 'ALL') {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('type_badge', function (ProductiveActivity $act) {
                $badges = [
                    'AGRICULTURAL' => '<span class="badge bg-success text-white"><i class="fas fa-seedling me-1"></i> Agrícola</span>',
                    'FORESTRY' => '<span class="badge bg-dark text-white"><i class="fas fa-tree me-1"></i> Forestal</span>',
                    'AQUACULTURE' => '<span class="badge bg-info text-white"><i class="fas fa-fish me-1"></i> Piscícola</span>',
                    'LIVESTOCK' => '<span class="badge bg-warning text-dark"><i class="fas fa-paw me-1"></i> Pecuario</span>',
                    'INSTITUTIONAL' => '<span class="badge bg-primary text-white"><i class="fas fa-landmark me-1"></i> Institucional</span>',
                    'SERVICES' => '<span class="badge bg-secondary text-white"><i class="fas fa-concierge-bell me-1"></i> Servicios</span>',
                ];
                return $badges[$act->type] ?? '<span class="badge bg-light text-dark">' . e($act->type) . '</span>';
            })
            ->addColumn('status_badge', function (ProductiveActivity $act) {
                $badges = [
                    'ACTIVA' => '<span class="badge bg-success-soft text-success border border-success fw-bold">Activa</span>',
                    'INACTIVA' => '<span class="badge bg-secondary text-white">Inactiva</span>',
                    'EN_MANTENIMIENTO' => '<span class="badge bg-warning-soft text-warning border border-warning fw-bold">En Mantenimiento</span>',
                    'CERRADA' => '<span class="badge bg-dark text-white">Cerrada</span>',
                ];
                return $badges[$act->status] ?? '<span class="badge bg-light text-dark">' . e($act->status) . '</span>';
            })
            ->addColumn('progress_bar', function (ProductiveActivity $act) {
                $pct = (float) $act->execution_progress_percent;
                $color = $pct >= 80 ? 'bg-success' : ($pct >= 40 ? 'bg-info' : 'bg-warning');
                return '
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height: 8px;">
                            <div class="progress-bar ' . $color . '" role="progressbar" style="width: ' . $pct . '%"></div>
                        </div>
                        <span class="small fw-bold">' . $pct . '%</span>
                    </div>';
            })
            ->addColumn('approval_status_badge', function (ProductiveActivity $act) {
                if (!$act->approvals()->exists()) {
                    return '<span class="badge bg-light text-muted border">Sin Enviar</span>';
                }
                $pending = $act->currentPendingApproval();
                if ($act->isFullyApproved()) {
                    return '<span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i> Aprobado</span>';
                }
                if ($pending) {
                    return '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> ' . e($pending->label) . '</span>';
                }
                return '<span class="badge bg-secondary text-white">En Trámite</span>';
            })
            ->addColumn('action', function (ProductiveActivity $act) {
                $hasApprovals = $act->approvals()->exists();
                $btnApproval = '';
                if (!$hasApprovals) {
                    $btnApproval = '<button type="button" class="btn btn-sm btn-outline-primary btn-submit-approval" data-id="' . $act->id . '" title="Enviar a Aprobación Institucional"><i class="fas fa-file-signature"></i></button>';
                }

                return '
                    <div class="btn-group gap-1">
                        <a href="' . route('productive_activities.show', $act->id) . '" class="btn btn-sm btn-outline-info" title="Ver Detalle y Seguimiento"><i class="fas fa-eye"></i></a>
                        <a href="' . route('productive_activities.edit', $act->id) . '" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="fas fa-edit"></i></a>
                        ' . $btnApproval . '
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="' . $act->id . '" data-name="' . e($act->name) . '" title="Eliminar"><i class="fas fa-trash"></i></button>
                    </div>';
            })
            ->rawColumns(['type_badge', 'status_badge', 'progress_bar', 'approval_status_badge', 'action'])
            ->make(true);
    }

    public function create(): View
    {
        $areas = Area::orderBy('name')->get();
        $users = User::orderBy('nombres')->get();
        $fundSources = FundSource::where('is_active', true)->orderBy('name')->get();

        $types = [
            'AGRICULTURAL' => 'Agrícola (Cultivos, Frutales, Granos)',
            'FORESTRY' => 'Forestal (Viveros, Maderables, Plantaciones)',
            'AQUACULTURE' => 'Piscícola (Estanques, Alevines, Truchas/Peces)',
            'LIVESTOCK' => 'Pecuario (Vacunos, Porcinos, Cuyes, Aves)',
            'INSTITUTIONAL' => 'Institucional (Eventos, Alquileres, Central)',
            'SERVICES' => 'Servicios / Extensión (Idiomas, Talleres, Consultoría)'
        ];

        return view('admin.productive_activities.create', compact('areas', 'users', 'fundSources', 'types'));
    }

    public function store(ProductiveActivityValidate $request): RedirectResponse
    {
        $data = $request->validated();
        $activity = ProductiveActivity::create($data);

        // Si se envió observación de monitoreo o progreso inicial, registrar en tracking log
        if (!empty($data['monitoring_observations']) || ($data['execution_progress_percent'] ?? 0) > 0) {
            ActivityTrackingLog::create([
                'productive_activity_id' => $activity->id,
                'user_id' => Auth::id(),
                'log_date' => now()->toDateString(),
                'progress_percent' => $data['execution_progress_percent'] ?? 0,
                'status' => $data['status'],
                'comment' => $data['monitoring_observations'] ?: 'Registro inicial de la actividad productiva.',
            ]);
        }

        return redirect()->route('productive_activities.index')
            ->with('success', 'Actividad productiva "' . $activity->name . '" registrada exitosamente.');
    }

    public function show(int $id): View
    {
        $activity = ProductiveActivity::with([
            'area',
            'head',
            'defaultFundSource',
            'productiveUnits.inChargeUser',
            'trackingLogs.user',
            'approvals.approver'
        ])->findOrFail($id);

        $currentPending = $activity->currentPendingApproval();

        return view('admin.productive_activities.show', compact('activity', 'currentPending'));
    }

    public function edit(int $id): View
    {
        $activity = ProductiveActivity::findOrFail($id);
        $areas = Area::orderBy('name')->get();
        $users = User::orderBy('nombres')->get();
        $fundSources = FundSource::where('is_active', true)->orderBy('name')->get();

        $types = [
            'AGRICULTURAL' => 'Agrícola',
            'FORESTRY' => 'Forestal',
            'AQUACULTURE' => 'Piscícola',
            'LIVESTOCK' => 'Pecuario',
            'INSTITUTIONAL' => 'Institucional',
            'SERVICES' => 'Servicios / Idiomas'
        ];

        return view('admin.productive_activities.edit', compact('activity', 'areas', 'users', 'fundSources', 'types'));
    }

    public function update(ProductiveActivityValidate $request, int $id): RedirectResponse
    {
        $activity = ProductiveActivity::findOrFail($id);
        $data = $request->validated();
        $activity->update($data);

        return redirect()->route('productive_activities.show', $activity->id)
            ->with('success', 'Actividad productiva actualizada correctamente.');
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|exists:productive_activities,id']);
        $activity = ProductiveActivity::findOrFail($request->id);
        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Actividad productiva eliminada correctamente.'
        ]);
    }

    public function submitApproval(Request $request, int $id): JsonResponse
    {
        $activity = ProductiveActivity::findOrFail($id);

        if ($activity->approvals()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta actividad ya cuenta con un flujo de firmas en trámite.'
            ], 422);
        }

        $this->approvalService->generateWorkflow($activity, Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'La actividad ha sido enviada formalmente a la cadena jerárquica de aprobación.'
        ]);
    }

    public function storeTrackingLog(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'log_date' => 'required|date',
            'progress_percent' => 'required|numeric|min:0|max:100',
            'status' => 'required|string',
            'comment' => 'required|string|max:1000',
        ]);

        $activity = ProductiveActivity::findOrFail($id);

        $log = ActivityTrackingLog::create([
            'productive_activity_id' => $activity->id,
            'user_id' => Auth::id(),
            'log_date' => $request->log_date,
            'progress_percent' => $request->progress_percent,
            'status' => $request->status,
            'comment' => $request->comment,
        ]);

        // Actualizar el estado y % consolidado en la actividad
        $activity->update([
            'execution_progress_percent' => $request->progress_percent,
            'status' => $request->status,
            'monitoring_observations' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hito de seguimiento físico-financiero registrado.',
            'log' => $log->load('user'),
        ]);
    }
}
