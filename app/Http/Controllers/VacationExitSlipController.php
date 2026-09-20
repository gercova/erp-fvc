<?php

namespace App\Http\Controllers;

use App\Http\Requests\VacationExitSlipValidate;
use App\Models\Area;
use App\Models\Business;
use App\Models\VacationExitSlip;
use App\Services\DocumentApprovalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VacationExitSlipController extends Controller
{
    protected DocumentApprovalService $approvalService;

    public function __construct(DocumentApprovalService $approvalService) {
        $this->approvalService = $approvalService;
    }

    public function index(): View {
        return view('admin.documents.vacation_exit_slips.index');
    }

    public function get(Request $request): JsonResponse {
        $query = VacationExitSlip::with(['user', 'area', 'approvals'])
            ->when($request->filled('filter_status'), function ($q) use ($request) {
                $q->where('status', $request->input('filter_status'));
            })
            ->when($request->filled('filter_search'), function ($q) use ($request) {
                $search = trim($request->input('filter_search'));
                $q->where(function ($sub) use ($search) {
                    $sub->where('correlativo', 'like', "%{$search}%")
                        ->orWhere('apellidos_nombres', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%")
                        ->orWhere('area_programa_estudios', 'like', "%{$search}%")
                        ->orWhere('cargo_especialidad', 'like', "%{$search}%")
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
                $q->whereDate('fecha_desde', $request->input('filter_date'));
            })
            ->orderByDesc('id');

        return datatables()->of($query)
            ->editColumn('correlativo', function ($doc) {
                return '<span class="fw-bold text-primary">N° ' . e($doc->correlativo) . '</span>';
            })
            ->addColumn('servidor', function ($doc) {
                return '<div><div class="fw-semibold">' . e($doc->apellidos_nombres) . '</div><small class="text-muted">DNI: ' . e($doc->dni) . ' | ' . e($doc->condicion_laboral) . '</small></div>';
            })
            ->addColumn('area_cargo', function ($doc) {
                return '<div><div class="fw-semibold">' . e($doc->area_programa_estudios) . '</div><small class="text-muted">' . e($doc->cargo_especialidad) . '</small></div>';
            })
            ->addColumn('periodo', function ($doc) {
                $desde = $doc->fecha_desde ? $doc->fecha_desde->format('d/m/Y') : '-';
                $hasta = $doc->fecha_hasta ? $doc->fecha_hasta->format('d/m/Y') : '-';
                return '<div><span class="fw-bold">' . e($doc->total_dias) . ' días</span><br><small class="text-muted">Del ' . e($desde) . ' al ' . e($hasta) . '</small></div>';
            })
            ->addColumn('estado_badge', function ($doc) {
                return $doc->status_badge;
            })
            ->addColumn('acciones', function ($doc) {
                $id         = $doc->id;
                $pdfUrl     = route('vacation-exit-slips.pdf', $id);
                $showUrl    = route('vacation-exit-slips.show', $id);
                $editUrl    = route('vacation-exit-slips.edit', $id);

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
            ->rawColumns(['correlativo', 'servidor', 'area_cargo', 'periodo', 'estado_badge', 'acciones'])
            ->make(true);
    }

    public function create(): View {
        $user           = Auth::user();
        $areas          = Area::orderBy('name')->get();
        $userAreaDetail = $user->primaryAreaDetail;
        $lastDoc        = VacationExitSlip::orderByDesc('id')->first();
        $nextNum        = $lastDoc ? (int) preg_replace('/\D/', '', $lastDoc->correlativo) + 1 : 1;
        $correlativo    = 'PSVAC-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);

        return view('admin.documents.vacation_exit_slips.create', compact('user', 'areas', 'userAreaDetail', 'correlativo'));
    }

    public function store(VacationExitSlipValidate $request): JsonResponse|RedirectResponse {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $doc = VacationExitSlip::create([
                'correlativo'               => $validated['correlativo'],
                'user_id'                   => $user->id,
                'area_id'                   => $validated['area_id'] ?? $user->primaryAreaDetail?->area_id,
                'apellidos_nombres'         => $validated['apellidos_nombres'],
                'dni'                       => $validated['dni'],
                'condicion_laboral'         => $validated['condicion_laboral'],
                'cargo_especialidad'        => $validated['cargo_especialidad'],
                'area_programa_estudios'    => $validated['area_programa_estudios'],
                'motivo'                    => 'Goce de descanso de vacaciones',
                'fecha_desde'               => $validated['fecha_desde'],
                'fecha_hasta'               => $validated['fecha_hasta'],
                'total_dias'                => $validated['total_dias'],
                'resolucion_directoral'     => $validated['resolucion_directoral'] ?? null,
                'declaracion'               => 'Declaro que durante el periodo indicado hare uso del descanso vacacional autorizado, comprometiéndome a reincorporarme a mis labores en la fecha establecida, conforme a la normativa vigente.',
                'lugar_emision'             => 'Uchiza',
                'status'                    => 'PENDIENTE',
            ]);

            $this->approvalService->generateWorkflow($doc, $user);

            DB::commit();

            $successMsg = 'Papeleta de salida por vacaciones creada exitosamente.';

            if ($request->expectsJson()) {
                session()->flash('toast_success', $successMsg);
                return response()->json([
                    'type'      => 'success',
                    'message'   => $successMsg,
                    'redirect'  => route('vacation-exit-slips.index'),
                ]);
            }

            return redirect()->route('vacation-exit-slips.index')
                ->with('toast_success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json(['type' => 'error', 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(int $id): View {
        $slip = VacationExitSlip::with(['approvals.approver', 'user', 'area'])->findOrFail($id);
        return view('admin.documents.vacation_exit_slips.show', compact('slip'));
    }

    public function edit(int $id): View|RedirectResponse {
        $slip   = VacationExitSlip::findOrFail($id);
        if (!in_array($slip->status, ['PENDIENTE', 'OBSERVADO'])) {
            return redirect()->route('vacation-exit-slips.show', $id);
        }

        $user   = Auth::user();
        $areas  = Area::orderBy('name')->get();
        return view('admin.documents.vacation_exit_slips.edit', compact('slip', 'user', 'areas'));
    }

    public function update(VacationExitSlipValidate $request, int $id): JsonResponse {
        $slip = VacationExitSlip::findOrFail($id);
        if (!in_array($slip->status, ['PENDIENTE', 'OBSERVADO'])) {
            return response()->json(['type' => 'error', 'message' => 'No editable.'], 403);
        }

        $validated = $request->validated();

        $slip->update([
            'area_id'                   => $validated['area_id'] ?? $slip->area_id,
            'apellidos_nombres'         => $validated['apellidos_nombres'],
            'dni'                       => $validated['dni'],
            'condicion_laboral'         => $validated['condicion_laboral'],
            'cargo_especialidad'        => $validated['cargo_especialidad'],
            'area_programa_estudios'    => $validated['area_programa_estudios'],
            'fecha_desde'               => $validated['fecha_desde'],
            'fecha_hasta'               => $validated['fecha_hasta'],
            'total_dias'                => $validated['total_dias'],
            'resolucion_directoral'     => $validated['resolucion_directoral'] ?? null,
            'status'                    => 'PENDIENTE',
        ]);

        return response()->json([
            'type'      => 'success',
            'message'   => 'Papeleta de vacaciones actualizada exitosamente.',
            'redirect'  => route('vacation-exit-slips.show', $slip->id),
        ]);
    }

    public function delete(Request $request): JsonResponse {
        $doc = VacationExitSlip::findOrFail($request->input('id'));
        $doc->update(['status' => 'ANULADO']);
        $doc->delete();
        return response()->json(['type' => 'success', 'message' => 'Papeleta de vacaciones anulada.']);
    }

    public function pdf(int $id) {
        $slip       = VacationExitSlip::with(['approvals', 'user', 'area'])->findOrFail($id);
        $business   = Business::find(1);
        $pdf        = Pdf::loadView('admin.documents.pdf.vacation_exit_slip', compact('slip', 'business'))->setPaper('a4', 'portrait');
        return $pdf->stream('Papeleta_Vacaciones_' . $slip->correlativo . '.pdf');
    }
}
