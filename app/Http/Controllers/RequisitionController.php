<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Business;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Services\DocumentApprovalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequisitionController extends Controller
{
    protected DocumentApprovalService $approvalService;

    public function __construct(DocumentApprovalService $approvalService) {
        $this->approvalService = $approvalService;
    }

    public function index(): View {
        return view('admin.documents.requisitions.index');
    }

    public function get(Request $request): JsonResponse {
        $query = Requisition::with(['user', 'area', 'approvals'])
            ->when($request->filled('filter_status'), function ($q) use ($request) {
                $q->where('status', $request->input('filter_status'));
            })
            ->when($request->filled('filter_search'), function ($q) use ($request) {
                $search = trim($request->input('filter_search'));
                $q->where(function ($sub) use ($search) {
                    $sub->where('correlativo', 'like', "%{$search}%")
                        ->orWhere('de', 'like', "%{$search}%")
                        ->orWhere('cargo', 'like', "%{$search}%")
                        ->orWhere('dirigido_a', 'like', "%{$search}%")
                        ->orWhere('finalidad', 'like', "%{$search}%")
                        ->orWhere('justificacion', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('nombres', 'like', "%{$search}%")
                                ->orWhere('user', 'like', "%{$search}%");
                        })
                        ->orWhereHas('area', function ($a) use ($search) {
                            $a->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('filter_date'), function ($q) use ($request) {
                $q->whereDate('fecha', $request->input('filter_date'));
            })
            ->orderByDesc('id');

        return datatables()->of($query)
            ->editColumn('correlativo', function ($req) {
                return '<span class="fw-bold text-primary">' . e($req->correlativo) . '</span>';
            })
            ->editColumn('fecha', function ($req) {
                return $req->fecha ? $req->fecha->format('d/m/Y') : '-';
            })
            ->addColumn('solicitante', function ($req) {
                return '<div><div class="fw-semibold">' . e($req->de) . '</div><small class="text-muted">' . e($req->cargo) . '</small></div>';
            })
            ->addColumn('finalidad', function ($req) {
                return '<div><div class="fw-semibold text-truncate" style="max-width: 250px;">' . e($req->finalidad) . '</div><small class="text-muted">' . e($req->area?->name ?? 'Área no asignada') . '</small></div>';
            })
            ->editColumn('total', function ($req) {
                return '<span class="fw-bold">S/ ' . number_format($req->total, 2) . '</span>';
            })
            ->addColumn('estado_badge', function ($req) {
                return $req->status_badge;
            })
            ->addColumn('acciones', function ($req) {
                $id         = $req->id;
                $pdfUrl     = route('requisitions.pdf', $id);
                $showUrl    = route('requisitions.show', $id);
                $editUrl    = route('requisitions.edit', $id);
                $actions    = '<div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0">
                        <a class="dropdown-item" href="' . $showUrl . '"><i class="fas fa-eye text-primary me-2"></i> Ver Detalle</a>
                        <a class="dropdown-item" href="' . $pdfUrl . '" target="_blank"><i class="fas fa-file-pdf text-danger me-2"></i> Imprimir PDF</a>';

                if (in_array($req->status, ['PENDIENTE', 'OBSERVADO'])) {
                    $actions .= '<a class="dropdown-item" href="' . $editUrl . '"><i class="fas fa-edit text-warning me-2"></i> Editar</a>
                        <button class="dropdown-item text-danger btn-delete" data-id="' . $id . '"><i class="fas fa-trash-alt me-2"></i> Anular</button>';
                }

                $actions .= '</div></div>';
                return $actions;
            })
            ->rawColumns(['correlativo', 'fecha', 'solicitante', 'finalidad', 'total', 'estado_badge', 'acciones'])
            ->make(true);
    }

    public function create(): View {
        $user           = Auth::user();
        $areas          = Area::orderBy('name')->get();
        $userAreaDetail = $user->primaryAreaDetail;
        // Auto-generate correlativo
        $lastReq        = Requisition::orderByDesc('id')->first();
        $nextNum        = $lastReq ? (int) preg_replace('/\D/', '', $lastReq->correlativo) + 1 : 316;
        $correlativo    = str_pad($nextNum, 7, '0', STR_PAD_LEFT);
        return view('admin.documents.requisitions.create', compact('user', 'areas', 'userAreaDetail', 'correlativo'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'correlativo'               => 'required|unique:requisitions,correlativo',
            'fecha'                     => 'required|date',
            'dirigido_a'                => 'required|string|max:255',
            'de'                        => 'required|string|max:255',
            'cargo'                     => 'required|string|max:255',
            'area_id'                   => 'nullable|exists:areas,id',
            'finalidad'                 => 'required|string',
            'fuente_financiamiento'     => 'required|in:programa_estudios,actividad_productiva,plan',
            'fuente_especificar'        => 'nullable|string|max:255',
            'justificacion'             => 'nullable|string',
            'items'                     => 'required|array|min:1',
            'items.*.cantidad'          => 'required|numeric|min:0.01',
            'items.*.descripcion'       => 'required|string|max:500',
            'items.*.unidad_medida'     => 'nullable|string|max:50',
            'items.*.precio_unitario'   => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();

            $total = 0;
            foreach ($request->input('items') as $item) {
                $total += (float)$item['cantidad'] * (float)$item['precio_unitario'];
            }

            $requisition = Requisition::create([
                'correlativo'           => $validated['correlativo'],
                'user_id'               => $user->id,
                'area_id'               => $validated['area_id'] ?? $user->primaryAreaDetail?->area_id,
                'dirigido_a'            => $validated['dirigido_a'],
                'de'                    => $validated['de'],
                'cargo'                 => $validated['cargo'],
                'fecha'                 => $validated['fecha'],
                'finalidad'             => $validated['finalidad'],
                'fuente_financiamiento' => $validated['fuente_financiamiento'],
                'fuente_especificar'    => $validated['fuente_especificar'] ?? null,
                'justificacion'         => $validated['justificacion'] ?? null,
                'condiciones_admin'     => 'El presente requerimiento deberá ser ingresado por Trámite Documentario (Mesa de Partes) para su evaluación y aprobación por la Dirección General.',
                'total'                 => $total,
                'status'                => 'PENDIENTE',
            ]);

            foreach ($request->input('items') as $index => $itemData) {
                $cant   = (float) $itemData['cantidad'];
                $price  = (float) $itemData['precio_unitario'];
                RequisitionItem::create([
                    'requisition_id'    => $requisition->id,
                    'item_number'       => $index + 1,
                    'cantidad'          => $cant,
                    'descripcion'       => $itemData['descripcion'],
                    'unidad_medida'     => $itemData['unidad_medida'] ?? null,
                    'precio_unitario'   => $price,
                    'precio_total'      => $cant * $price,
                ]);
            }

            // Generate approval workflow
            $this->approvalService->generateWorkflow($requisition, $user);

            DB::commit();

            $successMsg = 'Requerimiento registrado con éxito y enviado a flujo de aprobación.';

            if ($request->expectsJson()) {
                session()->flash('toast_success', $successMsg);
                return response()->json([
                    'type'      => 'success',
                    'message'   => $successMsg,
                    'redirect'  => route('requisitions.index'),
                ]);
            }

            return redirect()->route('requisitions.index')
                ->with('toast_success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json([
                    'type'      => 'error',
                    'message'   => 'Error al guardar el requerimiento: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withInput()->with('error', 'Error al guardar el requerimiento: ' . $e->getMessage());
        }
    }

    public function show(int $id): View {
        $requisition = Requisition::with(['items', 'approvals.approver', 'user', 'area'])->findOrFail($id);
        return view('admin.documents.requisitions.show', compact('requisition'));
    }

    public function edit(int $id): View|RedirectResponse {
        $requisition = Requisition::with('items')->findOrFail($id);
        if (!in_array($requisition->status, ['PENDIENTE', 'OBSERVADO'])) {
            return redirect()->route('requisitions.show', $id)->with('error', 'El documento ya no se encuentra en estado editable.');
        }

        $user       = Auth::user();
        $areas      = Area::orderBy('name')->get();
        return view('admin.documents.requisitions.edit', compact('requisition', 'user', 'areas'));
    }

    public function update(Request $request, $id): JsonResponse {
        $requisition = Requisition::findOrFail($id);
        if (!in_array($requisition->status, ['PENDIENTE', 'OBSERVADO'])) {
            return response()->json(['type' => 'error', 'message' => 'No se puede editar este documento.'], 403);
        }

        $validated = $request->validate([
            'fecha'                 => 'required|date',
            'dirigido_a'            => 'required|string|max:255',
            'de'                    => 'required|string|max:255',
            'cargo'                 => 'required|string|max:255',
            'area_id'               => 'nullable|exists:areas,id',
            'finalidad'             => 'required|string',
            'fuente_financiamiento' => 'required|in:programa_estudios,actividad_productiva,plan',
            'fuente_especificar'    => 'nullable|string|max:255',
            'justificacion'         => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.cantidad'      => 'required|numeric|min:0.01',
            'items.*.descripcion'   => 'required|string|max:500',
            'items.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $total = 0;
            foreach ($request->input('items') as $item) {
                $total += (float) $item['cantidad'] * (float) $item['precio_unitario'];
            }

            $requisition->update([
                'area_id'               => $validated['area_id'] ?? $requisition->area_id,
                'dirigido_a'            => $validated['dirigido_a'],
                'de'                    => $validated['de'],
                'cargo'                 => $validated['cargo'],
                'fecha'                 => $validated['fecha'],
                'finalidad'             => $validated['finalidad'],
                'fuente_financiamiento' => $validated['fuente_financiamiento'],
                'fuente_especificar'    => $validated['fuente_especificar'] ?? null,
                'justificacion'         => $validated['justificacion'] ?? null,
                'total'                 => $total,
                'status'                => 'PENDIENTE',
            ]);

            $requisition->items()->delete();
            foreach ($request->input('items') as $index => $itemData) {
                $cant  = (float) $itemData['cantidad'];
                $price = (float) $itemData['precio_unitario'];
                RequisitionItem::create([
                    'requisition_id'    => $requisition->id,
                    'item_number'       => $index + 1,
                    'cantidad'          => $cant,
                    'descripcion'       => $itemData['descripcion'],
                    'precio_unitario'   => $price,
                    'precio_total'      => $cant * $price,
                ]);
            }

            DB::commit();

            return response()->json([
                'type'      => 'success',
                'message'   => 'Requerimiento actualizado correctamente.',
                'redirect'  => route('requisitions.show', $requisition->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['type' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request): JsonResponse {
        $requisition = Requisition::findOrFail($request->input('id'));
        $requisition->update(['status' => 'ANULADO']);
        $requisition->delete();
        return response()->json([
            'type'      => 'success',
            'message'   => 'Requerimiento anulado con éxito.',
        ]);
    }

    public function pdf(int $id) {
        $requisition = Requisition::with([
            'items',
            'approvals.approver',
            'user',
            'area'
        ])->findOrFail($id);
        $business   = Business::find(1);
        $pdf        = Pdf::loadView('admin.documents.pdf.requisition', compact('requisition', 'business'))->setPaper('a4', 'portrait');

        return $pdf->stream('Requerimiento_' . $requisition->correlativo . '.pdf');
    }
}
