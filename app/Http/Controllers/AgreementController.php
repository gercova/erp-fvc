<?php

namespace App\Http\Controllers;

use App\Enums\AgreementDocumentType;
use App\Enums\AgreementStatus;
use App\Enums\AgreementType;
use App\Enums\ObligationResponsibleParty;
use App\Enums\ObligationStatus;
use App\Http\Requests\AddendumValidate;
use App\Http\Requests\AgreementValidate;
use App\Models\Agreement;
use App\Models\AgreementAddendum;
use App\Models\AgreementDocument;
use App\Models\AgreementObligation;
use App\Models\Area;
use App\Models\Client;
use App\Models\ProductiveActivity;
use App\Models\User;
use App\Services\Agreements\AgreementCodeService;
use App\Services\Agreements\AgreementFileService;
use App\Services\DocumentApprovalService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class AgreementController extends Controller
{
    public function __construct(
        protected AgreementCodeService $codeService,
        protected AgreementFileService $fileService,
        protected DocumentApprovalService $approvalService
    ) {}

    /**
     * Display a listing of agreements with KPIs and DataTables.
     */
    public function index(Request $request): View {
        $user           = Auth::user();
        $isBroadScope   = $user->hasRole(['SUPERADMIN', 'ADMIN', 'DIRECTOR_GENERAL', 'ADMINISTRACION', 'ADMINISTRATION', 'CONTABILIDAD', 'ACCOUNTING']);
        // Scope query according to access privileges
        $baseQuery      = Agreement::query();
        if (!$isBroadScope) {
            $allowedIds = $request->attributes->get('allowed_agreement_ids') ?? [];
            $baseQuery->whereIn('id', $allowedIds);
        }

        // KPIs calculation
        $activeQuery        = (clone $baseQuery)->where('status', AgreementStatus::ACTIVE);
        $kpiActive          = $activeQuery->count();
        $expiringQuery      = (clone $baseQuery)->where(function ($q) {
            $q->where('status', AgreementStatus::EXPIRING_SOON)
              ->orWhere(function ($sub) {
                  $sub->where('status', AgreementStatus::ACTIVE)
                      ->where('end_date', '<=', Carbon::now()->addDays(30))
                      ->where('end_date', '>=', Carbon::now());
              });
        });
        $kpiExpiringSoon = $expiringQuery->count();

        $kpiTotalAmount = (clone $baseQuery)
            ->whereIn('status', [AgreementStatus::ACTIVE, AgreementStatus::EXPIRING_SOON])
            ->sum('total_amount');

        // Obligation progress
        $obligationQuery = AgreementObligation::whereHas('agreement', function ($q) use ($isBroadScope, $request) {
            if (!$isBroadScope) {
                $allowedIds = $request->attributes->get('allowed_agreement_ids') ?? [];
                $q->whereIn('id', $allowedIds);
            }
        });
        $totalObligations       = (clone $obligationQuery)->count();
        $completedObligations   = (clone $obligationQuery)->where('status', ObligationStatus::COMPLETED->value)->count();
        $kpiObligationProgress  = $totalObligations > 0
            ? round(($completedObligations / $totalObligations) * 100, 1)
            : 0;

        $areas = Area::orderBy('name')->get();
        $types = AgreementType::cases();
        $statuses = AgreementStatus::cases();

        return view('admin.agreements.index', compact(
            'kpiActive',
            'kpiExpiringSoon',
            'kpiTotalAmount',
            'kpiObligationProgress',
            'areas',
            'types',
            'statuses',
            'isBroadScope'
        ));
    }

    /**
     * Provide JSON dataset for DataTables.
     */
    public function data(Request $request): JsonResponse {
        $user = Auth::user();
        $isBroadScope = $user->hasRole(['SUPERADMIN', 'ADMIN', 'DIRECTOR_GENERAL', 'ADMINISTRACION', 'ADMINISTRATION', 'CONTABILIDAD', 'ACCOUNTING']);

        $query = Agreement::with(['client', 'area', 'coordinator', 'obligations'])
            ->when(!$isBroadScope, function ($q) use ($request) {
                $allowedIds = $request->attributes->get('allowed_agreement_ids') ?? [];
                $q->whereIn('id', $allowedIds);
            })
            ->when($request->filled('filter_type'), function ($q) use ($request) {
                $q->where('type', $request->input('filter_type'));
            })
            ->when($request->filled('filter_status'), function ($q) use ($request) {
                $q->where('status', $request->input('filter_status'));
            })
            ->when($request->filled('filter_area'), function ($q) use ($request) {
                $q->where('area_id', $request->input('filter_area'));
            })
            ->when($request->filled('filter_search'), function ($q) use ($request) {
                $term = trim($request->input('filter_search'));
                $q->where(function ($sub) use ($term) {
                    $sub->where('code', 'like', "%{$term}%")
                        ->orWhere('title', 'like', "%{$term}%")
                        ->orWhere('objective', 'like', "%{$term}%")
                        ->orWhereHas('client', function ($cq) use ($term) {
                            $cq->where('nombres', 'like', "%{$term}%")
                               ->orWhere('nro_documento', 'like', "%{$term}%");
                        });
                });
            });

        return DataTables::of($query)
            ->addColumn('codigo', function (Agreement $agreement) {
                $url = route('agreements.show', $agreement->id);
                return '<a href="' . $url . '" class="fw-bold text-primary text-decoration-none">' . e($agreement->code) . '</a>';
            })
            ->addColumn('tipo_alcance', function (Agreement $agreement) {
                $typeBadge = $agreement->isFramework()
                    ? '<span class="badge bg-purple text-white" style="background:#6f42c1;">Marco</span>'
                    : '<span class="badge bg-primary">Específico</span>';
                return '<div class="d-flex flex-column align-items-start gap-1">' .
                    $typeBadge .
                    '<small class="text-muted">' . e($agreement->scope ?? 'Nacional') . '</small>' .
                    '</div>';
            })
            ->addColumn('nombre_convenio', function (Agreement $agreement) {
                return '<div class="fw-semibold text-dark">' . e($agreement->name) . '</div>' .
                    '<small class="text-muted text-truncate d-block" style="max-width: 280px;" title="' . e($agreement->objective) . '">' . e($agreement->objective) . '</small>';
            })
            ->addColumn('contraparte', function (Agreement $agreement) {
                if (!$agreement->client) {
                    return '<span class="text-muted">No asignada</span>';
                }
                return '<div class="fw-semibold">' . e($agreement->client->nombres) . '</div>' .
                    '<small class="badge bg-light text-secondary border">RUC/Doc: ' . e($agreement->client->nro_documento) . '</small>';
            })
            ->addColumn('departamento_responsable', function (Agreement $agreement) {
                $areaName = $agreement->area?->name ?? 'Institucional';
                $leadName = $agreement->coordinator?->nombres ?? 'Sin asignar';
                return '<div><div class="fw-semibold">' . e($areaName) . '</div><small class="text-muted"><i class="fas fa-user-tie me-1"></i>' . e($leadName) . '</small></div>';
            })
            ->addColumn('vigencia', function (Agreement $agreement) {
                $start = $agreement->start_date ? $agreement->start_date->format('d/m/Y') : '-';
                $end = $agreement->end_date ? $agreement->end_date->format('d/m/Y') : '-';
                $days = $agreement->daysRemaining();

                $badgeClass = 'bg-secondary';
                $badgeText = "{$days} días";
                if ($agreement->status === AgreementStatus::EXPIRED || $days < 0) {
                    $badgeClass = 'bg-danger';
                    $badgeText = 'Vencido';
                } elseif ($days <= 30) {
                    $badgeClass = 'bg-warning text-dark';
                    $badgeText = "{$days} días (Próx. a vencer)";
                } else {
                    $badgeClass = 'bg-success';
                    $badgeText = "{$days} días vigentes";
                }

                return '<div class="d-flex flex-column">' .
                    '<span class="text-nowrap small"><i class="far fa-calendar-alt me-1"></i>' . $start . ' al ' . $end . '</span>' .
                    '<span class="badge ' . $badgeClass . ' mt-1 align-self-start">' . $badgeText . '</span>' .
                    '</div>';
            })
            ->addColumn('monto', function (Agreement $agreement) {
                $currencySymbol = match ($agreement->currency) {
                    'USD' => '$',
                    'EUR' => '€',
                    default => 'S/',
                };
                return '<span class="fw-bold text-dark">' . $currencySymbol . ' ' . number_format($agreement->total_amount, 2) . '</span>';
            })
            ->addColumn('compromisos_progreso', function (Agreement $agreement) {
                $total = $agreement->obligations->count();
                if ($total === 0) {
                    return '<small class="text-muted">Sin compromisos</small>';
                }
                $completed = $agreement->obligations->where('status', ObligationStatus::COMPLETED)->count();
                $percent = round(($completed / $total) * 100);
                $barColor = $percent === 100 ? 'bg-success' : ($percent >= 50 ? 'bg-info' : 'bg-warning');

                return '<div style="min-width: 110px;">' .
                    '<div class="d-flex justify-content-between small mb-1">' .
                    '<span class="fw-bold">' . $percent . '%</span>' .
                    '<span class="text-muted">' . $completed . '/' . $total . '</span>' .
                    '</div>' .
                    '<div class="progress" style="height: 6px;">' .
                    '<div class="progress-bar ' . $barColor . '" role="progressbar" style="width: ' . $percent . '%;"></div>' .
                    '</div>' .
                    '</div>';
            })
            ->addColumn('estado', function (Agreement $agreement) {
                $status = $agreement->status;
                $statusValue = $status instanceof AgreementStatus ? $status->value : (string) $status;
                $statusMap = [
                    'DRAFT'         => ['class' => 'bg-secondary', 'label' => 'Borrador'],
                    'IN_APPROVAL'   => ['class' => 'bg-warning text-dark', 'label' => 'En Aprobación'],
                    'ACTIVE'        => ['class' => 'bg-success', 'label' => 'Activo / Vigente'],
                    'EXPIRING_SOON' => ['class' => 'bg-warning text-dark', 'label' => 'Por Vencer'],
                    'EXPIRED'       => ['class' => 'bg-danger', 'label' => 'Vencido'],
                    'SETTLED'       => ['class' => 'bg-info text-dark', 'label' => 'Liquidado'],
                    'TERMINATED'    => ['class' => 'bg-dark', 'label' => 'Resuelto / Terminado'],
                    'REJECTED'      => ['class' => 'bg-danger', 'label' => 'Rechazado'],
                ];
                $meta = $statusMap[$statusValue] ?? ['class' => 'bg-light text-dark', 'label' => $statusValue];
                return '<span class="badge ' . $meta['class'] . '">' . e($meta['label']) . '</span>';
            })
            ->addColumn('acciones', function (Agreement $agreement) {
                $showUrl = route('agreements.show', $agreement->id);
                $editUrl = route('agreements.edit', $agreement->id);

                $btns = '<div class="btn-group btn-group-sm" role="group">';
                $btns .= '<a href="' . $showUrl . '" class="btn btn-outline-primary" title="Ver Expediente"><i class="fas fa-eye"></i></a>';

                if ($agreement->status === AgreementStatus::DRAFT || auth()->user()->can('agreements.manage')) {
                    $btns .= '<a href="' . $editUrl . '" class="btn btn-outline-secondary" title="Editar"><i class="fas fa-edit"></i></a>';
                }

                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['codigo', 'tipo_alcance', 'nombre_convenio', 'contraparte', 'departamento_responsable', 'vigencia', 'monto', 'compromisos_progreso', 'estado', 'acciones'])
            ->make(true);
    }

    /**
     * Show form for creating a new agreement.
     */
    public function create(): View {
        $clients            = Client::whereNotNull('nro_documento')->orderBy('nombres')->get();
        $areas              = Area::orderBy('name')->get();
        $users              = User::orderBy('nombres')->get();
        $productiveActivities   = ProductiveActivity::where('status', 'ACTIVA')->orderBy('name')->get();
        $frameworkAgreements    = Agreement::where('type', AgreementType::FRAMEWORK)
            ->whereIn('status', [AgreementStatus::ACTIVE, AgreementStatus::DRAFT])
            ->get();

        return view('admin.agreements.create', compact('clients', 'areas', 'users', 'productiveActivities', 'frameworkAgreements'));
    }

    /**
     * Store a newly created agreement in storage.
     */
    public function store(AgreementValidate $request): RedirectResponse {
        $data = $request->validated();

        $code = !empty($data['code'])
            ? $data['code']
            : $this->codeService->generateAgreementCode((int) date('Y', strtotime($data['start_date'])));

        $agreement = DB::transaction(function () use ($data, $code, $request) {
            $agreement = Agreement::create([
                'code'                     => $code,
                'type'                     => $data['type'],
                'scope'                    => $data['scope'],
                'name'                     => $data['name'],
                'objective'                => $data['objective'],
                'client_id'                => $data['client_id'],
                'parent_agreement_id'      => $data['parent_agreement_id'] ?? null,
                'area_id'                  => $data['area_id'],
                'coordinator_user_id'      => $data['coordinator_user_id'] ?? Auth::id(),
                'created_by_user_id'       => Auth::id(),
                'productive_activity_id'   => $data['productive_activity_id'] ?? null,
                'start_date'               => $data['start_date'],
                'end_date'                 => $data['end_date'],
                'signing_date'             => $data['signing_date'] ?? null,
                'currency'                 => $data['currency'],
                'total_amount'             => $data['total_amount'],
                'status'                   => AgreementStatus::DRAFT,
                'has_economic_obligation'  => $request->boolean('has_economic_obligation'),
                'requires_mutual_reports'  => $request->boolean('requires_mutual_reports'),
                'key_clauses'              => $data['key_clauses'] ?? null,
                'termination_conditions'   => $data['termination_conditions'] ?? null,
                'notes'                    => $data['notes'] ?? null,
            ]);

            // Initial audit log
            $agreement->transitionStatus(
                AgreementStatus::DRAFT,
                'Registro inicial del convenio institucional en borrador',
                Auth::user(),
                'AGREEMENT_CREATED'
            );

            // If initial file uploaded
            if ($request->hasFile('signed_document')) {
                $this->fileService->storeDocument(
                    $agreement,
                    $request->file('signed_document'),
                    AgreementDocumentType::CONVENIO_FIRMADO,
                    'Convenio Firmado / Proyecto Inicial',
                    Auth::user()
                );
            }

            return $agreement;
        });

        return redirect()->route('agreements.show', $agreement->id)
            ->with('success', "Convenio institucional {$agreement->code} registrado exitosamente.");
    }

    /**
     * Display the specified agreement with full details.
     */
    public function show(int $id): View {
        $agreement = Agreement::with([
            'client.tipoDocumento',
            'area.head',
            'coordinator',
            'creator',
            'productiveActivity',
            'parentAgreement',
            'specificAgreements',
            'addenda.creator',
            'obligations.verifier',
            'installments',
            'documents.uploader',
            'auditLogs.user',
            'approvals.approver',
        ])->findOrFail($id);

        $currentUser = Auth::user();
        $userRoles = $currentUser->roles->pluck('name')->toArray();
        $isSuper = in_array('SUPERADMIN', $userRoles) || in_array('ADMIN', $userRoles);

        $canManage = $isSuper || $currentUser->can('agreements.manage') || $agreement->coordinator_user_id === $currentUser->id;
        $canSubmitApproval = ($agreement->status === AgreementStatus::DRAFT || $agreement->status === AgreementStatus::REJECTED) &&
            ($isSuper || $agreement->coordinator_user_id === $currentUser->id || $agreement->created_by_user_id === $currentUser->id);

        $responsibleParties = ObligationResponsibleParty::cases();
        $obligationStatuses = ObligationStatus::cases();
        $documentTypes      = AgreementDocumentType::cases();

        return view('admin.agreements.show', compact(
            'agreement',
            'canManage',
            'canSubmitApproval',
            'responsibleParties',
            'obligationStatuses',
            'documentTypes'
        ));
    }

    /**
     * Show the form for editing an agreement.
     */
    public function edit(int $id): View {
        $agreement  = Agreement::findOrFail($id);
        $clients    = Client::whereNotNull('nro_documento')->orderBy('nombres')->get();
        $areas      = Area::orderBy('name')->get();
        $users      = User::orderBy('nombres')->get();
        $productiveActivities   = ProductiveActivity::where('status', 'ACTIVA')->orderBy('name')->get();
        $frameworkAgreements    = Agreement::where('type', AgreementType::FRAMEWORK)
            ->where('id', '!=', $id)
            ->get();

        return view('admin.agreements.edit', compact('agreement', 'clients', 'areas', 'users', 'productiveActivities', 'frameworkAgreements'));
    }

    /**
     * Update the specified agreement in storage.
     */
    public function update(AgreementValidate $request, int $id): RedirectResponse {
        $agreement = Agreement::findOrFail($id);
        $data      = $request->validated();

        $agreement->update([
            'name'                     => $data['name'],
            'type'                     => $data['type'],
            'scope'                    => $data['scope'],
            'objective'                => $data['objective'],
            'client_id'                => $data['client_id'],
            'parent_agreement_id'      => $data['parent_agreement_id'] ?? null,
            'area_id'                  => $data['area_id'],
            'coordinator_user_id'      => $data['coordinator_user_id'] ?? $agreement->coordinator_user_id,
            'productive_activity_id'   => $data['productive_activity_id'] ?? null,
            'start_date'               => $data['start_date'],
            'end_date'                 => $data['end_date'],
            'signing_date'             => $data['signing_date'] ?? null,
            'currency'                 => $data['currency'],
            'total_amount'             => $data['total_amount'],
            'has_economic_obligation'  => $request->boolean('has_economic_obligation'),
            'requires_mutual_reports'  => $request->boolean('requires_mutual_reports'),
            'key_clauses'              => $data['key_clauses'] ?? null,
            'termination_conditions'   => $data['termination_conditions'] ?? null,
            'notes'                    => $data['notes'] ?? null,
        ]);

        $agreement->transitionStatus(
            $agreement->status,
            'Actualización de datos y cláusulas generales del convenio',
            Auth::user(),
            'AGREEMENT_UPDATED'
        );

        return redirect()->route('agreements.show', $agreement->id)
            ->with('success', 'Convenio actualizado correctamente.');
    }

    /**
     * Remove the specified agreement from storage.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse {
        $agreement = Agreement::findOrFail($id);
        $agreement->transitionStatus(
            $agreement->status,
            'Eliminación lógica del convenio institucional',
            Auth::user(),
            'AGREEMENT_DELETED'
        );

        $agreement->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Convenio eliminado correctamente.']);
        }

        return redirect()->route('agreements.index')
            ->with('success', 'Convenio institucional eliminado.');
    }

    /**
     * Submit agreement to hierarchical approval workflow.
     */
    public function submitApproval(Request $request, int $id): JsonResponse|RedirectResponse {
        $agreement = Agreement::findOrFail($id);

        if (!in_array($agreement->status, [AgreementStatus::DRAFT, AgreementStatus::REJECTED], true)) {
            $msg = 'Solo los convenios en estado Borrador o Rechazado pueden enviarse a aprobación.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        if (!$agreement->client || empty($agreement->client->nro_documento)) {
            $msg = 'El convenio debe contar con una contraparte y RUC válido antes de ser enviado a aprobación.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        DB::transaction(function () use ($agreement) {
            // Transition status to IN_APPROVAL with audit log
            $agreement->transitionStatus(
                AgreementStatus::IN_APPROVAL,
                'Envío formal del expediente a la cadena jerárquica de visación y aprobación',
                Auth::user(),
                'SUBMIT_APPROVAL'
            );

            // Generate document approval workflow (steps 1..4)
            $this->approvalService->generateWorkflow($agreement, Auth::user());
        });

        $msg = 'El convenio ha sido enviado formalmente a la cadena jerárquica de aprobación.';
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
    }

    /**
     * Store an addendum with validity period and amount recalculation.
     */
    public function storeAddendum(AddendumValidate $request, int $id): JsonResponse|RedirectResponse {
        $agreement = Agreement::findOrFail($id);
        $data = $request->validated();

        $addendum = DB::transaction(function () use ($agreement, $data, $request) {
            $addendum = AgreementAddendum::create([
                'agreement_id'      => $agreement->id,
                'type'              => $data['type'],
                'resolution_number' => $data['resolution_number'] ?? null,
                'justification'     => $data['justification'],
                'new_end_date'      => $data['new_end_date'] ?? null,
                'amount_delta'      => $data['amount_delta'] ?? 0.00,
                'signature_date'    => $data['signature_date'] ?? now()->toDateString(),
                'created_by_user_id'=> Auth::id(),
            ]);

            // If file attached, store in digital archive
            if ($request->hasFile('file')) {
                $this->fileService->storeDocument(
                    $agreement,
                    $request->file('file'),
                    AgreementDocumentType::ADDENDUM,
                    "Adenda {$addendum->code} - " . ($data['resolution_number'] ?? 'Documento Sustentatorio'),
                    Auth::user()
                );
            }

            return $addendum;
        });

        $msg = "Adenda {$addendum->code} registrada y aplicada exitosamente.";
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'addendum' => $addendum])
            : back()->with('success', $msg);
    }

    /**
     * Store an institutional obligation / clause.
     */
    public function storeObligation(Request $request, int $id): JsonResponse|RedirectResponse {
        $agreement = Agreement::findOrFail($id);
        $request->validate([
            'clause_reference'  => 'nullable|string|max:50',
            'responsible_party' => 'required|in:OUR_INSTITUTION,COUNTERPARTY,MUTUAL',
            'title'             => 'required|string|max:200',
            'description'       => 'nullable|string',
            'due_date'          => 'nullable|date',
        ]);

        $obligation = $agreement->obligations()->create([
            'clause_reference'  => $request->input('clause_reference'),
            'responsible_party' => $request->input('responsible_party'),
            'title'             => $request->input('title'),
            'description'       => $request->input('description'),
            'due_date'          => $request->input('due_date'),
            'status'            => ObligationStatus::PENDING,
        ]);

        $agreement->transitionStatus(
            $agreement->status,
            "Registro de compromiso / cláusula: {$obligation->title}",
            Auth::user(),
            'OBLIGATION_ADDED'
        );

        $msg = 'Compromiso registrado exitosamente.';
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'obligation' => $obligation])
            : back()->with('success', $msg);
    }

    /**
     * Update status of an obligation.
     */
    public function updateObligationStatus(Request $request, int $id, int $obligationId): JsonResponse {
        $agreement  = Agreement::findOrFail($id);
        $obligation = $agreement->obligations()->findOrFail($obligationId);

        $request->validate([
            'status'         => 'required|in:PENDING,IN_PROGRESS,COMPLETED,WAIVED,BREACHED',
            'evidence_notes' => 'nullable|string',
        ]);

        $newStatus = $request->input('status');
        $obligation->update([
            'status'              => $newStatus,
            'evidence_notes'      => $request->input('evidence_notes'),
            'verified_by_user_id' => Auth::id(),
            'completed_at'        => $newStatus === ObligationStatus::COMPLETED->value ? now() : null,
        ]);

        $agreement->transitionStatus(
            $agreement->status,
            "Actualización de compromiso '{$obligation->title}' a estado {$newStatus}",
            Auth::user(),
            'OBLIGATION_STATUS_UPDATED'
        );

        return response()->json([
            'success'       => true,
            'message'       => 'Estado del compromiso actualizado exitosamente.',
            'obligation'    => $obligation,
        ]);
    }

    /**
     * Upload an agreement document to private storage.
     */
    public function uploadDocument(Request $request, int $id): JsonResponse|RedirectResponse {
        $agreement = Agreement::findOrFail($id);
        $request->validate([
            'document_type' => 'required|string',
            'title'         => 'required|string|max:200',
            'file'          => 'required|file|max:20480',
        ]);

        $doc = $this->fileService->storeDocument(
            $agreement,
            $request->file('file'),
            $request->input('document_type'),
            $request->input('title'),
            Auth::user()
        );

        $agreement->transitionStatus(
            $agreement->status,
            "Subida de documento: {$doc->title} ({$doc->document_type->value})",
            Auth::user(),
            'DOCUMENT_UPLOADED'
        );

        $msg = 'Documento adjuntado exitosamente al expediente.';
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'document' => $doc])
            : back()->with('success', $msg);
    }

    /**
     * Download an agreement document from private storage.
     */
    public function downloadDocument(int $id, int $documentId): StreamedResponse {
        $agreement  = Agreement::findOrFail($id);
        $document   = $agreement->documents()->findOrFail($documentId);
        return $this->fileService->downloadDocument($document, Auth::user());
    }

    /**
     * Transition agreement status manually with audit log.
     */
    public function changeStatus(Request $request, int $id): JsonResponse|RedirectResponse {
        $agreement  = Agreement::findOrFail($id);
        $request->validate([
            'new_status' => 'required|in:ACTIVE,EXPIRING_SOON,EXPIRED,SETTLED,TERMINATED,REJECTED,DRAFT',
            'reason'     => 'required|string|max:500',
        ]);

        $newStatus  = AgreementStatus::from($request->input('new_status'));
        $reason     = $request->input('reason');

        $agreement->transitionStatus(
            $newStatus,
            $reason,
            Auth::user(),
            'STATUS_CHANGE_MANUAL'
        );

        $msg = "Estado del convenio actualizado a {$newStatus->value}.";
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
    }
}
