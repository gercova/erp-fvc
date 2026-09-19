<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Business;
use App\Models\ExitSlip;
use App\Services\DocumentApprovalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExitSlipController extends Controller
{
    protected DocumentApprovalService $approvalService;

    public function __construct(DocumentApprovalService $approvalService) {
        $this->approvalService = $approvalService;
    }

    public function index(): View {
        return view('admin.documents.exit_slips.index');
    }

    public function get(Request $request): JsonResponse {
        $query = ExitSlip::with(['user', 'area', 'approvals'])
            ->when($request->filled('filter_status'), function ($q) use ($request) {
                $q->where('status', $request->input('filter_status'));
            })
            ->when($request->filled('filter_search'), function ($q) use ($request) {
                $search = trim($request->input('filter_search'));
                $q->where(function ($sub) use ($search) {
                    $sub->where('correlativo', 'like', "%{$search}%")
                        ->orWhere('nombres_apellidos', 'like', "%{$search}%")
                        ->orWhere('motivo', 'like', "%{$search}%")
                        ->orWhere('destino', 'like', "%{$search}%");
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
            ->addColumn('personal', function ($doc) {
                $badge = $doc->tipo_personal === 'DOCENTE' ? 'bg-primary text-white' : 'bg-secondary text-white';
                return '<div><div class="fw-semibold">' . e($doc->nombres_apellidos) . '</div><span class="badge ' . $badge . ' text-xs">' . e($doc->tipo_personal) . '</span></div>';
            })
            ->addColumn('horario', function ($doc) {
                $salida  = ($doc->fecha_salida ? $doc->fecha_salida->format('d/m/Y') : '') . ' ' . substr((string)$doc->hora_salida, 0, 5);
                $retorno = $doc->fecha_retorno ? ($doc->fecha_retorno->format('d/m/Y') . ' ' . substr((string)$doc->hora_retorno, 0, 5)) : 'No definido';
                return '<div class="small"><div><strong>Salida:</strong> ' . e($salida) . '</div><div><strong>Retorno:</strong> ' . e($retorno) . '</div></div>';
            })
            ->editColumn('motivo', function ($doc) {
                return '<div><div class="fw-semibold">' . e($doc->motivo) . '</div><small class="text-muted">' . e($doc->destino ?: $doc->lugar) . '</small></div>';
            })
            ->addColumn('estado_badge', function ($doc) {
                return $doc->status_badge;
            })
            ->addColumn('acciones', function ($doc) {
                $id         = $doc->id;
                $pdfUrl     = route('exit-slips.pdf', $id);
                $showUrl    = route('exit-slips.show', $id);
                $editUrl    = route('exit-slips.edit', $id);
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
            ->rawColumns(['correlativo', 'personal', 'horario', 'motivo', 'estado_badge', 'acciones'])
            ->make(true);
    }

    public function create(): View {
        $user           = Auth::user();
        $areas          = Area::orderBy('name')->get();
        $userAreaDetail = $user->primaryAreaDetail;
        $lastDoc        = ExitSlip::orderByDesc('id')->first();
        $nextNum        = $lastDoc ? (int) preg_replace('/\D/', '', $lastDoc->correlativo) + 1 : 1;
        $correlativo    = 'PS-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        return view('admin.documents.exit_slips.create', compact('user', 'areas', 'userAreaDetail', 'correlativo'));
    }

    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'correlativo'       => 'required|unique:exit_slips,correlativo',
            'nombres_apellidos' => 'required|string|max:255',
            'tipo_personal'     => 'required|in:DOCENTE,ADMINISTRATIVO',
            'area_id'           => 'nullable|exists:areas,id',
            'fecha_salida'      => 'required|date',
            'hora_salida'       => 'required',
            'fecha_retorno'     => 'nullable|date',
            'hora_retorno'      => 'nullable',
            'destino'           => 'nullable|string|max:255',
            'motivo'            => 'required|string|max:50',
            'motivo_especificar' => 'nullable|string|max:255',
            'lugar'             => 'nullable|string|max:255',
            'observaciones'     => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $doc = ExitSlip::create([
                'correlativo'       => $validated['correlativo'],
                'user_id'           => $user->id,
                'area_id'           => $validated['area_id'] ?? $user->primaryAreaDetail?->area_id,
                'nombres_apellidos' => $validated['nombres_apellidos'],
                'tipo_personal'     => $validated['tipo_personal'],
                'fecha_salida'      => $validated['fecha_salida'],
                'hora_salida'       => $validated['hora_salida'],
                'fecha_retorno'     => $validated['fecha_retorno'] ?? null,
                'hora_retorno'      => $validated['hora_retorno'] ?? null,
                'destino'           => $validated['destino'] ?? null,
                'motivo'            => $validated['motivo'],
                'motivo_especificar' => $validated['motivo_especificar'] ?? null,
                'lugar'             => $validated['lugar'] ?? null,
                'observaciones'     => $validated['observaciones'] ?? null,
                'lugar_emision'     => 'Uchiza',
                'nota_salud'        => 'NOTA: Los permisos por salud deben regularizarse con FUT y/o constancia de atención médica en un plazo de 48 horas',
                'status'            => 'PENDIENTE',
            ]);

            $this->approvalService->generateWorkflow($doc, $user);

            DB::commit();

            return response()->json([
                'type'      => 'success',
                'message'   => 'Papeleta de salida registrada exitosamente.',
                'redirect'  => route('exit-slips.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['type' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(int $id): View {
        $slip = ExitSlip::with(['approvals.approver', 'user', 'area'])->findOrFail($id);
        return view('admin.documents.exit_slips.show', compact('slip'));
    }

    public function edit(int $id): View|RedirectResponse {
        $slip = ExitSlip::findOrFail($id);
        if (!in_array($slip->status, ['PENDIENTE', 'OBSERVADO'])) {
            return redirect()->route('exit-slips.show', $id);
        }

        $user   = Auth::user();
        $areas  = Area::orderBy('name')->get();
        return view('admin.documents.exit_slips.edit', compact('slip', 'user', 'areas'));
    }

    public function update(Request $request, int $id): JsonResponse {
        $slip = ExitSlip::findOrFail($id);
        if (!in_array($slip->status, ['PENDIENTE', 'OBSERVADO'])) {
            return response()->json([
                'type'      => 'error', 
                'message'   => 'No editable.'
            ], 403);
        }

        $validated = $request->validate([
            'nombres_apellidos' => 'required|string|max:255',
            'tipo_personal'     => 'required|in:DOCENTE,ADMINISTRATIVO',
            'area_id'           => 'nullable|exists:areas,id',
            'fecha_salida'      => 'required|date',
            'hora_salida'       => 'required',
            'fecha_retorno'     => 'nullable|date',
            'hora_retorno'      => 'nullable',
            'destino'           => 'nullable|string|max:255',
            'motivo'            => 'required|string|max:50',
            'motivo_especificar' => 'nullable|string|max:255',
            'lugar'             => 'nullable|string|max:255',
            'observaciones'     => 'nullable|string',
        ]);

        $slip->update([
            'area_id'           => $validated['area_id'] ?? $slip->area_id,
            'nombres_apellidos' => $validated['nombres_apellidos'],
            'tipo_personal'     => $validated['tipo_personal'],
            'fecha_salida'      => $validated['fecha_salida'],
            'hora_salida'       => $validated['hora_salida'],
            'fecha_retorno'     => $validated['fecha_retorno'] ?? null,
            'hora_retorno'      => $validated['hora_retorno'] ?? null,
            'destino'           => $validated['destino'] ?? null,
            'motivo'            => $validated['motivo'],
            'motivo_especificar' => $validated['motivo_especificar'] ?? null,
            'lugar'             => $validated['lugar'] ?? null,
            'observaciones'     => $validated['observaciones'] ?? null,
            'status'            => 'PENDIENTE',
        ]);

        return response()->json([
            'type'      => 'success',
            'message'   => 'Papeleta de salida actualizada.',
            'redirect'  => route('exit-slips.show', $slip->id),
        ]);
    }

    public function delete(Request $request): JsonResponse {
        $doc = ExitSlip::findOrFail($request->input('id'));
        $doc->update(['status' => 'ANULADO']);
        $doc->delete();

        return response()->json(['type' => 'success', 'message' => 'Papeleta anulada.']);
    }

    public function pdf(int $id) {
        $slip       = ExitSlip::with(['approvals', 'user', 'area'])->findOrFail($id);
        $business   = Business::find(1);
        $pdf        = Pdf::loadView('admin.documents.pdf.exit_slip', compact('slip', 'business'))->setPaper('a4', 'portrait');

        return $pdf->stream('Papeleta_Salida_' . $slip->correlativo . '.pdf');
    }
}
