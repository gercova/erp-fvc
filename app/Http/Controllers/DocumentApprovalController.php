<?php

namespace App\Http\Controllers;

use App\Models\DocumentApproval;
use App\Models\ExitSlip;
use App\Models\ExpenseDeclaration;
use App\Models\FuelControlSlip;
use App\Models\Requisition;
use App\Models\User;
use App\Models\VacationExitSlip;
use App\Models\VehicleExitSlip;
use App\Services\DocumentApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentApprovalController extends Controller
{
    protected DocumentApprovalService $approvalService;

    public function __construct(DocumentApprovalService $approvalService) {
        $this->approvalService = $approvalService;
    }

    public function index(): View {
        $user           = Auth::user();
        $userRoles      = $user->roles->pluck('name')->toArray();
        $isSuper        = in_array('SUPERADMIN', $userRoles) || in_array('ADMIN', $userRoles);
        $pendingCount   = $user->pendingApprovalsCount();
        $signedCount    = DocumentApproval::query()
            ->where('status', 'APROBADO')
            ->where('approver_id', $user->id)
            ->count();

        return view('admin.approvals.index', compact('pendingCount', 'signedCount', 'isSuper'));
    }

    public function get(Request $request): JsonResponse {
        $user       = Auth::user();
        $userRoles  = $user->roles->pluck('name')->toArray();
        $isSuper    = in_array('SUPERADMIN', $userRoles) || in_array('ADMIN', $userRoles);
        $scope      = $request->input('filter_scope', 'pending');

        $query = DocumentApproval::with(['document', 'approver'])
            ->when($request->filled('filter_type'), function ($q) use ($request) {
                $type = $request->input('filter_type');
                $classMap = [
                    'requisition'           => Requisition::class,
                    'expense_declaration'   => ExpenseDeclaration::class,
                    'exit_slip'             => ExitSlip::class,
                    'vehicle_exit_slip'     => VehicleExitSlip::class,
                    'vacation_exit_slip'    => VacationExitSlip::class,
                    'fuel_control_slip'     => FuelControlSlip::class,
                ];
                if (isset($classMap[$type])) {
                    $q->where('document_type', $classMap[$type]);
                }
            })
            ->when($request->filled('filter_search'), function ($q) use ($request) {
                $search = trim($request->input('filter_search'));
                $q->where(function ($sub) use ($search) {
                    $sub->where('label', 'like', "%{$search}%")
                        ->orWhere('role_name', 'like', "%{$search}%")
                        ->orWhere('approver_name', 'like', "%{$search}%");
                });
            });

        if ($scope === 'pending') {
            $query->where('status', 'PENDIENTE')
                ->where(function ($q) use ($user, $userRoles, $isSuper) {
                    $q->where('approver_id', $user->id);
                    if (!empty($userRoles)) {
                        $q->orWhere(function ($sub) use ($userRoles) {
                            $sub->whereNull('approver_id')->whereIn('role_name', $userRoles);
                        });
                    }
                    if ($isSuper) {
                        $q->orWhereNull('approver_id');
                    }
                });
        } elseif ($scope === 'signed') {
            $query->where('status', 'APROBADO')->where('approver_id', $user->id);
        }

        $query->orderByDesc('id');

        return datatables()->of($query)
            ->addColumn('tipo_documento', function ($approval) {
                $type = class_basename($approval->document_type);
                $types = [
                    'Requisition'           => '<span class="badge bg-primary">Requerimiento</span>',
                    'ExpenseDeclaration'    => '<span class="badge bg-success">Declaración Jurada</span>',
                    'ExitSlip'              => '<span class="badge bg-info text-dark">Papeleta de Salida</span>',
                    'VehicleExitSlip'       => '<span class="badge bg-warning text-dark">Papeleta Vehículo</span>',
                    'VacationExitSlip'      => '<span class="badge bg-purple text-white" style="background:#6f42c1;">Papeleta Vacaciones</span>',
                    'FuelControlSlip'       => '<span class="badge bg-danger">Vale Combustible</span>',
                ];
                return $types[$type] ?? '<span class="badge bg-secondary">' . e($type) . '</span>';
            })
            ->addColumn('documento_correlativo', function ($approval) {
                $doc = $approval->document;
                return $doc ? '<span class="fw-bold">' . e($doc->correlativo ?? "ID: {$doc->id}") . '</span>' : '-';
            })
            ->addColumn('solicitante', function ($approval) {
                $doc = $approval->document;
                if (!$doc) return '-';
                $name = $doc->de ?? $doc->servidor_nombres ?? $doc->nombres_apellidos ?? $doc->solicitante_nombre ?? $doc->apellidos_nombres ?? ($doc->user?->nombres ?? '-');
                return '<div class="fw-semibold">' . e($name) . '</div>';
            })
            ->editColumn('label', function ($approval) {
                return '<div><div class="fw-bold">' . e($approval->label) . '</div><small class="text-muted">Paso ' . e($approval->step_order) . '</small></div>';
            })
            ->editColumn('status', function ($approval) {
                return match ($approval->status) {
                    'APROBADO'  => '<span class="badge bg-success">Firmado</span>',
                    'PENDIENTE' => '<span class="badge bg-warning text-dark">Pendiente</span>',
                    'OBSERVADO' => '<span class="badge bg-secondary">Observado</span>',
                    'RECHAZADO' => '<span class="badge bg-danger">Rechazado</span>',
                    default     => '<span class="badge bg-light text-dark">' . e($approval->status) . '</span>',
                };
            })
            ->addColumn('fecha', function ($approval) {
                return $approval->signed_at ? $approval->signed_at->format('d/m/Y H:i') : ($approval->created_at ? $approval->created_at->format('d/m/Y') : '-');
            })
            ->addColumn('acciones', function ($approval) {
                $showUrl = route('approvals.show', $approval->id);
                return '<a href="' . $showUrl . '" class="btn btn-sm btn-primary"><i class="fas fa-file-signature me-1"></i> Revisar y Firmar</a>';
            })
            ->rawColumns(['tipo_documento', 'documento_correlativo', 'solicitante', 'label', 'status', 'acciones'])
            ->make(true);
    }

    public function show(int $id): View {
        $approval   = DocumentApproval::with(['approver'])->findOrFail($id);
        $document   = $approval->document;
        $user       = Auth::user();
        $userRoles  = $user->roles->pluck('name')->toArray();
        $isSuper    = in_array('SUPERADMIN', $userRoles) || in_array('ADMIN', $userRoles);

        // Can this user sign this specific approval?
        $canSign = $approval->status === 'PENDIENTE' && (
            $approval->approver_id === $user->id ||
            in_array($approval->role_name, $userRoles) ||
            $isSuper
        );

        // Load all steps for this document
        $allSteps = DocumentApproval::where('document_type', $approval->document_type)
            ->where('document_id', $approval->document_id)
            ->orderBy('step_order')
            ->get();

        $pdfRouteName = match (class_basename($approval->document_type)) {
            'Requisition'           => 'requisitions.pdf',
            'ExpenseDeclaration'    => 'expense-declarations.pdf',
            'ExitSlip'              => 'exit-slips.pdf',
            'VehicleExitSlip'       => 'vehicle-exit-slips.pdf',
            'VacationExitSlip'      => 'vacation-exit-slips.pdf',
            'FuelControlSlip'       => 'fuel-control-slips.pdf',
            default                 => null,
        };

        $pdfUrl = $pdfRouteName ? route($pdfRouteName, $approval->document_id) : '#';

        return view('admin.approvals.show', compact('approval', 'document', 'canSign', 'allSteps', 'pdfUrl', 'user'));
    }

    public function approve(Request $request): JsonResponse {
        $approvalId = $request->input('approval_id');
        $approval   = DocumentApproval::findOrFail($approvalId);
        $user       = Auth::user();
        $userRoles  = $user->roles->pluck('name')->toArray();
        $isSuper    = in_array('SUPERADMIN', $userRoles) || in_array('ADMIN', $userRoles);

        if ($approval->status !== 'PENDIENTE') {
            return response()->json([
                'type'      => 'error', 
                'message'   => 'Este paso ya no se encuentra pendiente de firma.'], 400);
        }

        if ($approval->approver_id !== $user->id && !in_array($approval->role_name, $userRoles) && !$isSuper) {
            return response()->json([
                'type'      => 'error', 
                'message'   => 'No cuenta con permisos para firmar este documento.'
            ], 403);
        }

        $signatureData = $request->input('signature_data', $user->firma_digital);
        $observations  = $request->input('observations');

        $this->approvalService->signAndApprove($approval, $user, $signatureData, $observations);

        return response()->json([
            'type'      => 'success',
            'message'   => 'Documento firmado y aprobado correctamente.',
            'redirect'  => route('approvals.index'),
        ]);
    }

    public function reject(Request $request): JsonResponse {
        $request->validate([
            'approval_id'   => 'required|exists:document_approvals,id',
            'status'        => 'required|in:OBSERVADO,RECHAZADO',
            'observations'  => 'required|string|min:5|max:1000',
        ]);

        $approval   = DocumentApproval::findOrFail($request->input('approval_id'));
        $user       = Auth::user();
        $userRoles  = $user->roles->pluck('name')->toArray();
        $isSuper    = in_array('SUPERADMIN', $userRoles) || in_array('ADMIN', $userRoles);

        if ($approval->status !== 'PENDIENTE') {
            return response()->json([
                'type'      => 'error', 
                'message'   => 'Este paso ya fue procesado.'
            ], 400);
        }

        if ($approval->approver_id !== $user->id && !in_array($approval->role_name, $userRoles) && !$isSuper) {
            return response()->json([
                'type'      => 'error', 
                'message'   => 'No tiene permisos para observar o rechazar este documento.'
            ], 403);
        }

        $status         = $request->input('status');
        $observations   = $request->input('observations');

        $this->approvalService->observeOrReject($approval, $user, $status, $observations);

        return response()->json([
            'type'      => 'success',
            'message'   => "El documento ha sido marcado como {$status} con éxito.",
            'redirect'  => route('approvals.index'),
        ]);
    }
}
