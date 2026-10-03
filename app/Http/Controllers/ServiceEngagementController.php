<?php

namespace App\Http\Controllers;

use App\Enums\ServiceDeliverableStatus;
use App\Enums\ServiceEngagementStatus;
use App\Http\Requests\ServiceEngagementValidate;
use App\Models\Agreement;
use App\Models\Client;
use App\Models\ProductiveActivity;
use App\Models\ServiceAttendee;
use App\Models\ServiceDeliverable;
use App\Models\ServiceEngagement;
use App\Models\ServiceHourLog;
use App\Models\ServiceSession;
use App\Models\TechnologicalService;
use App\Models\User;
use App\Services\Services\CertificateService;
use App\Services\Services\ParticipantImportService;
use App\Services\Services\ServiceReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ServiceEngagementController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService,
        protected ParticipantImportService $importService,
        protected ServiceReportService $reportService
    ) {}

    /**
     * Display service engagements catalog and operational KPIs.
     */
    public function index(Request $request): View {
        $totalEngagements       = ServiceEngagement::count();
        $inProgressCount        = ServiceEngagement::where('status', ServiceEngagementStatus::IN_PROGRESS)->count();
        $completedCount         = ServiceEngagement::where('status', ServiceEngagementStatus::COMPLETED)->count();
        $totalAmount            = (float) ServiceEngagement::sum('total_amount');
        $totalContractedHours   = (float) ServiceEngagement::sum('contracted_hours');
        $totalConsumedHours     = (float) ServiceEngagement::sum('consumed_hours');
        $lowBalanceCount        = ServiceEngagement::where('status', ServiceEngagementStatus::IN_PROGRESS)
            ->where('contracted_hours', '>', 0)
            ->whereRaw('(contracted_hours - consumed_hours) <= low_balance_threshold_hours')
            ->count();

        $services       = TechnologicalService::where('is_active', true)->orderBy('name')->get();
        $agreements     = Agreement::whereIn('status', ['ACTIVE', 'EXPIRING_SOON'])->orderBy('code')->get();

        return view('admin.services.engagements.index', compact(
            'totalEngagements',
            'inProgressCount',
            'completedCount',
            'totalAmount',
            'totalContractedHours',
            'totalConsumedHours',
            'lowBalanceCount',
            'services',
            'agreements'
        ));
    }

    /**
     * Server-side DataTables provider for service engagements.
     */
    public function data(Request $request): JsonResponse {
        $query = ServiceEngagement::with(['client', 'technologicalService', 'agreement', 'responsibleUser'])
            ->when($request->filled('filter_status'), function ($q) use ($request) {
                $q->where('status', $request->input('filter_status'));
            })
            ->when($request->filled('filter_service'), function ($q) use ($request) {
                $q->where('technological_service_id', $request->input('filter_service'));
            })
            ->when($request->filled('filter_agreement'), function ($q) use ($request) {
                $q->where('agreement_id', $request->input('filter_agreement'));
            })
            ->when($request->filled('filter_search'), function ($q) use ($request) {
                $term = trim($request->input('filter_search'));
                $q->where(function ($sub) use ($term) {
                    $sub->where('code', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('client', fn($cq) => $cq->where('nombres', 'like', "%{$term}%")->orWhere('nro_documento', 'like', "%{$term}%"))
                        ->orWhereHas('technologicalService', fn($sq) => $sq->where('name', 'like', "%{$term}%"));
                });
            });

        return DataTables::of($query)
            ->addColumn('codigo', function (ServiceEngagement $eng) {
                $url = route('services.engagements.show', $eng->id);
                return '<a href="' . $url . '" class="fw-bold text-primary text-decoration-none">' . e($eng->code) . '</a>';
            })
            ->addColumn('servicio', function (ServiceEngagement $eng) {
                $svcName = $eng->technologicalService?->name ?? 'Servicio Tecnológico';
                $agreementBadge = $eng->agreement_id
                    ? '<span class="badge bg-purple text-white ms-1" style="background:#6f42c1;">Convenio: ' . e($eng->agreement?->code) . '</span>'
                    : '<span class="badge bg-secondary ms-1">Servicio Directo</span>';
                return '<div><div class="fw-semibold text-dark">' . e($svcName) . '</div>' . $agreementBadge . '</div>';
            })
            ->addColumn('cliente', function (ServiceEngagement $eng) {
                $cli = $eng->client;
                return '<div><div class="fw-semibold">' . e($cli?->nombres ?? 'No asignado') . '</div>' .
                    '<small class="text-muted">Doc: ' . e($cli?->nro_documento ?? '-') . '</small></div>';
            })
            ->addColumn('modalidad_plazo', function (ServiceEngagement $eng) {
                $start = $eng->start_date ? $eng->start_date->format('d/m/Y') : '-';
                $end   = $eng->expected_delivery_date ? $eng->expected_delivery_date->format('d/m/Y') : '-';
                return '<div><span class="badge bg-light text-dark border">' . e($eng->delivery_modality ?? 'IN_PERSON') . '</span>' .
                    '<div class="small text-muted mt-1">' . $start . ' al ' . $end . '</div></div>';
            })
            ->addColumn('bolsa_horas', function (ServiceEngagement $eng) {
                if ((float)$eng->contracted_hours <= 0) {
                    return '<span class="text-muted small">Sin bolsa de horas</span>';
                }
                $consumed = (float) $eng->consumed_hours;
                $total    = (float) $eng->contracted_hours;
                $percent  = min(100, round(($consumed / $total) * 100, 1));
                $lowAlert = $eng->isLowBalance()
                    ? '<span class="badge bg-danger ms-1 animate__animated animate__pulse animate__infinite">Bolsa Baja</span>'
                    : '';

                $barClass = $percent >= 90 ? 'bg-danger' : ($percent >= 75 ? 'bg-warning' : 'bg-success');

                return '<div>' .
                    '<div class="d-flex justify-content-between small mb-1">' .
                    '<span><strong>' . $consumed . '</strong> / ' . $total . ' hrs</span>' .
                    $lowAlert .
                    '</div>' .
                    '<div class="progress" style="height: 6px;">' .
                    '<div class="progress-bar ' . $barClass . '" style="width:' . $percent . '%;"></div>' .
                    '</div>' .
                    '</div>';
            })
            ->addColumn('monto', function (ServiceEngagement $eng) {
                return '<div class="fw-bold text-dark">' . e($eng->currency) . ' ' . number_format($eng->total_amount, 2) . '</div>';
            })
            ->addColumn('estado', function (ServiceEngagement $eng) {
                $status = $eng->status instanceof ServiceEngagementStatus ? $eng->status->value : (string)$eng->status;
                $badgeClass = match ($status) {
                    'IN_PROGRESS' => 'bg-primary',
                    'COMPLETED'   => 'bg-success',
                    'CANCELLED'   => 'bg-danger',
                    default       => 'bg-secondary',
                };
                $label = match ($status) {
                    'IN_PROGRESS' => 'En Ejecución',
                    'COMPLETED'   => 'Concluido',
                    'CANCELLED'   => 'Cancelado',
                    default       => 'Borrador',
                };
                return '<span class="badge ' . $badgeClass . '">' . $label . '</span>';
            })
            ->addColumn('acciones', function (ServiceEngagement $eng) {
                $showUrl = route('services.engagements.show', $eng->id);
                return '<a href="' . $showUrl . '" class="btn btn-sm btn-outline-primary" title="Ver Detalles"><i class="fas fa-eye"></i></a>';
            })
            ->rawColumns(['codigo', 'servicio', 'cliente', 'modalidad_plazo', 'bolsa_horas', 'monto', 'estado', 'acciones'])
            ->make(true);
    }

    /**
     * Show form for creating a new service engagement.
     */
    public function create(): View {
        $clients        = Client::orderBy('nombres')->get();
        $services       = TechnologicalService::where('is_active', true)->with('productiveActivity')->orderBy('name')->get();
        $agreements     = Agreement::whereIn('status', ['ACTIVE', 'EXPIRING_SOON'])->orderBy('code')->get();
        $specialists    = User::where('estado', 1)->orderBy('nombres')->get();
        $activities     = ProductiveActivity::where('status', 'ACTIVA')->orderBy('name')->get();
        return view('admin.services.engagements.create', compact(
            'clients',
            'services',
            'agreements',
            'specialists',
            'activities'
        ));
    }

    /**
     * Store newly created service engagement.
     */
    public function store(ServiceEngagementValidate $request): RedirectResponse {
        $data = $request->validated();

        $service = TechnologicalService::findOrFail($data['technological_service_id']);
        if (empty($data['productive_activity_id'])) {
            $data['productive_activity_id'] = $service->productive_activity_id;
        }

        $quantity       = (float) ($data['quantity'] ?? 1.00);
        $unitPrice      = (float) $data['unit_price'];
        $totalAmount    = (float) ($data['total_amount'] ?? ($quantity * $unitPrice));
        $engagement     = DB::transaction(function () use ($data, $quantity, $unitPrice, $totalAmount) {
            return ServiceEngagement::create([
                'agreement_id'                => $data['agreement_id'] ?? null,
                'client_id'                   => $data['client_id'],
                'technological_service_id'    => $data['technological_service_id'],
                'productive_activity_id'      => $data['productive_activity_id'],
                'responsible_user_id'         => $data['responsible_user_id'],
                'description'                 => $data['description'],
                'delivery_modality'           => $data['delivery_modality'],
                'contracted_hours'            => $data['contracted_hours'] ?? 0.00,
                'consumed_hours'              => 0.00,
                'hourly_rate'                 => $data['hourly_rate'] ?? null,
                'quantity'                    => $quantity,
                'unit_price'                  => $unitPrice,
                'total_amount'                => $totalAmount,
                'currency'                    => $data['currency'],
                'start_date'                  => $data['start_date'],
                'expected_delivery_date'      => $data['expected_delivery_date'],
                'status'                      => ServiceEngagementStatus::IN_PROGRESS,
                'allow_pool_overage'          => $data['allow_pool_overage'] ?? false,
                'min_attendance_percent'      => $data['min_attendance_percent'] ?? 80.00,
                'low_balance_threshold_hours' => $data['low_balance_threshold_hours'] ?? 5.00,
            ]);
        });

        return redirect()->route('services.engagements.show', $engagement->id)
            ->with('success', "Orden de servicio tecnológico {$engagement->code} creada exitosamente en ejecución.");
    }

    /**
     * Show detailed dashboard of a service engagement.
     */
    public function show(int $id): View {
        $engagement = ServiceEngagement::with([
            'client.tipoDocumento',
            'technologicalService.area',
            'agreement',
            'productiveActivity',
            'responsibleUser',
            'closedBy',
            'sessions.instructor',
            'sessions.attendees',
            'hourLogs.specialist',
            'deliverables.approver',
            'deliverables.clientSignoffUser',
            'billing',
            'saleNote',
        ])->findOrFail($id);

        $specialists    = User::where('estado', 1)->orderBy('nombres')->get();
        $isLowBalance   = $engagement->isLowBalance();
        $remainingHours = $engagement->remainingHours();

        // Get unique participant roster with calculated attendance percent and certificate status
        $uniqueAttendees = $engagement->attendees()
            ->select('dni_or_document', 'full_name', 'email', 'phone', 'organization')
            ->distinct()
            ->get()
            ->map(function ($att) use ($engagement) {
                $att->attendance_percent = $engagement->calculateAttendancePercent($att->dni_or_document);
                $att->is_eligible = $engagement->isEligibleForCertificate($att->dni_or_document);
                $issued = ServiceAttendee::where('service_engagement_id', $engagement->id)
                    ->where('dni_or_document', $att->dni_or_document)
                    ->whereNotNull('certificate_code')
                    ->first();
                $att->certificate_code = $issued?->certificate_code;
                $att->certificate_issued_at = $issued?->certificate_issued_at;
                return $att;
            });

        return view('admin.services.engagements.show', compact(
            'engagement',
            'specialists',
            'isLowBalance',
            'remainingHours',
            'uniqueAttendees'
        ));
    }

    /**
     * Update service engagement parameters.
     */
    public function update(ServiceEngagementValidate $request, int $id): RedirectResponse {
        $engagement = ServiceEngagement::findOrFail($id);
        $data       = $request->validated();
        $engagement->update($data);
        return back()->with('success', "Orden de servicio {$engagement->code} actualizada correctamente.");
    }

    /**
     * Soft delete an engagement.
     */
    public function destroy(int $id): RedirectResponse {
        $engagement = ServiceEngagement::findOrFail($id);
        $engagement->delete();
        return redirect()->route('services.engagements.index')
            ->with('success', 'Orden de servicio tecnológico eliminada.');
    }

    /**
     * Store training session for an engagement.
     */
    public function storeSession(Request $request, int $id): JsonResponse|RedirectResponse {
        $engagement = ServiceEngagement::findOrFail($id);

        $request->validate([
            'topic'              => 'required|string|max:255',
            'instructor_user_id' => 'required|exists:users,id',
            'session_date'       => 'required|date',
            'start_time'         => 'required',
            'end_time'           => 'required',
            'duration_hours'     => 'nullable|numeric|min:0',
            'location'           => 'required|string|max:150',
            'status'             => 'required|in:SCHEDULED,CONDUCTED,CANCELLED,RESCHEDULED',
            'observations'       => 'nullable|string',
        ]);

        $duration = (float) ($request->input('duration_hours') ?? 0);
        if ($duration <= 0 && $request->filled('start_time') && $request->filled('end_time')) {
            try {
                $start = Carbon::parse($request->input('start_time'));
                $end   = Carbon::parse($request->input('end_time'));
                $duration = round(max(0, $end->diffInMinutes($start) / 60), 2);
            } catch (\Throwable $e) {}
        }

        // Verify hours pool if status is CONDUCTED
        if ($request->input('status') === 'CONDUCTED' && $duration > 0) {
            if (!$engagement->canConsumeHours($duration, false)) {
                $msg = "La duración de la sesión ({$duration} hrs) excede la bolsa de horas disponible ({$engagement->remainingHours()} hrs). Se requiere autorización de sobregiro.";
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => $msg], 422)
                    : back()->withErrors(['duration_hours' => $msg]);
            }
        }

        $session = $engagement->sessions()->create([
            'topic'              => $request->input('topic'),
            'instructor_user_id' => $request->input('instructor_user_id'),
            'session_date'       => $request->input('session_date'),
            'start_time'         => $request->input('start_time'),
            'end_time'           => $request->input('end_time'),
            'duration_hours'     => $duration,
            'location'           => $request->input('location'),
            'status'             => $request->input('status'),
            'observations'       => $request->input('observations'),
        ]);

        $msg = "Sesión N° {$session->session_number} registrada correctamente.";
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'session' => $session])
            : back()->with('success', $msg);
    }

    /**
     * Store single participant.
     */
    public function storeAttendee(Request $request, int $id): JsonResponse|RedirectResponse {
        $engagement = ServiceEngagement::findOrFail($id);

        $request->validate([
            'dni_or_document'    => 'required|string|max:20',
            'full_name'          => 'required|string|max:200',
            'email'              => 'nullable|email|max:120',
            'phone'              => 'nullable|string|max:30',
            'organization'       => 'nullable|string|max:150',
            'service_session_id' => 'nullable|exists:service_sessions,id',
            'attended'           => 'nullable|boolean',
        ]);

        $sessionId = $request->input('service_session_id') ?: $engagement->sessions()->first()?->id;

        $attendee = ServiceAttendee::updateOrCreate(
            [
                'service_session_id' => $sessionId,
                'dni_or_document'    => trim($request->input('dni_or_document')),
            ],
            [
                'service_engagement_id' => $engagement->id,
                'full_name'             => trim($request->input('full_name')),
                'email'                 => $request->input('email'),
                'phone'                 => $request->input('phone'),
                'organization'          => $request->input('organization'),
                'attended'              => $request->boolean('attended', true),
            ]
        );

        $msg = "Participante {$attendee->full_name} registrado exitosamente.";
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'attendee' => $attendee])
            : back()->with('success', $msg);
    }

    /**
     * Import participants from Excel or CSV.
     */
    public function importAttendees(Request $request, int $id): JsonResponse|RedirectResponse {
        $engagement = ServiceEngagement::findOrFail($id);

        $request->validate([
            'excel_file'         => 'required|file|mimes:xlsx,xls,csv,txt',
            'service_session_id' => 'nullable|exists:service_sessions,id',
        ]);

        try {
            $result = $this->importService->import(
                $engagement,
                $request->file('excel_file'),
                $request->input('service_session_id') ? (int)$request->input('service_session_id') : null
            );

            $msg = "Importación completada: {$result['imported']} participantes importados, {$result['skipped']} omitidos.";
            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $msg, 'data' => $result])
                : back()->with('success', $msg);
        } catch (\Throwable $e) {
            $msg = "Error al procesar el archivo Excel: " . $e->getMessage();
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['excel_file' => $msg]);
        }
    }

    /**
     * Update session attendance for multiple participants.
     */
    public function updateAttendance(Request $request, int $id, int $sessionId): JsonResponse {
        $engagement     = ServiceEngagement::findOrFail($id);
        $session        = $engagement->sessions()->findOrFail($sessionId);
        $attendanceData = $request->input('attendance', []); // [attendee_id => 1 or 0]

        foreach ($attendanceData as $attId => $attended) {
            ServiceAttendee::where('id', $attId)
                ->where('service_session_id', $session->id)
                ->update(['attended' => (bool)$attended]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Asistencias actualizadas correctamente.',
        ]);
    }

    /**
     * Issue certificate for a participant.
     */
    public function issueCertificate(Request $request, int $id, int $attendeeId): JsonResponse|RedirectResponse {
        $engagement = ServiceEngagement::findOrFail($id);
        $attendee   = ServiceAttendee::where('service_engagement_id', $engagement->id)->findOrFail($attendeeId);

        try {
            $this->certificateService->issueCertificate($attendee);
            $msg = "Certificado {$attendee->certificate_code} emitido satisfactoriamente para {$attendee->full_name}.";
            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $msg, 'certificate_code' => $attendee->certificate_code])
                : back()->with('success', $msg);
        } catch (\DomainException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->withErrors(['certificate' => $e->getMessage()]);
        }
    }

    /**
     * Download or stream PDF certificate.
     */
    public function downloadCertificate(int $id, int $attendeeId) {
        $engagement = ServiceEngagement::findOrFail($id);
        $attendee   = ServiceAttendee::where('service_engagement_id', $engagement->id)->findOrFail($attendeeId);

        if (empty($attendee->certificate_code)) {
            $this->certificateService->issueCertificate($attendee);
        }

        $pdf        = $this->certificateService->generatePdf($attendee);
        $filename   = 'CERTIFICADO_' . Str::slug($attendee->full_name) . '_' . ($attendee->certificate_code ?? 'DOC') . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Public Certificate Verification endpoint (No login required!).
     */
    public function verifyPublicCertificate(string $code): View {
        $data = $this->certificateService->verifyCertificate($code);
        return view('admin.services.certificates.public_verify', compact('data', 'code'));
    }

    /**
     * Store hour log / technical assistance visit and deduct from pool.
     */
    public function storeHourLog(Request $request, int $id): JsonResponse|RedirectResponse {
        $engagement = ServiceEngagement::findOrFail($id);
        
        $request->validate([
            'specialist_user_id'    => 'required|exists:users,id',
            'log_date'              => 'required|date',
            'hours'                 => 'required|numeric|min:0.25',
            'modality'              => 'required|in:FIELD,IN_PERSON,VIRTUAL,LAB',
            'activity_performed'    => 'required|string|max:255',
            'location_or_farm'      => 'nullable|string|max:200',
            'client_contact_name'   => 'nullable|string|max:150',
            'client_signed'         => 'nullable|boolean',
            'observations'          => 'nullable|string',
            'is_authorized_overage' => 'nullable|boolean',
        ]);

        $hours = (float) $request->input('hours');
        $isAuthorized = $request->boolean('is_authorized_overage');

        // CRITICAL ACCEPTANCE CRITERIA: consumed hours never exceed allocated pool without explicit authorization
        if (!$engagement->canConsumeHours($hours, $isAuthorized)) {
            $msg = "Las horas registradas ({$hours} hrs) superan la bolsa de horas contratadas (Disponible: {$engagement->remainingHours()} hrs). Se requiere autorización explícita para sobregiro.";
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['hours' => $msg]);
        }

        $hourLog = $engagement->hourLogs()->create([
            'specialist_user_id'    => $request->input('specialist_user_id'),
            'log_date'              => $request->input('log_date'),
            'hours'                 => $hours,
            'modality'              => $request->input('modality'),
            'activity_performed'    => $request->input('activity_performed'),
            'location_or_farm'      => $request->input('location_or_farm'),
            'client_contact_name'   => $request->input('client_contact_name'),
            'client_signed'         => $request->boolean('client_signed', false),
            'observations'          => $request->input('observations'),
            'is_authorized_overage' => $isAuthorized,
        ]);

        $engagement->refresh();
        $warningMsg = $engagement->isLowBalance()
            ? " ¡ALERTA: El saldo de la bolsa de horas es bajo ({$engagement->remainingHours()} hrs restantes)!"
            : "";

        $msg = "Registro de visita técnica guardado ({$hours} hrs deducidas).{$warningMsg}";

        return $request->expectsJson()
            ? response()->json([
                'success'        => true,
                'message'        => $msg,
                'is_low_balance' => $engagement->isLowBalance(),
                'remaining_hours'=> $engagement->remainingHours(),
                'hour_log'       => $hourLog,
            ])
            : back()->with('success', $msg);
    }

    /**
     * Store service deliverable with file upload.
     */
    public function storeDeliverable(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $engagement = ServiceEngagement::findOrFail($id);

        $request->validate([
            'deliverable_name' => 'required|string|max:200',
            'description'      => 'required|string',
            'due_date'         => 'required|date',
            'file'             => 'nullable|file|mimes:pdf,docx,xlsx,zip,rar|max:25600',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('deliverables', 'private');
        }

        $deliverable = $engagement->deliverables()->create([
            'deliverable_name' => $request->input('deliverable_name'),
            'description'      => $request->input('description'),
            'due_date'         => $request->input('due_date'),
            'submission_date'  => $filePath ? now()->toDateString() : null,
            'status'           => $filePath ? ServiceDeliverableStatus::SUBMITTED : ServiceDeliverableStatus::PENDING,
            'file_path'        => $filePath,
        ]);

        $msg = "Entregable comprometido {$deliverable->deliverable_name} registrado correctamente.";
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'deliverable' => $deliverable])
            : back()->with('success', $msg);
    }

    /**
     * Client sign-off on deliverable.
     */
    public function signoffDeliverable(Request $request, int $id, int $deliverableId): JsonResponse|RedirectResponse
    {
        $engagement  = ServiceEngagement::findOrFail($id);
        $deliverable = $engagement->deliverables()->findOrFail($deliverableId);

        $request->validate([
            'client_signoff_name'  => 'required|string|max:150',
            'client_signoff_notes' => 'nullable|string',
        ]);

        $deliverable->update([
            'status'                 => ServiceDeliverableStatus::APPROVED,
            'client_signoff_date'    => now(),
            'client_signoff_name'    => $request->input('client_signoff_name'),
            'client_signoff_notes'   => $request->input('client_signoff_notes'),
            'client_signoff_user_id' => Auth::id(),
            'approved_by_user_id'    => Auth::id(),
            'approval_date'          => now(),
        ]);

        $msg = "Conformidad técnica y sign-off del entregable registrada exitosamente.";
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'deliverable' => $deliverable])
            : back()->with('success', $msg);
    }

    /**
     * Close service engagement with final technical report.
     */
    public function closeEngagement(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $engagement = ServiceEngagement::findOrFail($id);

        $request->validate([
            'closure_summary'  => 'required|string',
            'settlement_notes' => 'nullable|string',
        ]);

        $engagement->update([
            'status'               => ServiceEngagementStatus::COMPLETED,
            'actual_delivery_date' => now()->toDateString(),
            'closed_at'            => now(),
            'closed_by_user_id'    => Auth::id(),
            'closure_summary'      => $request->input('closure_summary'),
            'settlement_notes'     => $request->input('settlement_notes') ?? $request->input('closure_summary'),
        ]);

        $msg = "La orden de servicio {$engagement->code} ha sido cerrada formalmente con estatus Concluido.";
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
    }

    /**
     * Download Final Service Closure Report PDF.
     */
    public function downloadFinalReport(int $id)
    {
        $engagement = ServiceEngagement::findOrFail($id);
        $pdf = $this->reportService->generateFinalReportPdf($engagement);
        $filename = 'INFORME_FINAL_' . Str::slug($engagement->code) . '.pdf';
        return $pdf->stream($filename);
    }
}
