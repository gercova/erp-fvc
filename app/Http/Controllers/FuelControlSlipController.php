<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Business;
use App\Models\FuelControlSlip;
use App\Models\FuelControlSlipItem;
use App\Services\DocumentApprovalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FuelControlSlipController extends Controller
{
    protected DocumentApprovalService $approvalService;

    public function __construct(DocumentApprovalService $approvalService) {
        $this->approvalService = $approvalService;
    }

    public function index(): View {
        return view('admin.documents.fuel_control_slips.index');
    }

    public function get(Request $request): JsonResponse {
        $query = FuelControlSlip::with(['user', 'area', 'approvals'])
            ->when($request->filled('filter_status'), function ($q) use ($request) {
                $q->where('status', $request->input('filter_status'));
            })
            ->when($request->filled('filter_search'), function ($q) use ($request) {
                $search = trim($request->input('filter_search'));
                $q->where(function ($sub) use ($search) {
                    $sub->where('correlativo', 'like', "%{$search}%")
                        ->orWhere('nombre_grifo', 'like', "%{$search}%")
                        ->orWhere('vehiculo_maquina', 'like', "%{$search}%")
                        ->orWhere('placa', 'like', "%{$search}%")
                        ->orWhere('actividad_comision', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('filter_date'), function ($q) use ($request) {
                $q->whereDate('fecha', $request->input('filter_date'));
            })
            ->orderByDesc('id');

        return datatables()->of($query)
            ->editColumn('correlativo', function ($doc) {
                return '<span class="fw-bold text-danger">N° ' . e($doc->correlativo) . '</span>';
            })
            ->editColumn('fecha', function ($doc) {
                return ($doc->fecha ? $doc->fecha->format('d/m/Y') : '') . ' ' . substr((string)$doc->hora, 0, 5);
            })
            ->addColumn('vehiculo_placa', function ($doc) {
                return '<div><div class="fw-semibold">' . e($doc->vehiculo_maquina) . '</div><small class="text-muted">Placa: ' . e($doc->placa ?: 'S/N') . '</small></div>';
            })
            ->addColumn('grifo_actividad', function ($doc) {
                return '<div><div class="fw-semibold">' . e($doc->nombre_grifo) . '</div><small class="text-muted">' . e($doc->actividad_comision) . '</small></div>';
            })
            ->editColumn('total_general', function ($doc) {
                return '<span class="fw-bold">S/ ' . number_format($doc->total_general, 2) . '</span>';
            })
            ->addColumn('estado_badge', function ($doc) {
                return $doc->status_badge;
            })
            ->addColumn('acciones', function ($doc) {
                $id         = $doc->id;
                $pdfUrl     = route('fuel-control-slips.pdf', $id);
                $showUrl    = route('fuel-control-slips.show', $id);
                $editUrl    = route('fuel-control-slips.edit', $id);

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
            ->rawColumns(['correlativo', 'fecha', 'vehiculo_placa', 'grifo_actividad', 'total_general', 'estado_badge', 'acciones'])
            ->make(true);
    }

    public function create(): View {
        $user           = Auth::user();
        $areas          = Area::orderBy('name')->get();
        $userAreaDetail = $user->primaryAreaDetail;
        $lastDoc        = FuelControlSlip::orderByDesc('id')->first();
        $nextNum        = $lastDoc ? (int) preg_replace('/\D/', '', $lastDoc->correlativo) + 1 : 41;
        $correlativo    = str_pad($nextNum, 7, '0', STR_PAD_LEFT);

        return view('admin.documents.fuel_control_slips.create', compact('user', 'areas', 'userAreaDetail', 'correlativo'));
    }

    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'correlativo'           => 'required|unique:fuel_control_slips,correlativo',
            'fecha'                 => 'required|date',
            'hora'                  => 'required',
            'requerimiento_nro'     => 'nullable|string|max:50',
            'orden_compra_nro'      => 'nullable|string|max:50',
            'nombre_grifo'          => 'required|string|max:255',
            'vehiculo_maquina'      => 'required|string|max:255',
            'placa'                 => 'nullable|string|max:50',
            'kilometraje_horometro' => 'nullable|string|max:50',
            'actividad_comision'    => 'required|string',
            'facturar_a'            => 'required|string|max:255',
            'area_id'               => 'nullable|exists:areas,id',
            'observaciones'         => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.cantidad'      => 'required|numeric|min:0.01',
            'items.*.unidad_medida' => 'required|string|max:20',
            'items.*.descripcion'       => 'required|string|max:255',
            'items.*.precio_unitario'   => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $total = 0;
            foreach ($request->input('items') as $item) {
                $pu = (float) ($item['precio_unitario'] ?? 0);
                $total += (float) $item['cantidad'] * $pu;
            }

            $user = Auth::user();
            $doc = FuelControlSlip::create([
                'correlativo'       => $validated['correlativo'],
                'user_id'           => $user->id,
                'area_id'           => $validated['area_id'] ?? $user->primaryAreaDetail?->area_id,
                'fecha'             => $validated['fecha'],
                'hora'              => $validated['hora'],
                'requerimiento_nro' => $validated['requerimiento_nro'] ?? null,
                'orden_compra_nro'  => $validated['orden_compra_nro'] ?? null,
                'nombre_grifo'      => $validated['nombre_grifo'],
                'vehiculo_maquina'  => $validated['vehiculo_maquina'],
                'placa'             => $validated['placa'] ?? null,
                'kilometraje_horometro' => $validated['kilometraje_horometro'] ?? null,
                'actividad_comision'    => $validated['actividad_comision'],
                'facturar_a'        => $validated['facturar_a'],
                'observaciones'     => $validated['observaciones'] ?? null,
                'total_general'     => $total,
                'status'            => 'PENDIENTE',
            ]);

            foreach ($request->input('items') as $itemData) {
                $cant   = (float) $itemData['cantidad'];
                $pu     = (float) ($itemData['precio_unitario'] ?? 0);
                FuelControlSlipItem::create([
                    'fuel_control_slip_id'  => $doc->id,
                    'cantidad'              => $cant,
                    'unidad_medida'         => $itemData['unidad_medida'],
                    'descripcion'           => $itemData['descripcion'],
                    'precio_unitario'       => $pu,
                    'total'                 => $cant * $pu,
                ]);
            }

            $this->approvalService->generateWorkflow($doc, $user);

            DB::commit();

            return response()->json([
                'type'      => 'success',
                'message'   => 'Vale de control interno registrado exitosamente.',
                'redirect'  => route('fuel-control-slips.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['type' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(int $id): View {
        $slip = FuelControlSlip::with(['items', 'approvals.approver', 'user', 'area'])->findOrFail($id);
        return view('admin.documents.fuel_control_slips.show', compact('slip'));
    }

    public function edit(int $id): View|RedirectResponse {
        $slip = FuelControlSlip::with('items')->findOrFail($id);
        if (!in_array($slip->status, ['PENDIENTE', 'OBSERVADO'])) {
            return redirect()->route('fuel-control-slips.show', $id);
        }

        $user   = Auth::user();
        $areas  = Area::orderBy('name')->get();
        return view('admin.documents.fuel_control_slips.edit', compact('slip', 'user', 'areas'));
    }

    public function update(Request $request, int $id): JsonResponse {
        $slip = FuelControlSlip::findOrFail($id);
        if (!in_array($slip->status, ['PENDIENTE', 'OBSERVADO'])) {
            return response()->json([
                'type'      => 'error', 
                'message'   => 'No editable.'
            ], 403);
        }

        $validated = $request->validate([
            'fecha'                     => 'required|date',
            'hora'                      => 'required',
            'requerimiento_nro'         => 'nullable|string|max:50',
            'orden_compra_nro'          => 'nullable|string|max:50',
            'nombre_grifo'              => 'required|string|max:255',
            'vehiculo_maquina'          => 'required|string|max:255',
            'placa'                     => 'nullable|string|max:50',
            'kilometraje_horometro'     => 'nullable|string|max:50',
            'actividad_comision'        => 'required|string',
            'facturar_a'                => 'required|string|max:255',
            'area_id'                   => 'nullable|exists:areas,id',
            'observaciones'             => 'nullable|string',
            'items'                     => 'required|array|min:1',
            'items.*.cantidad'          => 'required|numeric|min:0.01',
            'items.*.unidad_medida'     => 'required|string|max:20',
            'items.*.descripcion'       => 'required|string|max:255',
            'items.*.precio_unitario'   => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $total = 0;
            foreach ($request->input('items') as $item) {
                $pu = (float) ($item['precio_unitario'] ?? 0);
                $total += (float) $item['cantidad'] * $pu;
            }

            $slip->update([
                'area_id'                   => $validated['area_id'] ?? $slip->area_id,
                'fecha'                     => $validated['fecha'],
                'hora'                      => $validated['hora'],
                'requerimiento_nro'         => $validated['requerimiento_nro'] ?? null,
                'orden_compra_nro'          => $validated['orden_compra_nro'] ?? null,
                'nombre_grifo'              => $validated['nombre_grifo'],
                'vehiculo_maquina'          => $validated['vehiculo_maquina'],
                'placa'                     => $validated['placa'] ?? null,
                'kilometraje_horometro'     => $validated['kilometraje_horometro'] ?? null,
                'actividad_comision'        => $validated['actividad_comision'],
                'facturar_a'                => $validated['facturar_a'],
                'observaciones'             => $validated['observaciones'] ?? null,
                'total_general'             => $total,
                'status'                    => 'PENDIENTE',
            ]);

            $slip->items()->delete();
            foreach ($request->input('items') as $itemData) {
                $cant = (float) $itemData['cantidad'];
                $pu = (float) ($itemData['precio_unitario'] ?? 0);
                FuelControlSlipItem::create([
                    'fuel_control_slip_id'  => $slip->id,
                    'cantidad'              => $cant,
                    'unidad_medida'         => $itemData['unidad_medida'],
                    'descripcion'           => $itemData['descripcion'],
                    'precio_unitario'       => $pu,
                    'total'                 => $cant * $pu,
                ]);
            }

            DB::commit();

            return response()->json([
                'type'      => 'success',
                'message'   => 'Vale de control actualizado correctamente.',
                'redirect'  => route('fuel-control-slips.show', $slip->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['type' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request): JsonResponse{
        $doc = FuelControlSlip::findOrFail($request->input('id'));
        $doc->update(['status' => 'ANULADO']);
        $doc->delete();

        return response()->json(['type' => 'success', 'message' => 'Vale de control anulado.']);
    }

    public function pdf(int $id) {
        $slip       = FuelControlSlip::with(['items', 'approvals', 'user', 'area'])->findOrFail($id);
        $business   = Business::find(1);
        $pdf        = Pdf::loadView('admin.documents.pdf.fuel_control_slip', compact('slip', 'business'))->setPaper('a4', 'portrait');

        return $pdf->stream('Vale_Control_' . $slip->correlativo . '.pdf');
    }
}
