@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header with Code, Title and Action Buttons -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-dark font-monospace fs-6 px-3 py-2">{{ $engagement->code }}</span>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">{{ $engagement->technologicalService?->name ?? 'Servicio Tecnológico' }}</h1>
                @php
                    $status = $engagement->status instanceof \App\Enums\ServiceEngagementStatus ? $engagement->status->value : (string)$engagement->status;
                    $badgeClass = match($status) {
                        'IN_PROGRESS' => 'bg-primary',
                        'COMPLETED'   => 'bg-success',
                        'CANCELLED'   => 'bg-danger',
                        default       => 'bg-secondary'
                    };
                    $statusLabel = match($status) {
                        'IN_PROGRESS' => 'En Ejecución',
                        'COMPLETED'   => 'Concluido',
                        'CANCELLED'   => 'Cancelado',
                        default       => 'Borrador'
                    };
                @endphp
                <span class="badge {{ $badgeClass }} fs-6 px-3 py-2">{{ $statusLabel }}</span>

                @if($engagement->isUnderAgreement())
                    <span class="badge bg-purple text-white fs-6 px-3 py-2" style="background:#6f42c1;">
                        <i class="fas fa-handshake me-1"></i> Convenio: {{ $engagement->agreement?->code }}
                    </span>
                @else
                    <span class="badge bg-secondary fs-6 px-3 py-2">
                        <i class="fas fa-bolt me-1"></i> Servicio Directo
                    </span>
                @endif
            </div>
            <p class="text-muted small mb-0 mt-1">
                Cliente: <strong class="text-dark">{{ $engagement->client?->nombres }}</strong> (Doc: {{ $engagement->client?->nro_documento }}) &bull;
                Especialista: <strong class="text-dark">{{ $engagement->responsibleUser?->nombres }}</strong> &bull;
                Modalidad: <strong class="text-dark">{{ $engagement->delivery_modality ?? 'IN_PERSON' }}</strong>
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            @if($engagement->status === \App\Enums\ServiceEngagementStatus::IN_PROGRESS)
                <button type="button" class="btn btn-success btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#closeEngagementModal">
                    <i class="fas fa-flag-checkered me-1"></i> Cerrar y Liquidar Servicio
                </button>
            @endif

            <a href="{{ route('services.engagements.final_report', $engagement->id) }}" target="_blank" class="btn btn-outline-danger btn-sm shadow-sm">
                <i class="fas fa-file-pdf me-1"></i> Informe Final PDF
            </a>

            <a href="{{ route('services.engagements.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Catálogo
            </a>
        </div>
    </div>

    <!-- Alert / Banner de Bolsa de Horas Baja -->
    @if($isLowBalance && $engagement->status === \App\Enums\ServiceEngagementStatus::IN_PROGRESS)
        <div class="alert alert-warning d-flex align-items-center gap-3 py-3 mb-4 shadow-sm border-warning" role="alert">
            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
            <div>
                <strong class="d-block text-dark fs-6">¡Atención: Saldo Bajo en la Bolsa de Horas!</strong>
                <span>Restan únicamente <strong>{{ $remainingHours }} horas</strong> de las <strong>{{ (float)$engagement->contracted_hours }} horas contratadas</strong>. Gestione una ampliación de horas o verifique el saldo antes de agendar nuevas jornadas.</span>
            </div>
        </div>
    @endif

    <!-- Main Navigation Tabs -->
    <ul class="nav nav-tabs nav-tabs-bordered mb-4" id="engagementTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                <i class="fas fa-tachometer-alt me-1"></i> Resumen & Horas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="sessions-tab" data-bs-toggle="tab" data-bs-target="#sessions" type="button" role="tab">
                <i class="fas fa-chalkboard-teacher me-1"></i> Capacitación & Asistencia ({{ $engagement->sessions->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="hourlogs-tab" data-bs-toggle="tab" data-bs-target="#hourlogs" type="button" role="tab">
                <i class="fas fa-stopwatch me-1"></i> Asesoría en Campo / Horas ({{ $engagement->hourLogs->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="deliverables-tab" data-bs-toggle="tab" data-bs-target="#deliverables" type="button" role="tab">
                <i class="fas fa-file-invoice me-1"></i> Entregables & Sign-Off ({{ $engagement->deliverables->count() }})
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="engagementTabContent">
        <!-- TAB 1: OVERVIEW & BAG OF HOURS -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-0">
                            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-info-circle me-1"></i> Detalles Operativos del Servicio</h6>
                        </div>
                        <div class="card-body pt-0">
                            <div class="p-3 bg-light rounded mb-4">
                                <h6 class="fw-bold text-dark mb-1">Descripción y Objetivos:</h6>
                                <p class="mb-0 text-muted small">{{ $engagement->description }}</p>
                            </div>

                            <div class="row g-3 small">
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Cliente Contratante:</span>
                                    <strong class="text-dark">{{ $engagement->client?->nombres }}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">RUC / DNI:</span>
                                    <strong class="text-dark">{{ $engagement->client?->nro_documento }}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Marco Legal / Convenio:</span>
                                    @if($engagement->agreement)
                                        <a href="{{ route('agreements.show', $engagement->agreement->id) }}" class="fw-bold text-decoration-none">
                                            {{ $engagement->agreement->code }} - {{ $engagement->agreement->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">Servicio Directo</span>
                                    @endif
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Especialista Asignado:</span>
                                    <strong class="text-dark">{{ $engagement->responsibleUser?->nombres }}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Fecha de Inicio:</span>
                                    <strong class="text-dark">{{ $engagement->start_date?->format('d/m/Y') }}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Fecha Comprometida de Fin:</span>
                                    <strong class="text-dark">{{ $engagement->expected_delivery_date?->format('d/m/Y') }}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Centro de Costo APE:</span>
                                    <strong class="text-dark">{{ $engagement->productiveActivity?->name ?? 'No asignado' }}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Umbral Mínimo para Certificados:</span>
                                    <strong class="text-dark">{{ (float)$engagement->min_attendance_percent }}% de asistencia</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bag of Hours Card -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 mb-4 bg-light">
                        <div class="card-header bg-white py-3 border-0">
                            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-hourglass-half me-1"></i> Control de Bolsa de Horas</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-muted">Horas Consumidas:</span>
                                <span class="fw-bold fs-5 text-dark">{{ (float)$engagement->consumed_hours }} hrs</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-muted">Bolsa Contratada:</span>
                                <span class="fw-bold text-primary">{{ (float)$engagement->contracted_hours }} hrs</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="small text-muted">Saldo Disponible:</span>
                                <span class="fw-bold {{ $isLowBalance ? 'text-danger' : 'text-success' }} fs-5">
                                    {{ $remainingHours }} hrs
                                </span>
                            </div>

                            @php
                                $poolPercent = (float)$engagement->contracted_hours > 0
                                    ? min(100, round(((float)$engagement->consumed_hours / (float)$engagement->contracted_hours) * 100, 1))
                                    : 0;
                                $barClass = $poolPercent >= 90 ? 'bg-danger' : ($poolPercent >= 75 ? 'bg-warning' : 'bg-primary');
                            @endphp
                            <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar {{ $barClass }}" role="progressbar" style="width: {{ $poolPercent }}%;"></div>
                            </div>
                            <div class="text-end small text-muted mb-4">{{ $poolPercent }}% de la bolsa ejecutada</div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted">Total Facturado:</span>
                                <strong class="fs-5 text-dark">{{ $engagement->currency }} {{ number_format($engagement->total_amount, 2) }}</strong>
                            </div>
                            @if($engagement->hourly_rate)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted">Tarifa / Hora:</span>
                                    <span>{{ $engagement->currency }} {{ number_format($engagement->hourly_rate, 2) }}</span>
                                </div>
                            @endif

                            <div class="mt-3">
                                <span class="badge {{ $engagement->allow_pool_overage ? 'bg-info' : 'bg-secondary' }}">
                                    {{ $engagement->allow_pool_overage ? 'Sobregiro Permitido' : 'Sobregiro Bloqueado Estricto' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: TRAINING SESSIONS & ATTENDANCE -->
        <div class="tab-pane fade" id="sessions" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-calendar-alt text-primary me-2"></i>Jornadas Técnicas y Sesiones de Capacitación
                </h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newSessionModal">
                        <i class="fas fa-plus me-1"></i> Programar Sesión
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importAttendeesModal">
                        <i class="fas fa-file-excel me-1"></i> Importar Participantes Excel
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#newAttendeeModal">
                        <i class="fas fa-user-plus me-1"></i> Agregar Participante
                    </button>
                </div>
            </div>

            <!-- Sessions List Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted text-uppercase">
                                <tr>
                                    <th>N°</th>
                                    <th>Tema / Contenido</th>
                                    <th>Fecha & Horario</th>
                                    <th>Duración</th>
                                    <th>Instructor</th>
                                    <th>Lugar / Modalidad</th>
                                    <th>Estado</th>
                                    <th class="text-end">Asistencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($engagement->sessions as $s)
                                <tr>
                                    <td class="fw-bold">{{ $s->session_number }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $s->topic }}</div>
                                        @if($s->observations)
                                            <small class="text-muted">{{ $s->observations }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $s->session_date?->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $s->start_time }} a {{ $s->end_time }}</small>
                                    </td>
                                    <td><strong>{{ (float)$s->duration_hours }} hrs</strong></td>
                                    <td>{{ $s->instructor?->nombres }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $s->location }}</span></td>
                                    <td>
                                        @php
                                            $sBadge = match($s->status?->value ?? $s->status) {
                                                'CONDUCTED' => 'bg-success',
                                                'CANCELLED' => 'bg-danger',
                                                'RESCHEDULED' => 'bg-warning',
                                                default => 'bg-info'
                                            };
                                        @endphp
                                        <span class="badge {{ $sBadge }}">{{ $s->status?->value ?? $s->status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-take-attendance" 
                                            data-session-id="{{ $s->id }}" 
                                            data-session-topic="{{ $s->topic }}">
                                            <i class="fas fa-clipboard-check me-1"></i> Pasar Asistencia
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        No hay sesiones programadas aún. Haga clic en "Programar Sesión" para comenzar.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Participant Roster & Attendance Matrix -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="fas fa-users me-1 text-primary"></i> Padrón de Participantes & Acreditación de Certificados
                    </h6>
                    <small class="text-muted">Umbral para certificado: <strong>{{ (float)$engagement->min_attendance_percent }}% de asistencia</strong></small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted text-uppercase">
                                <tr>
                                    <th>DNI / Doc</th>
                                    <th>Participante</th>
                                    <th>Organización / Empresa</th>
                                    <th>% Asistencia</th>
                                    <th>Estado de Acreditación</th>
                                    <th class="text-end">Certificado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($uniqueAttendees as $att)
                                <tr>
                                    <td class="font-monospace fw-bold">{{ $att->dni_or_document }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $att->full_name }}</div>
                                        <small class="text-muted">{{ $att->email }} {{ $att->phone ? '&bull; ' . $att->phone : '' }}</small>
                                    </td>
                                    <td>{{ $att->organization ?? 'Particular' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <strong>{{ $att->attendance_percent }}%</strong>
                                            <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                                <div class="progress-bar {{ $att->is_eligible ? 'bg-success' : 'bg-warning' }}" 
                                                    style="width: {{ min(100, $att->attendance_percent) }}%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($att->is_eligible)
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Apto para Certificado</span>
                                        @else
                                            <span class="badge bg-secondary">Asistencia Insuficiente (<{{ (float)$engagement->min_attendance_percent }}%)</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($att->certificate_code)
                                            <a href="{{ route('services.certificates.download', [$engagement->id, $att->id]) }}" 
                                                target="_blank" class="btn btn-sm btn-outline-danger" title="Descargar PDF">
                                                <i class="fas fa-file-pdf me-1"></i> {{ $att->certificate_code }}
                                            </a>
                                        @elseif($att->is_eligible)
                                            <form action="{{ route('services.certificates.issue', [$engagement->id, $att->id]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success shadow-sm">
                                                    <i class="fas fa-award me-1"></i> Emitir
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-light border text-muted" disabled>
                                                No califica
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No hay participantes registrados. Use "Importar Participantes Excel" o "Agregar Participante".
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: TECHNICAL ASSISTANCE & VISITS / HOURS LOG -->
        <div class="tab-pane fade" id="hourlogs" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-clipboard-list text-primary me-2"></i>Asesoría Técnica y Registro de Visitas en Campo
                </h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newHourLogModal">
                    <i class="fas fa-plus me-1"></i> Registrar Visita / Horas
                </button>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted text-uppercase">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Horas</th>
                                    <th>Modalidad</th>
                                    <th>Actividad / Asesoría Desarrollada</th>
                                    <th>Lugar / Parcela</th>
                                    <th>Especialista</th>
                                    <th>Conformidad Cliente</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($engagement->hourLogs as $log)
                                <tr>
                                    <td>{{ $log->log_date?->format('d/m/Y') }}</td>
                                    <td><strong class="text-primary fs-6">{{ (float)$log->hours }} hrs</strong></td>
                                    <td><span class="badge bg-light text-dark border">{{ $log->modality }}</span></td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $log->activity_performed }}</div>
                                        @if($log->observations)
                                            <small class="text-muted">{{ $log->observations }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $log->location_or_farm ?? '-' }}</td>
                                    <td>{{ $log->specialist?->nombres }}</td>
                                    <td>
                                        @if($log->client_signed)
                                            <span class="badge bg-success"><i class="fas fa-signature me-1"></i> Firmada</span>
                                        @else
                                            <span class="text-muted">{{ $log->client_contact_name ?? 'Conforme' }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No se han registrado visitas técnicas o deducciones de horas aún.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: DELIVERABLES & SIGNOFF -->
        <div class="tab-pane fade" id="deliverables" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-file-check text-primary me-2"></i>Entregables, Informes Técnicos y Conformidad de Cliente
                </h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newDeliverableModal">
                    <i class="fas fa-upload me-1"></i> Registrar Entregable
                </button>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted text-uppercase">
                                <tr>
                                    <th>Entregable</th>
                                    <th>Fecha Compromiso</th>
                                    <th>Fecha Entrega</th>
                                    <th>Estado</th>
                                    <th>Archivo</th>
                                    <th>Conformidad / Sign-Off</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($engagement->deliverables as $del)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $del->deliverable_name }}</div>
                                        <small class="text-muted">{{ $del->description }}</small>
                                    </td>
                                    <td>{{ $del->due_date?->format('d/m/Y') }}</td>
                                    <td>{{ $del->submission_date?->format('d/m/Y') ?? 'Pendiente' }}</td>
                                    <td>
                                        @php
                                            $dBadge = match($del->status?->value ?? $del->status) {
                                                'APPROVED' => 'bg-success',
                                                'SUBMITTED' => 'bg-info',
                                                'REJECTED' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $dBadge }}">{{ $del->status?->value ?? $del->status }}</span>
                                    </td>
                                    <td>
                                        @if($del->file_path)
                                            <a href="{{ Storage::disk('private')->url($del->file_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-download me-1"></i> Descargar
                                            </a>
                                        @else
                                            <span class="text-muted small">Sin archivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($del->client_signoff_date)
                                            <div class="text-success small fw-bold"><i class="fas fa-check-circle me-1"></i> Sign-Off Conforme</div>
                                            <div class="small text-muted">{{ $del->client_signoff_name }} &bull; {{ $del->client_signoff_date?->format('d/m/Y') }}</div>
                                        @else
                                            <span class="text-muted small">Pendiente de conformidad</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(!$del->client_signoff_date)
                                            <button type="button" class="btn btn-sm btn-outline-success btn-signoff"
                                                data-del-id="{{ $del->id }}"
                                                data-del-name="{{ $del->deliverable_name }}">
                                                <i class="fas fa-file-signature me-1"></i> Sign-Off Cliente
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No hay entregables comprometidos registrados.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: PROGRAMAR SESION -->
<div class="modal fade" id="newSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('services.engagements.sessions.store', $engagement->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-calendar-plus text-primary me-2"></i>Programar Sesión de Capacitación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tema / Contenido Técnico <span class="text-danger">*</span></label>
                        <input type="text" name="topic" class="form-control" required placeholder="Ej. Módulo 1: Manejo Integrado de Plagas...">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fecha de la Sesión <span class="text-danger">*</span></label>
                            <input type="date" name="session_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Instructor / Docente <span class="text-danger">*</span></label>
                            <select name="instructor_user_id" class="form-select" required>
                                @foreach($specialists as $sp)
                                    <option value="{{ $sp->id }}" {{ $sp->id == $engagement->responsible_user_id ? 'selected' : '' }}>
                                        {{ $sp->nombres }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Hora Inicio</label>
                            <input type="time" name="start_time" class="form-control" required value="08:00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Hora Fin</label>
                            <input type="time" name="end_time" class="form-control" required value="12:00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Duración (hrs)</label>
                            <input type="number" step="0.5" min="0" name="duration_hours" class="form-control" value="4.0">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Lugar / Aula / Link <span class="text-danger">*</span></label>
                            <input type="text" name="location" class="form-control" required value="Auditorio Central IESTP FVC">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Estado Inicial</label>
                            <select name="status" class="form-select">
                                <option value="SCHEDULED">Programada</option>
                                <option value="CONDUCTED">Realizada / Ejecutada</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Observaciones / Metas de la Jornada</label>
                        <textarea name="observations" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Guardar Sesión</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: REGISTRAR VISITA / ASISTENCIA TECNICA -->
<div class="modal fade" id="newHourLogModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('services.engagements.hour_logs.store', $engagement->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-stopwatch text-primary me-2"></i>Registrar Visita / Deducción de Horas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="p-2 bg-light border rounded mb-3 small">
                        Saldo actual disponible: <strong class="text-success fs-6">{{ $remainingHours }} hrs</strong>
                        de {{ (float)$engagement->contracted_hours }} hrs contratadas.
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fecha de Visita <span class="text-danger">*</span></label>
                            <input type="date" name="log_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Horas Ejecutadas (hrs) <span class="text-danger">*</span></label>
                            <input type="number" step="0.25" min="0.25" name="hours" class="form-control" required value="2.0">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Especialista <span class="text-danger">*</span></label>
                            <select name="specialist_user_id" class="form-select" required>
                                @foreach($specialists as $sp)
                                    <option value="{{ $sp->id }}" {{ $sp->id == $engagement->responsible_user_id ? 'selected' : '' }}>
                                        {{ $sp->nombres }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Modalidad</label>
                            <select name="modality" class="form-select" required>
                                <option value="FIELD">Campo / Fundo</option>
                                <option value="IN_PERSON">Presencial</option>
                                <option value="VIRTUAL">Virtual</option>
                                <option value="LAB">Laboratorio</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Actividad Realizada / Diagnóstico <span class="text-danger">*</span></label>
                        <input type="text" name="activity_performed" class="form-control" required placeholder="Ej. Análisis fitosanitario y calibración de podas...">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Ubicación / Parcela</label>
                            <input type="text" name="location_or_farm" class="form-control" placeholder="Fundo El Paraíso">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contacto de la Contraparte</label>
                            <input type="text" name="client_contact_name" class="form-control" placeholder="Nombre de quien atendió">
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="client_signed" class="form-check-input" id="client_signed" value="1">
                        <label class="form-check-label small" for="client_signed">El cliente firmó la hoja de visita en campo</label>
                    </div>

                    <div class="mb-3 form-check bg-light p-2 rounded">
                        <input type="checkbox" name="is_authorized_overage" class="form-check-input ms-0 me-2" id="is_authorized_overage" value="1">
                        <label class="form-check-label small fw-bold text-danger" for="is_authorized_overage">
                            Autorización explícita para sobregiro de horas
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Observaciones / Recomendaciones</label>
                        <textarea name="observations" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Guardar y Deducir Horas</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: REGISTRAR ENTREGABLE -->
<div class="modal fade" id="newDeliverableModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('services.engagements.deliverables.store', $engagement->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-upload text-primary me-2"></i>Registrar Entregable Comprometido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre del Entregable <span class="text-danger">*</span></label>
                        <input type="text" name="deliverable_name" class="form-control" required placeholder="Ej. Informe Técnico de Análisis de Suelos...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Descripción de Alcances <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Fecha Comprometida de Entrega <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" class="form-control" required value="{{ date('Y-m-d', strtotime('+15 days')) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Archivo Digital (PDF, DOCX, ZIP)</label>
                        <input type="file" name="file" class="form-control">
                        <div class="form-text small">Si se adjunta el archivo, el entregable pasará a estado Enviado.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Guardar Entregable</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: IMPORTAR PARTICIPANTES EXCEL -->
<div class="modal fade" id="importAttendeesModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('services.engagements.attendees.import', $engagement->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-excel text-success me-2"></i>Importar Padrón de Participantes (Excel / CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small mb-3">
                        <strong>Columnas recomendadas en la primera fila:</strong><br>
                        <code>dni</code> &bull; <code>nombres</code> &bull; <code>email</code> &bull; <code>telefono</code> &bull; <code>organizacion</code>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Archivo Excel (.xlsx, .xls) o CSV <span class="text-danger">*</span></label>
                        <input type="file" name="excel_file" class="form-control" required accept=".xlsx,.xls,.csv">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Asignar a Sesión Específica (Opcional)</label>
                        <select name="service_session_id" class="form-select">
                            <option value="">-- Roster Global del Servicio --</option>
                            @foreach($engagement->sessions as $ses)
                                <option value="{{ $ses->id }}">Sesión {{ $ses->session_number }}: {{ Str::limit($ses->topic, 40) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-upload me-1"></i> Procesar e Importar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: AGREGAR PARTICIPANTE INDIVIDUAL -->
<div class="modal fade" id="newAttendeeModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('services.engagements.attendees.store', $engagement->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-plus text-primary me-2"></i>Registrar Participante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">DNI / Documento <span class="text-danger">*</span></label>
                            <input type="text" name="dni_or_document" class="form-control" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Nombres Completos <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Teléfono / Celular</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Organización / Cooperativa / Empresa</label>
                        <input type="text" name="organization" class="form-control" placeholder="Cooperativa Agraria Cacaotera">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Guardar Participante</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: SIGN-OFF ENTREGABLE -->
<div class="modal fade" id="signoffModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="signoffForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-check-circle text-success me-2"></i>Conformidad Técnica de Cliente (Sign-Off)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3" id="signoffDeliverableText"></p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre del Representante del Cliente que aprueba <span class="text-danger">*</span></label>
                        <input type="text" name="client_signoff_name" class="form-control" required value="{{ $engagement->client?->nombres }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Notas de Conformidad / Aprobación</label>
                        <textarea name="client_signoff_notes" class="form-control" rows="2" placeholder="Entregable revisado y conforme con los términos del servicio..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-signature me-1"></i> Registrar Sign-Off</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: CERRAR SERVICIO -->
<div class="modal fade" id="closeEngagementModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('services.engagements.close', $engagement->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-flag-checkered text-success me-2"></i>Cierre y Liquidación del Servicio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        Al cerrar el servicio, el estado cambiará a <strong>Concluido</strong> y se generará el informe final de cierre y conformidad en PDF.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Resumen de Ejecución y Conclusiones <span class="text-danger">*</span></label>
                        <textarea name="closure_summary" class="form-control" rows="3" required placeholder="Resuma el cumplimiento de metas, horas consumidas y satisfacción del cliente..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Notas de Liquidación Final</label>
                        <textarea name="settlement_notes" class="form-control" rows="2" placeholder="Conformidad técnica y financiera sin saldos pendientes."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check-double me-1"></i> Concluir Servicio</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.btn-signoff').on('click', function() {
        const delId = $(this).data('del-id');
        const delName = $(this).data('del-name');
        $('#signoffDeliverableText').html('Registrando conformidad para: <strong>' + delName + '</strong>');
        $('#signoffForm').attr('action', '/services/engagements/{{ $engagement->id }}/deliverables/' + delId + '/signoff');
        $('#signoffModal').modal('show');
    });
});
</script>
@endsection
