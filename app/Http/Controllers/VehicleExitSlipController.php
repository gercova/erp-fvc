<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Business;
use App\Models\VehicleExitSlip;
use App\Services\DocumentApprovalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VehicleExitSlipController extends Controller
{
    protected DocumentApprovalService $approvalService;

    public function __construct(DocumentApprovalService $approvalService) {
        $this->approvalService = $approvalService;
    }

    public function index(): View {
        return view('admin.documents.vehicle_exit_slips.index');
    }

    public function get(Request $request): JsonResponse {
        $query = VehicleExitSlip::with(['user', 'area', 'approvals'])
            ->when($request->filled('filter_status'), function ($q) use ($request) {
                $q->where('status', $request->input('filter_status'));
            })
            ->when($request->filled('filter_search'), function ($q) use ($request) {
                $search = trim($request->input('filter_search'));
                $q->where(function ($sub) use ($search) {
                    $sub->where('correlativo', 'like', "%{$search}%")
                        ->orWhere('vehiculo', 'like', "%{$search}%")
                        ->orWhere('solicitante_nombre', 'like', "%{$search}%")
                        ->orWhere('chofer_nombre', 'like', "%{$search}%")
                        ->orWhere('lugar', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('filter_date'), function ($q) use ($request) {
                $q->whereDate('fecha_salida', $request->input('filter_date'));
            })
            ->orderByDesc('id');

        return datatables()->of($query)
            ->editColumn('correlativo', function ($doc) {
                return '<span class="fw-bold text-primary">' . e($doc->correlativo) . '</span>';
            })
            ->addColumn('vehiculo_info', function ($doc) {
                return '<div><div class="fw-semibold">' . e($doc->vehiculo) . '</div><small class="text-muted">Destino: ' . e($doc->lugar) . '</small></div>';
            })
            ->addColumn('personas', function ($doc) {
                return '<div><div><strong>Sol:</strong> ' . e($doc->solicitante_nombre) . '</div><small class="text-muted"><strong>Chofer:</strong> ' . e($doc->chofer_nombre) . ' (' . e($doc->brevete_numero ?: 'S/N') . ')</small></div>';
            })
            ->addColumn('horario', function ($doc) {
                $salida = ($doc->fecha_salida ? $doc->fecha_salida->format('d/m/Y') : '') . ' ' . substr((string)$doc->hora_salida, 0, 5);
                $retorno = $doc->fecha_retorno ? ($doc->fecha_retorno->format('d/m/Y') . ' ' . substr((string)$doc->hora_retorno, 0, 5)) : '-';
                return '<div class="small"><div>' . e($salida) . '</div><div>' . e($retorno) . '</div></div>';
            })
            ->addColumn('estado_badge', function ($doc) {
                return $doc->status_badge;
            })
            ->addColumn('acciones', function ($doc) {
                $id         = $doc->id;
                $pdfUrl     = route('vehicle-exit-slips.pdf', $id);
                $showUrl    = route('vehicle-exit-slips.show', $id);
                $editUrl    = route('vehicle-exit-slips.edit', $id);
                $actions    = '<div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0">
                        <a class="dropdown-item" href="' . $showUrl . '"><i class="fas fa-eye text-primary me-2"></i> Ver Detalle</a>
                        <a class="dropdown-item" href="' . $pdfUrl . '" target="_blank"><i class="fas fa-file-pdf text-danger me-2"></i> Imprimir PDF</a>';

                if (in_array($doc->status, ['PENDIENTE', 'OBSERVADO'])) {
                    $actions .= '<a class="dropdown-item" href="' . $editUrl . '"><i class="fas fa-edit text-warning me-2"></i> Editar</a>
                        <button class="dropdown-item text-danger btn-delete" data-id="' . $id . '"><i class="fas fa-trash-alt me-2"></i> Anular</button>';
                }

                $actions .= '</div></div>';
                return $actions;
            })
            ->rawColumns(['correlativo', 'vehiculo_info', 'personas', 'horario', 'estado_badge', 'acciones'])
            ->make(true);
    }

    public function create(): View {
        $user           = Auth::user();
        $areas          = Area::orderBy('name')->get();
        $userAreaDetail = $user->primaryAreaDetail;
        $lastDoc        = VehicleExitSlip::orderByDesc('id')->first();
        $nextNum        = $lastDoc ? (int) preg_replace('/\D/', '', $lastDoc->correlativo) + 1 : 1;
        $correlativo    = 'PSV-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);

        return view('admin.documents.vehicle_exit_slips.create', compact('user', 'areas', 'userAreaDetail', 'correlativo'));
    }

    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'correlativo'           => 'required|unique:vehicle_exit_slips,correlativo',
            'vehiculo'              => 'required|string|max:255',
            'solicitante_nombre'    => 'required|string|max:255',
            'chofer_nombre'         => 'required|string|max:255',
            'brevete_numero'        => 'nullable|string|max:50',
            'lugar'                 => 'required|string|max:255',
            'motivo'                => 'required|string',
            'fecha_salida'          => 'required|date',
            'hora_salida'           => 'required',
            'fecha_retorno'         => 'nullable|date',
            'hora_retorno'          => 'nullable',
            'area_id'               => 'nullable|exists:areas,id',
            'observaciones'         => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $doc = VehicleExitSlip::create([
                'correlativo'           => $validated['correlativo'],
                'user_id'               => $user->id,
                'area_id'               => $validated['area_id'] ?? $user->primaryAreaDetail?->area_id,
                'vehiculo'              => $validated['vehiculo'],
                'solicitante_nombre'    => $validated['solicitante_nombre'],
                'chofer_nombre'         => $validated['chofer_nombre'],
                'brevete_numero'        => $validated['brevete_numero'] ?? null,
                'lugar'                 => $validated['lugar'],
                'motivo'                => $validated['motivo'],
                'fecha_salida'          => $validated['fecha_salida'],
                'hora_salida'           => $validated['hora_salida'],
                'fecha_retorno'         => $validated['fecha_retorno'] ?? null,
                'hora_retorno'          => $validated['hora_retorno'] ?? null,
                'lugar_emision'         => 'Uchiza',
                'observaciones'         => $validated['observaciones'] ?? null,
                'status'                => 'PENDIENTE',
            ]);

            $this->approvalService->generateWorkflow($doc, $user);

            DB::commit();

            return response()->json([
                'type'      => 'success',
                'message'   => 'Papeleta de salida de vehículo creada exitosamente.',
                'redirect'  => route('vehicle-exit-slips.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['type' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(int $id): View {
        $slip = VehicleExitSlip::with(['approvals.approver', 'user', 'area'])->findOrFail($id);
        return view('admin.documents.vehicle_exit_slips.show', compact('slip'));
    }

    public function edit(int $id): View|RedirectResponse {
        $slip = VehicleExitSlip::findOrFail($id);
        if (!in_array($slip->status, ['PENDIENTE', 'OBSERVADO'])) {
            return redirect()->route('vehicle-exit-slips.show', $id);
        }

        $user   = Auth::user();
        $areas  = Area::orderBy('name')->get();
        return view('admin.documents.vehicle_exit_slips.edit', compact('slip', 'user', 'areas'));
    }

    public function update(Request $request, int $id): JsonResponse {
        $slip = VehicleExitSlip::findOrFail($id);
        if (!in_array($slip->status, ['PENDIENTE', 'OBSERVADO'])) {
            return response()->json(['type' => 'error', 'message' => 'No editable.'], 403);
        }

        $validated = $request->validate([
            'vehiculo'              => 'required|string|max:255',
            'solicitante_nombre'    => 'required|string|max:255',
            'chofer_nombre'         => 'required|string|max:255',
            'brevete_numero'        => 'nullable|string|max:50',
            'lugar'                 => 'required|string|max:255',
            'motivo'                => 'required|string',
            'fecha_salida'          => 'required|date',
            'hora_salida'           => 'required',
            'fecha_retorno'         => 'nullable|date',
            'hora_retorno'          => 'nullable',
            'area_id'               => 'nullable|exists:areas,id',
            'observaciones'         => 'nullable|string',
        ]);

        $slip->update([
            'area_id'               => $validated['area_id'] ?? $slip->area_id,
            'vehiculo'              => $validated['vehiculo'],
            'solicitante_nombre'    => $validated['solicitante_nombre'],
            'chofer_nombre'         => $validated['chofer_nombre'],
            'brevete_numero'        => $validated['brevete_numero'] ?? null,
            'lugar'                 => $validated['lugar'],
            'motivo'                => $validated['motivo'],
            'fecha_salida'          => $validated['fecha_salida'],
            'hora_salida'           => $validated['hora_salida'],
            'fecha_retorno'         => $validated['fecha_retorno'] ?? null,
            'hora_retorno'          => $validated['hora_retorno'] ?? null,
            'observaciones'         => $validated['observaciones'] ?? null,
            'status'                => 'PENDIENTE',
        ]);

        return response()->json([
            'type'      => 'success',
            'message'   => 'Papeleta de vehículo actualizada correctamente.',
            'redirect'  => route('vehicle-exit-slips.show', $slip->id),
        ]);
    }

    public function delete(Request $request): JsonResponse {
        $doc = VehicleExitSlip::findOrFail($request->input('id'));
        $doc->update(['status' => 'ANULADO']);
        $doc->delete();

        return response()->json(['type' => 'success', 'message' => 'Papeleta de vehículo anulada.']);
    }

    public function pdf(int $id) {
        $slip       = VehicleExitSlip::with(['approvals', 'user', 'area'])->findOrFail($id);
        $business   = Business::find(1);
        $pdf        = Pdf::loadView('admin.documents.pdf.vehicle_exit_slip', compact('slip', 'business'))->setPaper('a4', 'landscape');

        return $pdf->stream('Papeleta_Vehiculo_' . $slip->correlativo . '.pdf');
    }
}
