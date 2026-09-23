<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetLoanValidate;
use App\Models\Area;
use App\Models\Asset;
use App\Models\AssetLoan;
use App\Models\Business;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AssetLoanController extends Controller
{
    protected function getAccessibleAreas(User $user): Collection {
        $isGlobalSupervisor = $user->hasRole(['SUPERADMIN', 'ADMIN', 'PATRIMONIO', 'ABASTECIMIENTO', 'DIRECTOR_GENERAL']);

        if ($isGlobalSupervisor) {
            return Area::orderBy('name')->get();
        }

        $headedAreas = Area::where('head_user_id', $user->id)->pluck('id')->toArray();
        $assignedAreas = $user->employeeAreaDetails()->pluck('area_id')->toArray();
        if ($user->primaryArea) {
            $assignedAreas[] = $user->primaryArea->id;
        }
        $allowedAreaIds = array_unique(array_merge($headedAreas, $assignedAreas));

        return Area::whereIn('id', $allowedAreaIds)->orderBy('name')->get();
    }

    public function index(Request $request): View {
        $user               = Auth::user();
        $isGlobalSupervisor = $user->hasRole(['SUPERADMIN', 'ADMIN', 'PATRIMONIO', 'ABASTECIMIENTO', 'DIRECTOR_GENERAL']);
        $areas              = $this->getAccessibleAreas($user);

        $selectedAreaId     = $request->input('area_id');
        if ($selectedAreaId && !$areas->contains('id', $selectedAreaId)) {
            $selectedAreaId = null;
        }

        $selectedArea = $selectedAreaId ? Area::find($selectedAreaId) : null;

        // Métricas de préstamos
        $queryBase = AssetLoan::query();
        if ($selectedAreaId) {
            $queryBase->where('area_id', $selectedAreaId);
        } elseif (!$isGlobalSupervisor) {
            $queryBase->whereIn('area_id', $areas->pluck('id'));
        }

        $totalCount    = (clone $queryBase)->count();
        $activeCount   = (clone $queryBase)->where('status', 'PRESTADO')->count();
        $overdueCount  = (clone $queryBase)->where('status', 'PRESTADO')->where('expected_return_date', '<', now())->count();
        $returnedCount = (clone $queryBase)->whereIn('status', ['DEVUELTO', 'DEVUELTO_OBSERVADO'])->count();

        return view('admin.assets.loans.index', compact(
            'areas',
            'selectedArea',
            'selectedAreaId',
            'isGlobalSupervisor',
            'totalCount',
            'activeCount',
            'overdueCount',
            'returnedCount'
        ));
    }

    public function get(Request $request): JsonResponse {
        $user = Auth::user();
        $isGlobalSupervisor = $user->hasRole(['SUPERADMIN', 'ADMIN', 'PATRIMONIO', 'ABASTECIMIENTO', 'DIRECTOR_GENERAL']);
        $accessibleAreaIds = $this->getAccessibleAreas($user)->pluck('id')->toArray();

        $query = AssetLoan::with(['asset.area', 'area', 'user', 'borrowerUser', 'receivedByUser']);

        // Filtrado por área
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        } elseif (!$isGlobalSupervisor) {
            $query->whereIn('area_id', $accessibleAreaIds);
        }

        // Filtrado por tipo de solicitante
        if ($request->filled('filter_borrower_type')) {
            $query->where('borrower_type', $request->input('filter_borrower_type'));
        }

        // Filtrado por estado
        if ($request->filled('filter_status')) {
            $status = $request->input('filter_status');
            if ($status === 'OVERDUE') {
                $query->where('status', 'PRESTADO')->where('expected_return_date', '<', now());
            } elseif ($status === 'RETURNED') {
                $query->whereIn('status', ['DEVUELTO', 'DEVUELTO_OBSERVADO']);
            } else {
                $query->where('status', $status);
            }
        }

        // Búsqueda general
        if ($request->filled('filter_search')) {
            $search = trim($request->input('filter_search'));
            $query->where(function ($sub) use ($search) {
                $sub->where('loan_code', 'like', "%{$search}%")
                    ->orWhere('borrower_name', 'like', "%{$search}%")
                    ->orWhere('borrower_document', 'like', "%{$search}%")
                    ->orWhere('borrower_code', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhereHas('asset', function ($assetQ) use ($search) {
                        $assetQ->where('descripcion', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%")
                            ->orWhere('codigo_producto', 'like', "%{$search}%")
                            ->orWhere('serie', 'like', "%{$search}%")
                            ->orWhere('marca', 'like', "%{$search}%");
                    });
            });
        }

        $query->orderBy('created_at', 'desc');

        return datatables()->of($query)
            ->addColumn('codigo_col', function (AssetLoan $loan) {
                return '
                    <div class="fw-bold font-monospace text-primary">' . e($loan->loan_code) . '</div>' .
                       '<small class="text-muted"><i class="far fa-calendar-alt me-1"></i>' . $loan->loan_date->format('d/m/Y H:i') . '
                    </small>
                ';
            })
            ->addColumn('asset_col', function (AssetLoan $loan) {
                $asset = $loan->asset;
                if (!$asset) {
                    return '<span class="text-danger">Bien no encontrado</span>';
                }
                $html = '<div class="fw-semibold text-dark">' . e($asset->descripcion) . '</div>';
                $meta = [];
                if ($asset->codigo) {
                    $meta[] = '<span class="badge bg-light text-dark font-monospace border">' . e($asset->codigo) . '</span>';
                }
                if ($asset->serie && $asset->serie !== 'SIN SERIE') {
                    $meta[] = '<small class="text-muted">S/N: ' . e($asset->serie) . '</small>';
                }
                if ($asset->marca && $asset->marca !== 'SIN MARCA') {
                    $meta[] = '<small class="text-muted">' . e($asset->marca) . '</small>';
                }
                if (!empty($meta)) {
                    $html .= '<div class="d-flex align-items-center gap-2 mt-1">' . implode('', $meta) . '</div>';
                }
                return $html;
            })
            ->addColumn('borrower_col', function (AssetLoan $loan) {
                $html = '<div class="fw-semibold text-dark">' . e($loan->borrower_name) . '</div>';
                $html .= '<div class="d-flex align-items-center gap-1 my-1">' . $loan->borrower_type_badge . '</div>';
                
                $details = [];
                if ($loan->borrower_document) {
                    $details[] = 'DNI: ' . e($loan->borrower_document);
                }
                if ($loan->borrower_code) {
                    $details[] = 'Cód: ' . e($loan->borrower_code);
                }
                if ($loan->borrower_career_or_area) {
                    $details[] = e($loan->borrower_career_or_area);
                }
                if (!empty($details)) {
                    $html .= '<small class="text-muted d-block">' . implode(' &bull; ', $details) . '</small>';
                }
                return $html;
            })
            ->addColumn('dates_col', function (AssetLoan $loan) {
                $isOverdue = $loan->is_overdue;
                $html = '<div><small class="text-muted">Retorno previsto:</small><br>' .
                    '<span class="' . ($isOverdue ? 'text-danger fw-bold' : 'text-dark fw-medium') . '">' .
                    $loan->expected_return_date->format('d/m/Y H:i') . '</span></div>';

                if ($loan->actual_return_date) {
                    $html .= '<div class="mt-1"><small class="text-success"><i class="fas fa-check-circle me-1"></i>Devuelto el: ' .
                        $loan->actual_return_date->format('d/m/Y H:i') . '</small></div>';
                } elseif ($isOverdue) {
                    $diff = $loan->expected_return_date->diffForHumans();
                    $html .= '<div class="mt-1"><span class="badge bg-danger text-white">Vencido ' . $diff . '</span></div>';
                }
                return $html;
            })
            ->addColumn('destination_col', function (AssetLoan $loan) {
                $html = '<div class="fw-medium text-dark">' . e($loan->destination) . '</div>';
                if ($loan->purpose) {
                    $html .= '<small class="text-muted text-truncate d-block" style="max-width: 220px;" title="' . e($loan->purpose) . '">' . e(Str::limit($loan->purpose, 45)) . '</small>';
                }
                return $html;
            })
            ->addColumn('status_badge', function (AssetLoan $loan) {
                return $loan->status_badge;
            })
            ->addColumn('acciones', function (AssetLoan $loan) {
                $id         = $loan->id;
                $editUrl    = route('asset_loans.edit', $id);
                $pdfUrl     = route('asset_loans.pdf', $id);
                $isReturned = in_array($loan->status, ['DEVUELTO', 'DEVUELTO_OBSERVADO']);

                $returnBtn  = '';
                if (!$isReturned) {
                    $returnBtn = '<li><button class="dropdown-item py-1 btn-quick-return" data-id="' . $id . '" data-code="' . e($loan->loan_code) . '" data-borrower="' . e($loan->borrower_name) . '"><i class="fas fa-undo-alt me-2"></i>Registrar Devolución</button></li>';
                }

                return '<div class="d-flex align-items-center justify-content-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-info py-1 px-2 btn-quick-detail" data-id="' . $id . '" title="Ver detalles del préstamo">
                        <i class="fas fa-eye"></i>
                    </button>
                    ' . (!$isReturned ? '<button type="button" class="btn btn-sm btn-outline-success py-1 px-2 btn-quick-return" data-id="' . $id . '" data-code="' . e($loan->loan_code) . '" data-borrower="' . e($loan->borrower_name) . '" title="Registrar Devolución"><i class="fas fa-check"></i></button>' : '') . '
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle py-1 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-1">
                            <li><button class="dropdown-item py-1 btn-quick-detail" data-id="' . $id . '"><i class="fas fa-info-circle me-2"></i>Ver Ficha Completa</button></li>
                            <li><a class="dropdown-item py-1" href="' . $pdfUrl . '" target="_blank"><i class="fas fa-file-pdf me-2"></i>Papeleta de Préstamo (PDF)</a></li>
                            <li><a class="dropdown-item py-1" href="' . $editUrl . '"><i class="fas fa-edit me-2"></i>Editar Préstamo</a></li>
                            ' . $returnBtn . '
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><button class="dropdown-item py-1 btn-delete-loan" data-id="' . $id . '"><i class="fas fa-trash-alt me-2"></i>Eliminar Registro</button></li>
                        </ul>
                    </div>
                </div>';
            })
            ->rawColumns([
                'codigo_col',
                'asset_col',
                'borrower_col',
                'dates_col',
                'destination_col',
                'status_badge',
                'acciones',
            ])
            ->make(true);
    }

    public function create(Request $request): View {
        $user   = Auth::user();
        $areas  = $this->getAccessibleAreas($user);
        $selectedAreaId = $request->input('area_id') ?: ($areas->first()?->id ?? null);
        $availableAssets = collect();
        if ($selectedAreaId) {
            $activeLoanAssetIds = AssetLoan::where('status', 'PRESTADO')->pluck('asset_id')->toArray();

            $availableAssets = Asset::where('area_id', $selectedAreaId)
                ->where('estado_operativo', '!=', 'DE_BAJA')
                ->whereNotIn('id', $activeLoanAssetIds)
                ->orderBy('orden')
                ->get();
        }

        // Lista de usuarios registrados (docentes y administrativos para autocompletar)
        $systemUsers = User::orderBy('nombres')->get(['id', 'nombres', 'correo', 'telefono']);

        // Próximo código referencial
        $year = date('Y');
        $count = AssetLoan::whereYear('loan_date', $year)->count() + 1;
        $nextCode = sprintf('PRE-%s-%04d', $year, $count);

        return view('admin.assets.loans.create', compact(
            'areas',
            'selectedAreaId',
            'availableAssets',
            'systemUsers',
            'nextCode'
        ));
    }

    public function getAssetsByArea(Request $request): JsonResponse {
        $areaId = $request->input('area_id');
        $activeLoanAssetIds = AssetLoan::where('status', 'PRESTADO')->pluck('asset_id')->toArray();

        $assets = Asset::where('area_id', $areaId)
            ->where('estado_operativo', '!=', 'DE_BAJA')
            ->whereNotIn('id', $activeLoanAssetIds)
            ->orderBy('descripcion')
            ->get(['id', 'codigo', 'codigo_producto', 'descripcion', 'marca', 'modelo', 'serie', 'condicion', 'ubicacion']);

        return response()->json([
            'status' => true,
            'assets' => $assets,
        ]);
    }

    public function store(AssetLoanValidate $request): JsonResponse|RedirectResponse {
        $data = $request->validated();

        // Validar que el activo no esté ya en préstamo
        $alreadyLoaned = AssetLoan::where('asset_id', $data['asset_id'])
            ->where('status', 'PRESTADO')
            ->exists();

        if ($alreadyLoaned) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'msg'    => 'El bien patrimonial seleccionado ya se encuentra prestado actualmente.',
                    'type'   => 'warning',
                ], 422);
            }
            return back()->withInput()->with('toast_error', 'El bien patrimonial ya se encuentra prestado actualmente.');
        }

        $data['user_id'] = Auth::id();
        $loan = AssetLoan::create($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => true,
                'message' => 'El préstamo fue registrado exitosamente con el código ' . $loan->loan_code . '.',
                'loan'    => $loan,
            ]);
        }

        return redirect()->route('asset_loans.index', ['area_id' => $loan->area_id])
            ->with('toast_success', 'El préstamo fue registrado exitosamente (' . $loan->loan_code . ').');
    }

    public function quickDetail(int $id): JsonResponse {
        $loan = AssetLoan::with(['asset.area', 'area', 'user', 'borrowerUser', 'receivedByUser'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'loan'    => [
                'id'                       => $loan->id,
                'uuid'                     => $loan->uuid,
                'loan_code'                => $loan->loan_code,
                'status'                   => $loan->status,
                'status_label'             => $loan->status_label,
                'status_badge'             => $loan->status_badge,
                'is_overdue'               => $loan->is_overdue,
                'area_name'                => $loan->area?->name,
                'borrower_type'            => $loan->borrower_type,
                'borrower_type_label'      => $loan->borrower_type_label,
                'borrower_type_badge'      => $loan->borrower_type_badge,
                'borrower_name'            => $loan->borrower_name,
                'borrower_document'        => $loan->borrower_document,
                'borrower_code'            => $loan->borrower_code ?: 'NO ASIGNADO',
                'borrower_career_or_area'  => $loan->borrower_career_or_area ?: 'NO ESPECIFICADO',
                'borrower_phone'           => $loan->borrower_phone ?: 'NO REGISTRADO',
                'borrower_email'           => $loan->borrower_email ?: 'NO REGISTRADO',
                'loan_date'                => $loan->loan_date?->format('d/m/Y H:i'),
                'expected_return_date'     => $loan->expected_return_date?->format('d/m/Y H:i'),
                'actual_return_date'       => $loan->actual_return_date?->format('d/m/Y H:i') ?: 'PENDIENTE',
                'initial_condition'        => $loan->initial_condition_label,
                'return_condition'         => $loan->return_condition_label ?: 'PENDIENTE',
                'destination'              => $loan->destination,
                'purpose'                  => $loan->purpose ?: 'Sin especificar',
                'observations'             => $loan->observations ?: 'Sin observaciones',
                'return_observations'      => $loan->return_observations ?: 'Sin observaciones de retorno',
                'registered_by'            => $loan->user?->nombres ?: 'USUARIO',
                'received_by'              => $loan->receivedByUser?->nombres ?: 'PENDIENTE',
                'asset'                    => [
                    'id'          => $loan->asset?->id,
                    'codigo'      => $loan->asset?->codigo ?: 'NO ASIGNADO',
                    'descripcion' => $loan->asset?->descripcion,
                    'marca'       => $loan->asset?->marca,
                    'modelo'      => $loan->asset?->modelo,
                    'serie'       => $loan->asset?->serie,
                    'condicion'   => $loan->asset?->condicion_label,
                    'ubicacion'   => $loan->asset?->ubicacion,
                ],
                'pdf_url'                  => route('asset_loans.pdf', $loan->id),
                'edit_url'                 => route('asset_loans.edit', $loan->id),
            ]
        ]);
    }

    public function edit(int $id): View {
        $loan           = AssetLoan::with(['asset', 'area', 'user', 'borrowerUser', 'receivedByUser'])->findOrFail($id);
        $user           = Auth::user();
        $areas          = $this->getAccessibleAreas($user);
        $systemUsers    = User::orderBy('nombres')->get(['id', 'nombres', 'correo', 'telefono']);

        // Activos del área (incluyendo el activo actualmente prestado para este registro)
        $activeLoanAssetIds = AssetLoan::where('status', 'PRESTADO')
            ->where('id', '!=', $loan->id)
            ->pluck('asset_id')
            ->toArray();

        $availableAssets = Asset::where('area_id', $loan->area_id)
            ->where('estado_operativo', '!=', 'DE_BAJA')
            ->whereNotIn('id', $activeLoanAssetIds)
            ->orderBy('orden')
            ->get();

        return view('admin.assets.loans.edit', compact('loan', 'areas', 'availableAssets', 'systemUsers'));
    }

    public function update(AssetLoanValidate $request, int $id): JsonResponse|RedirectResponse {
        $loan = AssetLoan::findOrFail($id);
        $data = $request->validated();

        // Si cambió el activo, verificar que el nuevo no esté prestado por otro registro
        if ((int)$data['asset_id'] !== (int)$loan->asset_id) {
            $alreadyLoaned = AssetLoan::where('asset_id', $data['asset_id'])
                ->where('status', 'PRESTADO')
                ->where('id', '!=', $loan->id)
                ->exists();

            if ($alreadyLoaned) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status' => false,
                        'msg'    => 'El nuevo bien seleccionado ya se encuentra prestado en otro registro.',
                        'type'   => 'warning',
                    ], 422);
                }
                return back()->withInput()->with('toast_error', 'El nuevo bien seleccionado ya se encuentra prestado en otro registro.');
            }
        }

        // Si el estado se marca como DEVUELTO y no hay fecha de devolución real, asignar actual
        if (in_array($data['status'] ?? '', ['DEVUELTO', 'DEVUELTO_OBSERVADO'])) {
            if (empty($data['actual_return_date'])) {
                $data['actual_return_date'] = now();
            }
            if (empty($loan->received_by_user_id)) {
                $data['received_by_user_id'] = Auth::id();
            }

            // Opcional: Actualizar condición del bien patrimonial en el inventario
            if ($request->boolean('update_asset_condition') && !empty($data['return_condition'])) {
                $loan->asset?->update(['condicion' => $data['return_condition']]);
            }
        }

        $loan->update($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => true,
                'message' => 'El préstamo fue actualizado exitosamente.',
                'loan'    => $loan,
            ]);
        }

        return redirect()->route('asset_loans.index', ['area_id' => $loan->area_id])
            ->with('toast_success', 'El préstamo fue actualizado correctamente.');
    }

    public function registerReturn(Request $request, int $id): JsonResponse {
        $loan = AssetLoan::with('asset')->findOrFail($id);
        $request->validate([
            'return_condition'       => ['required', 'string', 'in:B,R,M,BAJA'],
            'actual_return_date'     => ['nullable', 'date'],
            'return_observations'    => ['nullable', 'string', 'max:1000'],
            'update_asset_condition' => ['nullable', 'boolean'],
        ]);

        $returnCond = $request->input('return_condition');
        $status = ($returnCond === 'M' || $request->filled('return_observations')) ? 'DEVUELTO_OBSERVADO' : 'DEVUELTO';

        $loan->status              = $status;
        $loan->return_condition    = $returnCond;
        $loan->actual_return_date  = $request->filled('actual_return_date') ? Carbon::parse($request->input('actual_return_date')) : now();
        $loan->return_observations = $request->input('return_observations');
        $loan->received_by_user_id = Auth::id();
        $loan->save();

        if ($request->boolean('update_asset_condition') && $loan->asset) {
            $loan->asset->condicion = $returnCond;
            $loan->asset->save();
        }

        return response()->json([
            'status'  => true,
            'message' => "Devolución del bien ({$loan->loan_code}) registrada exitosamente.",
        ]);
    }

    public function delete(Request $request): JsonResponse {
        $id     = $request->input('id');
        $loan   = AssetLoan::findOrFail($id);
        $loan->delete();

        return response()->json([
            'status'  => true,
            'message' => 'El registro de préstamo fue eliminado correctamente.',
        ]);
    }

    public function printLoanPdf(int $id): Response {
        $loan     = AssetLoan::with(['asset.area', 'area.head', 'user', 'borrowerUser', 'receivedByUser'])->findOrFail($id);
        $business = Business::first();
        $pdf      = Pdf::loadView('admin.assets.loans.pdf.loan_slip', compact('loan', 'business'))
            ->setPaper('a4', 'portrait');

        $filename = 'PAPELETA_PRESTAMO_' . Str::slug($loan->loan_code) . '.pdf';
        return $pdf->stream($filename);
    }
}
