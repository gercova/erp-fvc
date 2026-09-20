<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Business;
use App\Models\ExpenseDeclaration;
use App\Models\ExpenseDeclarationItem;
use App\Services\DocumentApprovalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Luecano\NumeroALetras\NumeroALetras;

class ExpenseDeclarationController extends Controller
{
    protected DocumentApprovalService $approvalService;

    public function __construct(DocumentApprovalService $approvalService) {
        $this->approvalService = $approvalService;
    }

    public function index(): View {
        return view('admin.documents.expense_declarations.index');
    }

    public function get(Request $request): JsonResponse {
        $query = ExpenseDeclaration::with(['user', 'area', 'approvals'])
            ->when($request->filled('filter_status'), function ($q) use ($request) {
                $q->where('status', $request->input('filter_status'));
            })
            ->when($request->filled('filter_search'), function ($q) use ($request) {
                $search = trim($request->input('filter_search'));
                $q->where(function ($sub) use ($search) {
                    $sub->where('correlativo', 'like', "%{$search}%")
                        ->orWhere('servidor_nombres', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%")
                        ->orWhere('cargo', 'like', "%{$search}%")
                        ->orWhere('conceptos', 'like', "%{$search}%")
                        ->orWhere('lugar', 'like', "%{$search}%")
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
            ->editColumn('correlativo', function ($doc) {
                return '<span class="fw-bold text-primary">' . e($doc->correlativo) . '</span>';
            })
            ->editColumn('fecha', function ($doc) {
                return $doc->fecha ? $doc->fecha->format('d/m/Y') : '-';
            })
            ->addColumn('servidor', function ($doc) {
                return '<div><div class="fw-semibold">' . e($doc->servidor_nombres) . '</div><small class="text-muted">DNI: ' . e($doc->dni) . ' - ' . e($doc->cargo) . '</small></div>';
            })
            ->addColumn('conceptos', function ($doc) {
                return '<div><div class="fw-semibold text-truncate" style="max-width: 250px;">' . e($doc->conceptos) . '</div><small class="text-muted">' . e($doc->area?->name ?? 'Área no asignada') . '</small></div>';
            })
            ->editColumn('total', function ($doc) {
                return '<span class="fw-bold">S/ ' . number_format($doc->total, 2) . '</span>';
            })
            ->addColumn('estado_badge', function ($doc) {
                return $doc->status_badge;
            })
            ->addColumn('acciones', function ($doc) {
                $id         = $doc->id;
                $pdfUrl     = route('expense-declarations.pdf', $id);
                $showUrl    = route('expense-declarations.show', $id);
                $editUrl    = route('expense-declarations.edit', $id);
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
            ->rawColumns(['correlativo', 'fecha', 'servidor', 'conceptos', 'total', 'estado_badge', 'acciones'])
            ->make(true);
    }

    public function create(): View {
        $user           = Auth::user();
        $areas          = Area::orderBy('name')->get();
        $userAreaDetail = $user->primaryAreaDetail;
        $lastDoc        = ExpenseDeclaration::orderByDesc('id')->first();
        $nextNum        = $lastDoc ? (int) preg_replace('/\D/', '', $lastDoc->correlativo) + 1 : 1;
        $correlativo    = 'DJG-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        return view('admin.documents.expense_declarations.create', compact('user', 'areas', 'userAreaDetail', 'correlativo'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'correlativo'       => 'required|unique:expense_declarations,correlativo',
            'fecha'             => 'required|date',
            'servidor_nombres'  => 'required|string|max:255',
            'dni'               => 'required|string|max:20',
            'cargo'             => 'required|string|max:255',
            'area_id'           => 'nullable|exists:areas,id',
            'conceptos'         => 'required|string',
            'lugar'             => 'nullable|string|max:100',
            'items'             => 'required|array|min:1',
            'items.*.fecha'             => 'required|date',
            'items.*.detalle_gestion'   => 'required|string|max:500',
            'items.*.importe'           => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            $total = 0;
            foreach ($request->input('items') as $item) {
                $total += (float) $item['importe'];
            }

            $formatter      = new NumeroALetras();
            $totalLetras    = $formatter->toInvoice($total, 2, 'SOLES');

            $user = Auth::user();
            $doc = ExpenseDeclaration::create([
                'correlativo'       => $validated['correlativo'],
                'user_id'           => $user->id,
                'area_id'           => $validated['area_id'] ?? $user->primaryAreaDetail?->area_id,
                'servidor_nombres'  => $validated['servidor_nombres'],
                'dni'               => $validated['dni'],
                'cargo'             => $validated['cargo'],
                'total'             => $total,
                'total_letras'      => $totalLetras,
                'conceptos'         => $validated['conceptos'],
                'fecha'             => $validated['fecha'],
                'lugar'             => $validated['lugar'] ?? 'Uchiza',
                'certifico'         => 'Que lo DECLARADO en el Documento Justifica los Gastos Efectuados.',
                'status'            => 'PENDIENTE',
            ]);

            foreach ($request->input('items') as $itemData) {
                ExpenseDeclarationItem::create([
                    'expense_declaration_id'    => $doc->id,
                    'fecha'                     => $itemData['fecha'],
                    'detalle_gestion'           => $itemData['detalle_gestion'],
                    'importe'                   => (float) $itemData['importe'],
                ]);
            }

            $this->approvalService->generateWorkflow($doc, $user);

            DB::commit();

            $successMsg = 'Declaración Jurada registrada correctamente.';

            if ($request->expectsJson()) {
                session()->flash('toast_success', $successMsg);
                return response()->json([
                    'type'      => 'success',
                    'message'   => $successMsg,
                    'redirect'  => route('expense-declarations.index'),
                ]);
            }

            return redirect()->route('expense-declarations.index')
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
        $declaration = ExpenseDeclaration::with(['items', 'approvals.approver', 'user', 'area'])->findOrFail($id);
        return view('admin.documents.expense_declarations.show', compact('declaration'));
    }

    public function edit(int $id): View|RedirectResponse {
        $declaration = ExpenseDeclaration::with('items')->findOrFail($id);
        if (!in_array($declaration->status, ['PENDIENTE', 'OBSERVADO'])) {
            return redirect()->route('expense-declarations.show', $id);
        }

        $user   = Auth::user();
        $areas  = Area::orderBy('name')->get();
        return view('admin.documents.expense_declarations.edit', compact('declaration', 'user', 'areas'));
    }

    public function update(Request $request, int $id): JsonResponse {
        $declaration = ExpenseDeclaration::findOrFail($id);
        if (!in_array($declaration->status, ['PENDIENTE', 'OBSERVADO'])) {
            return response()->json(['type' => 'error', 'message' => 'No editable.'], 403);
        }

        $validated = $request->validate([
            'fecha'             => 'required|date',
            'servidor_nombres'  => 'required|string|max:255',
            'dni'               => 'required|string|max:20',
            'cargo'             => 'required|string|max:255',
            'area_id'           => 'nullable|exists:areas,id',
            'conceptos'         => 'required|string',
            'lugar'             => 'nullable|string|max:100',
            'items'             => 'required|array|min:1',
            'items.*.fecha'             => 'required|date',
            'items.*.detalle_gestion'   => 'required|string|max:500',
            'items.*.importe'           => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            $total = 0;
            foreach ($request->input('items') as $item) {
                $total += (float) $item['importe'];
            }

            $formatter      = new NumeroALetras();
            $totalLetras    = $formatter->toInvoice($total, 2, 'SOLES');

            $declaration->update([
                'area_id'           => $validated['area_id'] ?? $declaration->area_id,
                'servidor_nombres'  => $validated['servidor_nombres'],
                'dni'               => $validated['dni'],
                'cargo'             => $validated['cargo'],
                'total'             => $total,
                'total_letras'      => $totalLetras,
                'conceptos'         => $validated['conceptos'],
                'fecha'             => $validated['fecha'],
                'lugar'             => $validated['lugar'] ?? 'Uchiza',
                'status'            => 'PENDIENTE',
            ]);

            $declaration->items()->delete();
            foreach ($request->input('items') as $itemData) {
                ExpenseDeclarationItem::create([
                    'expense_declaration_id'    => $declaration->id,
                    'fecha'                     => $itemData['fecha'],
                    'detalle_gestion'           => $itemData['detalle_gestion'],
                    'importe'                   => (float) $itemData['importe'],
                ]);
            }

            DB::commit();

            return response()->json([
                'type'      => 'success',
                'message'   => 'Declaración Jurada actualizada correctamente.',
                'redirect'  => route('expense-declarations.show', $declaration->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['type' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request): JsonResponse {
        $doc = ExpenseDeclaration::findOrFail($request->input('id'));
        $doc->update(['status' => 'ANULADO']);
        $doc->delete();

        return response()->json(['type' => 'success', 'message' => 'Declaración Jurada anulada.']);
    }

    public function pdf(int $id) {
        $declaration    = ExpenseDeclaration::with(['items', 'approvals', 'user'])->findOrFail($id);
        $business       = Business::find(1);
        $pdf            = Pdf::loadView('admin.documents.pdf.expense_declaration', compact('declaration', 'business'))->setPaper('a4', 'portrait');

        return $pdf->stream('Declaracion_Jurada_' . $declaration->correlativo . '.pdf');
    }
}
