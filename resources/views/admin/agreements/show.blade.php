@extends('admin.layout')

@section('content')
    <div class="container-fluid px-4 py-3">
        <!-- Header with Code, Title and Action Buttons -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-dark font-monospace fs-6 px-3 py-2">{{ $agreement->code }}</span>
                    <h1 class="h3 mb-0 text-gray-800 fw-bold">{{ $agreement->name }}</h1>
                    @php
                        $statusMap = [
                            'DRAFT' => ['class' => 'bg-secondary', 'label' => 'Borrador'],
                            'IN_APPROVAL' => ['class' => 'bg-warning text-dark', 'label' => 'En Aprobación'],
                            'ACTIVE' => ['class' => 'bg-success', 'label' => 'Activo / Vigente'],
                            'EXPIRING_SOON' => ['class' => 'bg-warning text-dark', 'label' => 'Próximo a Vencer'],
                            'EXPIRED' => ['class' => 'bg-danger', 'label' => 'Vencido'],
                            'SETTLED' => ['class' => 'bg-info text-dark', 'label' => 'Liquidado'],
                            'TERMINATED' => ['class' => 'bg-dark', 'label' => 'Resuelto / Terminado'],
                            'REJECTED' => ['class' => 'bg-danger', 'label' => 'Rechazado'],
                        ];
                        $stMeta = $statusMap[$agreement->status->value] ?? [
                            'class' => 'bg-light text-dark',
                            'label' => $agreement->status->value,
                        ];
                    @endphp
                    <span class="badge {{ $stMeta['class'] }} fs-6 px-3 py-2">{{ $stMeta['label'] }}</span>
                </div>
                <p class="text-muted small mb-0 mt-1">
                    Tipo: <strong
                        class="text-dark">{{ $agreement->isFramework() ? 'Convenio Marco' : 'Convenio Específico' }}</strong>
                    &bull;
                    Alcance: <strong class="text-dark">{{ $agreement->scope }}</strong> &bull;
                    Creado: {{ $agreement->created_at?->format('d/m/Y H:i') }} por
                    {{ $agreement->creator?->nombres ?? 'Sistema' }}
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                @if ($canSubmitApproval)
                    <button type="button" class="btn btn-warning btn-sm shadow-sm fw-bold text-dark" data-bs-toggle="modal"
                        data-bs-target="#submitApprovalModal">
                        <i class="fas fa-paper-plane me-1"></i> Enviar a Aprobación
                    </button>
                @endif

                @if (
                    $agreement->status === \App\Enums\AgreementStatus::ACTIVE ||
                        $agreement->status === \App\Enums\AgreementStatus::EXPIRING_SOON ||
                        $canManage)
                    <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal"
                        data-bs-target="#newAddendumModal">
                        <i class="fas fa-file-signature me-1"></i> Registrar Adenda
                    </button>
                @endif

                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#newObligationModal">
                    <i class="fas fa-plus me-1"></i> Agregar Cláusula / Compromiso
                </button>

                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#uploadDocModal">
                    <i class="fas fa-upload me-1"></i> Adjuntar Documento
                </button>

                @can('agreements.manage')
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal"
                        data-bs-target="#changeStatusModal">
                        <i class="fas fa-toggle-on me-1"></i> Cambiar Estado
                    </button>
                @endcan

                <a href="{{ route('agreements.compliance', $agreement->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-chart-line me-1"></i> Cumplimiento ({{ $agreement->compliancePercentage() }}%)
                </a>

                @if ($agreement->status === \App\Enums\AgreementStatus::DRAFT || $canManage)
                    <a href="{{ route('agreements.edit', $agreement->id) }}" class="btn btn-outline-dark btn-sm">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                @endif

                <a href="{{ route('agreements.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Catálogo
                </a>
            </div>
        </div>

        <!-- Alert / Banner de Vigencia -->
        @php
            $days = $agreement->daysRemaining();
        @endphp
        @if ($agreement->status === \App\Enums\AgreementStatus::ACTIVE && $days <= 30 && $days >= 0)
            <div class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-4 shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle fa-lg text-warning"></i>
                <div>
                    <strong>Atención:</strong> Este convenio vence en <strong>{{ $days }} días</strong>
                    ({{ $agreement->end_date?->format('d/m/Y') }}). Puede registrar una adenda de prórroga para extender el
                    plazo de vigencia.
                </div>
            </div>
        @elseif(
            $agreement->status === \App\Enums\AgreementStatus::EXPIRED ||
                ($days < 0 && $agreement->status === \App\Enums\AgreementStatus::ACTIVE))
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-4 shadow-sm" role="alert">
                <i class="fas fa-times-circle fa-lg text-danger"></i>
                <div>
                    <strong>Convenio Vencido:</strong> El período de vigencia culminó el
                    {{ $agreement->end_date?->format('d/m/Y') }}. Proceda con la liquidación institucional o registro de
                    adenda extraordinaria.
                </div>
            </div>
        @endif

        <!-- Workflow de Aprobación Stepper Card -->
        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="small fw-bold text-uppercase text-secondary">
                        <i class="fas fa-stamp me-1 text-primary"></i> Cadena Jerárquica de Aprobación Institucional
                        (`DocumentApprovalService`)
                    </span>
                    <span class="badge {{ $stMeta['class'] }}">{{ $stMeta['label'] }}</span>
                </div>

                @if ($agreement->approvals->isNotEmpty())
                    <div class="row g-2">
                        @foreach ($agreement->approvals as $approval)
                            <div class="col-md-3 col-sm-6">
                                <div class="card border-0 shadow-sm h-100 bg-white">
                                    <div class="card-body p-2">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="badge bg-light text-secondary border font-monospace">Paso
                                                {{ $approval->step_order }}</span>
                                            @if ($approval->status === 'APROBADO')
                                                <span class="badge bg-success"><i
                                                        class="fas fa-check-circle me-1"></i>Firmado</span>
                                            @elseif($approval->status === 'PENDIENTE')
                                                <span class="badge bg-warning text-dark"><i
                                                        class="fas fa-clock me-1"></i>Pendiente</span>
                                            @elseif($approval->status === 'OBSERVADO')
                                                <span class="badge bg-secondary"><i
                                                        class="fas fa-exclamation me-1"></i>Observado</span>
                                            @else
                                                <span class="badge bg-danger"><i
                                                        class="fas fa-times me-1"></i>Rechazado</span>
                                            @endif
                                        </div>
                                        <div class="fw-bold small text-truncate" title="{{ $approval->label }}">
                                            {{ $approval->label }}</div>
                                        <div class="text-muted small text-truncate">
                                            {{ $approval->approver_name ?: 'En espera de asignación' }}</div>
                                        @if ($approval->signed_at)
                                            <div class="text-xs text-muted mt-1"><i
                                                    class="fas fa-calendar-check me-1"></i>{{ $approval->signed_at->format('d/m/Y H:i') }}
                                            </div>
                                        @endif
                                        @if ($approval->signature_token)
                                            <div class="text-xs font-monospace text-primary text-truncate">Token:
                                                {{ $approval->signature_token }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="d-flex align-items-center justify-content-between p-2 rounded bg-white border">
                        <span class="small text-muted">
                            <i class="fas fa-info-circle text-info me-1"></i> El expediente se encuentra en borrador. Al
                            hacer clic en <strong>"Enviar a Aprobación"</strong> se generará la cadena de 4 firmas:
                            Solicitante &rarr; Jefe de Área &rarr; Administración &rarr; Dirección General.
                        </span>
                        @if ($canSubmitApproval)
                            <button type="button" class="btn btn-warning btn-sm text-dark fw-bold" data-bs-toggle="modal"
                                data-bs-target="#submitApprovalModal">
                                <i class="fas fa-paper-plane me-1"></i> Enviar Ahora
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Main Navigation Tabs -->
        <ul class="nav nav-pills mb-4" id="agreementTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-overview" data-bs-toggle="pill" data-bs-target="#content-overview"
                    type="button" role="tab">
                    <i class="fas fa-info-circle me-1"></i> Ficha del Convenio
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-obligations" data-bs-toggle="pill" data-bs-target="#content-obligations"
                    type="button" role="tab">
                    <i class="fas fa-list-check me-1"></i> Cláusulas y Compromisos
                    <span class="badge bg-secondary ms-1">{{ $agreement->obligations->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-installments" data-bs-toggle="pill"
                    data-bs-target="#content-installments" type="button" role="tab">
                    <i class="fas fa-hand-holding-usd me-1"></i> Cronograma de Recaudación
                    <span class="badge bg-secondary ms-1">{{ $agreement->installments->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-addenda" data-bs-toggle="pill" data-bs-target="#content-addenda"
                    type="button" role="tab">
                    <i class="fas fa-history me-1"></i> Historial de Adendas
                    <span class="badge bg-secondary ms-1">{{ $agreement->addenda->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-docs" data-bs-toggle="pill" data-bs-target="#content-docs"
                    type="button" role="tab">
                    <i class="fas fa-folder-open me-1"></i> Expediente Digital
                    <span class="badge bg-secondary ms-1">{{ $agreement->documents->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-audit" data-bs-toggle="pill" data-bs-target="#content-audit"
                    type="button" role="tab">
                    <i class="fas fa-user-shield me-1"></i> Registro de Auditoría
                    <span class="badge bg-secondary ms-1">{{ $agreement->auditLogs->count() }}</span>
                </button>
            </li>
        </ul>

        <!-- Tab Contents -->
        <div class="tab-content" id="agreementTabsContent">
            <!-- TAB 1: FICHA GENERAL DEL CONVENIO -->
            <div class="tab-pane fade show active" id="content-overview" role="tabpanel">
                <div class="row g-4">
                    <!-- Columna Izquierda: Entidad, Vigencia y Montos -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 mb-4 h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-building me-1"></i> Contraparte y
                                    Dependencia</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <th style="width: 40%;" class="text-muted">Contraparte / Entidad:</th>
                                        <td>
                                            <div class="fw-bold text-dark">
                                                {{ $agreement->client?->nombres ?? 'No asignada' }}</div>
                                            <span class="badge bg-light text-secondary border">RUC/Tax ID:
                                                {{ $agreement->client?->nro_documento ?? '-' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Dirección / Sede:</th>
                                        <td>{{ $agreement->client?->direccion ?? 'No especificada' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Departamento Responsable:</th>
                                        <td>
                                            <span
                                                class="fw-bold text-dark">{{ $agreement->area?->name ?? 'Institucional' }}</span>
                                            @if ($agreement->area?->head)
                                                <div class="small text-muted">Jefe: {{ $agreement->area->head->nombres }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Responsable Técnico:</th>
                                        <td>
                                            <div class="fw-bold text-dark">
                                                {{ $agreement->coordinator?->nombres ?? 'Sin asignar' }}</div>
                                            <small class="text-muted">{{ $agreement->coordinator?->email ?? '-' }}</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Actividad Productiva (APE):</th>
                                        <td>
                                            @if ($agreement->productiveActivity)
                                                <span
                                                    class="badge bg-info text-dark font-monospace">{{ $agreement->productiveActivity->code }}</span>
                                                {{ $agreement->productiveActivity->name }}
                                            @else
                                                <span class="text-muted">No vinculada a centro de costo productivo</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($agreement->parentAgreement)
                                        <tr>
                                            <th class="text-muted">Convenio Marco Padre:</th>
                                            <td>
                                                <a href="{{ route('agreements.show', $agreement->parentAgreement->id) }}"
                                                    class="fw-bold text-primary">
                                                    {{ $agreement->parentAgreement->code }} -
                                                    {{ $agreement->parentAgreement->name }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Vigencia, Plazos y Presupuesto -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 mb-4 h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-calendar-alt me-1"></i> Vigencia y
                                    Contraprestación</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <th style="width: 40%;" class="text-muted">Inicio de Vigencia:</th>
                                        <td class="fw-bold">
                                            {{ $agreement->start_date ? $agreement->start_date->format('d/m/Y') : '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Fin de Vigencia:</th>
                                        <td class="fw-bold">
                                            {{ $agreement->end_date ? $agreement->end_date->format('d/m/Y') : '-' }}
                                            <span class="badge bg-light text-dark border ms-2">{{ $days }} días
                                                restantes</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Fecha de Suscripción:</th>
                                        <td>{{ $agreement->signing_date ? $agreement->signing_date->format('d/m/Y') : 'Pendiente' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Moneda y Monto:</th>
                                        <td>
                                            <span class="fs-5 fw-bold text-success">
                                                {{ $agreement->currency }}
                                                {{ number_format($agreement->total_amount, 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Obligación Económica:</th>
                                        <td>
                                            @if ($agreement->has_economic_obligation)
                                                <span class="badge bg-success">Sí &bull; Genera desembolsos /
                                                    facturación</span>
                                            @else
                                                <span class="badge bg-secondary">No &bull; Cooperación técnica mutua</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Informes de Seguimiento:</th>
                                        <td>
                                            @if ($agreement->requires_mutual_reports)
                                                <span class="badge bg-info text-dark">Obligatorios periódicos</span>
                                            @else
                                                <span class="badge bg-light text-secondary border">A requerimiento</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Objeto y Cláusulas Card Completa -->
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-align-left me-1"></i> Objeto
                                    Sustantivo y Cláusulas Clave</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="small fw-bold text-uppercase text-muted">Objeto / Propósito:</label>
                                    <div class="p-3 bg-light rounded text-dark">{{ $agreement->objective }}</div>
                                </div>

                                @if ($agreement->key_clauses)
                                    <div class="mb-3">
                                        <label class="small fw-bold text-uppercase text-muted">Cláusulas Clave y
                                            Responsabilidades Mutuas:</label>
                                        <div class="p-3 bg-light rounded text-dark" style="white-space: pre-line;">
                                            {{ $agreement->key_clauses }}</div>
                                    </div>
                                @endif

                                @if ($agreement->termination_conditions)
                                    <div class="mb-0">
                                        <label class="small fw-bold text-uppercase text-muted">Condiciones de Resolución /
                                            Terminación:</label>
                                        <div class="p-3 bg-light rounded text-dark" style="white-space: pre-line;">
                                            {{ $agreement->termination_conditions }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: COMPROMISOS Y CLÁUSULAS -->
            <div class="tab-pane fade" id="content-obligations" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-tasks me-1"></i> Compromisos y Cláusulas
                                Operativas</h6>
                            <small class="text-muted">Monitoreo de responsabilidades de nuestra institución y la
                                contraparte.</small>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#newObligationModal">
                            <i class="fas fa-plus me-1"></i> Nuevo Compromiso
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase text-secondary">
                                    <tr>
                                        <th class="ps-3">Cláusula</th>
                                        <th>Responsable</th>
                                        <th>Compromiso / Descripción</th>
                                        <th>Fecha Límite</th>
                                        <th>Estado</th>
                                        <th>Verificación</th>
                                        <th class="text-end pe-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    @forelse($agreement->obligations as $ob)
                                        <tr>
                                            <td class="ps-3 fw-bold font-monospace">{{ $ob->clause_reference ?: 'S/N' }}
                                            </td>
                                            <td>
                                                @if ($ob->responsible_party === 'OUR_INSTITUTION')
                                                    <span class="badge bg-primary">IESTP "FVC"</span>
                                                @elseif($ob->responsible_party === 'COUNTERPARTY')
                                                    <span
                                                        class="badge bg-secondary">{{ $agreement->client?->nombres ?? 'Contraparte' }}</span>
                                                @else
                                                    <span class="badge bg-info text-dark">Mutuo</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $ob->title }}</div>
                                                @if ($ob->description)
                                                    <small
                                                        class="text-muted d-block">{{ Str::limit($ob->description, 120) }}</small>
                                                @endif
                                                @if ($ob->evidence_notes)
                                                    <small class="text-success"><i
                                                            class="fas fa-check-circle me-1"></i>{{ $ob->evidence_notes }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $ob->due_date ? $ob->due_date->format('d/m/Y') : 'Permanente' }}</td>
                                            <td>
                                                @php
                                                    $obBadge = match ($ob->status->value) {
                                                        'COMPLETED' => 'bg-success',
                                                        'IN_PROGRESS' => 'bg-info text-dark',
                                                        'PENDING' => 'bg-warning text-dark',
                                                        'BREACHED' => 'bg-danger',
                                                        default => 'bg-secondary',
                                                    };
                                                @endphp
                                                <span class="badge {{ $obBadge }}">{{ $ob->status->label() }}</span>
                                            </td>
                                            <td>
                                                @if ($ob->evidence_file_path)
                                                    <span class="badge bg-success d-inline-block mb-1"><i
                                                            class="fas fa-file-check me-1"></i> Evidencia adjunta</span>
                                                @endif
                                                @if ($ob->responsibleUser)
                                                    <div class="small fw-semibold text-dark"><i
                                                            class="fas fa-user me-1 text-muted"></i>{{ $ob->responsibleUser->name }}
                                                    </div>
                                                @endif
                                                @if ($ob->verifier)
                                                    <small class="text-muted d-block">Verif: {{ $ob->verifier->nombres }}
                                                        ({{ $ob->completed_at?->format('d/m/Y') }})</small>
                                                @elseif(!$ob->evidence_file_path && !$ob->responsibleUser)
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-3">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary btn-evidence-obligation"
                                                    data-id="{{ $ob->id }}" data-title="{{ $ob->title }}"
                                                    title="Adjuntar Evidencia">
                                                    <i class="fas fa-paperclip me-1"></i> Evidencia
                                                </button>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-success btn-update-obligation"
                                                    data-id="{{ $ob->id }}" data-title="{{ $ob->title }}"
                                                    data-status="{{ $ob->status->value }}"
                                                    data-notes="{{ $ob->evidence_notes }}" title="Actualizar Estado">
                                                    <i class="fas fa-check me-1"></i> Estado
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="fas fa-info-circle me-1"></i> No se han registrado compromisos
                                                individuales todavía.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: CRONOGRAMA DE RECAUDACIÓN (CUOTAS) -->
            <div class="tab-pane fade" id="content-installments" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-hand-holding-usd me-1"></i> Cronograma
                                de Recaudación y Cuotas</h6>
                            <small class="text-muted">Gestión de cuotas, emisión de comprobantes (Factura/Boleta/Nota de
                                Venta) y cobranza vinculada.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#newInstallmentModal">
                                <i class="fas fa-plus me-1"></i> Programar Cuota
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Mini KPI Row -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-3 col-6">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted text-uppercase fw-bold d-block">Programado</small>
                                    <span class="fs-6 fw-bold text-dark">S/
                                        {{ number_format($agreement->scheduledRevenue(), 2) }}</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted text-uppercase fw-bold d-block">Facturado</small>
                                    <span class="fs-6 fw-bold text-info">S/
                                        {{ number_format($agreement->invoicedRevenue(), 2) }}</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted text-uppercase fw-bold d-block">Cobrado</small>
                                    <span class="fs-6 fw-bold text-success">S/
                                        {{ number_format($agreement->collectedRevenue(), 2) }}</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted text-uppercase fw-bold d-block">Saldo Pendiente</small>
                                    <span class="fs-6 fw-bold text-danger">S/
                                        {{ number_format($agreement->pendingRevenue(), 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase text-secondary">
                                    <tr>
                                        <th class="ps-3">N°</th>
                                        <th>F. Vence</th>
                                        <th>Hito / Condición</th>
                                        <th class="text-end">Monto</th>
                                        <th class="text-center">Estado</th>
                                        <th>Comprobante</th>
                                        <th>F. Cobro / Ref</th>
                                        <th class="text-end pe-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    @forelse($agreement->installments as $inst)
                                        <tr>
                                            <td class="ps-3 fw-bold font-monospace">Cuota {{ $inst->installment_number }}
                                            </td>
                                            <td>{{ $inst->due_date ? \Carbon\Carbon::parse($inst->due_date)->format('d/m/Y') : '-' }}
                                            </td>
                                            <td>
                                                <strong>{{ $inst->milestone_condition ?: ($inst->description ?: 'Cuota estándar') }}</strong>
                                                @if ($inst->technologicalService)
                                                    <div class="small text-muted">{{ $inst->technologicalService->name }}
                                                    </div>
                                                @endif
                                                @if ($inst->adjustment_notes)
                                                    <div class="small text-warning"><i
                                                            class="fas fa-edit me-1"></i>Ajuste:
                                                        {{ $inst->adjustment_notes }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold">{{ $inst->currency ?? 'PEN' }}
                                                {{ number_format($inst->amount, 2) }}</td>
                                            <td class="text-center">
                                                @php
                                                    $stVal =
                                                        $inst->status instanceof \App\Enums\InstallmentStatus
                                                            ? $inst->status->value
                                                            : $inst->status;
                                                    $badgeCls = match ($stVal) {
                                                        'COLLECTED', 'PAID' => 'bg-success',
                                                        'INVOICED' => 'bg-info text-dark',
                                                        'OVERDUE' => 'bg-danger',
                                                        default => 'bg-warning text-dark',
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeCls }}">{{ $stVal }}</span>
                                            </td>
                                            <td>
                                                @if ($inst->billing)
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="fas fa-receipt me-1 text-primary"></i>Factura
                                                        #{{ $inst->billing->serie }}-{{ $inst->billing->correlativo }}
                                                    </span>
                                                @elseif($inst->saleNote)
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="fas fa-file-invoice me-1 text-secondary"></i>Nota Venta
                                                        #{{ $inst->saleNote->serie }}-{{ $inst->saleNote->correlativo }}
                                                    </span>
                                                @else
                                                    <span class="text-muted small">Sin comprobante</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($inst->paid_at)
                                                    <span
                                                        class="text-success fw-bold">{{ \Carbon\Carbon::parse($inst->paid_at)->format('d/m/Y') }}</span>
                                                    @if ($inst->payment_reference)
                                                        <small
                                                            class="text-muted d-block">{{ $inst->payment_reference }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="btn-group btn-group-sm">
                                                    @if ($inst->canBeInvoiced())
                                                        <button type="button"
                                                            class="btn btn-outline-primary btn-generate-voucher"
                                                            data-id="{{ $inst->id }}"
                                                            data-number="{{ $inst->installment_number }}"
                                                            data-amount="{{ $inst->amount }}"
                                                            data-currency="{{ $inst->currency ?? 'PEN' }}"
                                                            title="Emitir Comprobante (POS / Factura)">
                                                            <i class="fas fa-file-invoice-dollar me-1"></i> Facturar
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-outline-secondary btn-link-voucher"
                                                            data-id="{{ $inst->id }}"
                                                            data-number="{{ $inst->installment_number }}"
                                                            title="Vincular Comprobante Existente">
                                                            <i class="fas fa-link"></i>
                                                        </button>
                                                    @endif

                                                    @if ($inst->isInvoiced() && !$inst->isCollected() && !$inst->isPaid())
                                                        <button type="button"
                                                            class="btn btn-outline-success btn-record-payment"
                                                            data-id="{{ $inst->id }}"
                                                            data-number="{{ $inst->installment_number }}"
                                                            title="Registrar Cobro">
                                                            <i class="fas fa-check-double me-1"></i> Cobrar
                                                        </button>
                                                    @endif

                                                    @if (!$inst->isInvoiced() && !$inst->isPaid())
                                                        <button type="button"
                                                            class="btn btn-outline-warning btn-adjust-installment"
                                                            data-id="{{ $inst->id }}"
                                                            data-number="{{ $inst->installment_number }}"
                                                            data-amount="{{ $inst->amount }}"
                                                            data-due="{{ $inst->due_date ? \Carbon\Carbon::parse($inst->due_date)->format('Y-m-d') : '' }}"
                                                            data-desc="{{ $inst->description }}"
                                                            data-milestone="{{ $inst->milestone_condition }}"
                                                            title="Ajuste Financiero Autorizado">
                                                            <i class="fas fa-sliders-h"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="fas fa-calendar-times me-1"></i> No se han programado cuotas para
                                                este convenio todavía.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: HISTORIAL DE ADENDAS -->
            <div class="tab-pane fade" id="content-addenda" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-history me-1"></i> Historial y Recálculo
                                de Adendas</h6>
                            <small class="text-muted">Registro auditable de modificaciones de plazo y monto al convenio
                                institucional.</small>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#newAddendumModal">
                            <i class="fas fa-plus me-1"></i> Nueva Adenda
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase text-secondary">
                                    <tr>
                                        <th class="ps-3">Código Adenda</th>
                                        <th>Tipo de Adenda</th>
                                        <th>Resolución / Sustento</th>
                                        <th>Justificación</th>
                                        <th>Vigencia Anterior &rarr; Nueva</th>
                                        <th>Variación Presupuestal</th>
                                        <th>Fecha Suscripción</th>
                                        <th>Registrado Por</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    @forelse($agreement->addenda as $ad)
                                        <tr>
                                            <td class="ps-3 fw-bold font-monospace text-primary">{{ $ad->code }}</td>
                                            <td><span class="badge bg-secondary">{{ $ad->type->label() }}</span></td>
                                            <td>{{ $ad->resolution_number ?: 'Sin Resolución' }}</td>
                                            <td>{{ Str::limit($ad->justification, 100) }}</td>
                                            <td>
                                                <span
                                                    class="text-muted">{{ $ad->previous_end_date ? $ad->previous_end_date->format('d/m/Y') : '-' }}</span>
                                                &rarr;
                                                <span
                                                    class="fw-bold text-success">{{ $ad->new_end_date ? $ad->new_end_date->format('d/m/Y') : '-' }}</span>
                                            </td>
                                            <td>
                                                @if ((float) $ad->amount_delta != 0.0)
                                                    <span
                                                        class="fw-bold {{ (float) $ad->amount_delta > 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ (float) $ad->amount_delta > 0 ? '+' : '' }}{{ number_format($ad->amount_delta, 2) }}
                                                    </span>
                                                    <small class="text-muted d-block">Nuevo Total:
                                                        {{ number_format($ad->new_total_amount, 2) }}</small>
                                                @else
                                                    <span class="text-muted">Sin variación</span>
                                                @endif
                                            </td>
                                            <td>{{ $ad->signature_date ? $ad->signature_date->format('d/m/Y') : '-' }}
                                            </td>
                                            <td>{{ $ad->creator?->nombres ?? 'Sistema' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="fas fa-info-circle me-1"></i> No se han registrado adendas
                                                modificatorias para este convenio.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: EXPEDIENTE DIGITAL Y DOCUMENTOS -->
            <div class="tab-pane fade" id="content-docs" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-folder-open me-1"></i> Expediente
                                Digital del Convenio</h6>
                            <small class="text-muted">Documentos oficiales, versiones firmadas y anexos técnicos
                                almacenados de forma segura.</small>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#uploadDocModal">
                            <i class="fas fa-upload me-1"></i> Adjuntar Documento
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase text-secondary">
                                    <tr>
                                        <th class="ps-3">Tipo de Documento</th>
                                        <th>Título / Descripción</th>
                                        <th>Nombre del Archivo</th>
                                        <th>Tamaño</th>
                                        <th>Versión</th>
                                        <th>Subido Por</th>
                                        <th class="text-end pe-3">Descargar</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    @forelse($agreement->documents as $doc)
                                        <tr>
                                            <td class="ps-3"><span
                                                    class="badge bg-light text-dark border">{{ $doc->document_type->label() }}</span>
                                            </td>
                                            <td class="fw-semibold">{{ $doc->title }}</td>
                                            <td class="font-monospace text-muted">{{ $doc->file_name }}</td>
                                            <td>{{ number_format(($doc->file_size ?? 0) / 1024, 1) }} KB</td>
                                            <td><span class="badge bg-secondary">v{{ $doc->version }}</span></td>
                                            <td>{{ $doc->uploader?->nombres ?? 'Sistema' }}
                                                ({{ $doc->created_at?->format('d/m/Y') }})</td>
                                            <td class="text-end pe-3">
                                                <a href="{{ route('agreements.documents.download', $doc->id) }}"
                                                    class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-download me-1"></i> Descargar
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="fas fa-info-circle me-1"></i> No se han adjuntado archivos al
                                                expediente digital.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 5: REGISTRO DE AUDITORÍA -->
            <div class="tab-pane fade" id="content-audit" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-user-shield me-1"></i> Registro Cronológico
                            de Auditoría</h6>
                        <small class="text-muted">Trazabilidad inmutable de todas las acciones, transiciones de estado y
                            modificaciones.</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase text-secondary">
                                    <tr>
                                        <th class="ps-3">Fecha y Hora</th>
                                        <th>Acción</th>
                                        <th>Usuario Responsable</th>
                                        <th>Transición de Estado</th>
                                        <th>Motivo / Justificación</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    @forelse($agreement->auditLogs as $log)
                                        <tr>
                                            <td class="ps-3 text-nowrap font-monospace">
                                                {{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                            <td><span class="badge bg-dark font-monospace">{{ $log->action }}</span>
                                            </td>
                                            <td class="fw-semibold">{{ $log->user?->nombres ?? 'Sistema' }}</td>
                                            <td>
                                                @if ($log->previous_status && $log->new_status && $log->previous_status !== $log->new_status)
                                                    <span class="badge bg-secondary">{{ $log->previous_status }}</span>
                                                    &rarr;
                                                    <span class="badge bg-primary">{{ $log->new_status }}</span>
                                                @else
                                                    <span
                                                        class="badge bg-light text-secondary border">{{ $log->new_status ?: 'N/A' }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $log->reason ?: '-' }}</td>
                                            <td class="text-muted font-monospace text-xs">{{ $log->ip_address ?: '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="fas fa-info-circle me-1"></i> No hay registros de auditoría aún.
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

    <!-- MODAL 1: SUBMIT APPROVAL -->
    <div class="modal fade" id="submitApprovalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('agreements.submit_approval', $agreement->id) }}" method="POST">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold"><i class="fas fa-paper-plane me-1"></i> Enviar a Aprobación
                            Jerárquica</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">¿Está seguro de someter el convenio <strong>{{ $agreement->code }}</strong> a
                            la cadena jerárquica de aprobación institucional?</p>
                        <div class="alert alert-info py-2 small mb-0">
                            <i class="fas fa-info-circle me-1"></i> Se generará un expediente en
                            <strong>DocumentApprovalService</strong> con 4 pasos de visación digital:
                            <ol class="mb-0 ps-3 mt-1">
                                <li>Solicitante / Coordinador Técnico</li>
                                <li>Jefe de Área Responsable</li>
                                <li>Jefatura de Administración</li>
                                <li>Dirección General (Firma Final que activa el convenio)</li>
                            </ol>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning fw-bold text-dark">Confirmar Envío</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: NUEVA ADENDA -->
    <div class="modal fade" id="newAddendumModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('agreements.addenda.store', $agreement->id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="fas fa-file-signature me-1"></i> Registrar Nueva Adenda
                            Modificatoria</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tipo de Adenda <span
                                        class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="TERM_EXTENSION">Prórroga de Plazo (Vigencia)</option>
                                    <option value="AMOUNT_EXTENSION">Ampliación Presupuestal (Monto)</option>
                                    <option value="MIXED">Mixta (Plazo y Monto)</option>
                                    <option value="SCOPE_MODIFICATION">Modificación de Alcance / Cláusulas</option>
                                    <option value="OTHER">Otras Modificaciones</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Resolución Directoral / N° Documento</label>
                                <input type="text" name="resolution_number" class="form-control"
                                    placeholder="Ej. R.D. N° 045-2026-IESTP-FVC">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Justificación y Sustento de la Adenda <span
                                        class="text-danger">*</span></label>
                                <textarea name="justification" rows="3" class="form-control"
                                    placeholder="Exponga los motivos técnicos, normativos o presupuestales..." required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nueva Fecha de Término (Prórroga)</label>
                                <input type="date" name="new_end_date" class="form-control"
                                    value="{{ $agreement->end_date?->format('Y-m-d') }}">
                                <small class="text-muted">Fecha actual:
                                    {{ $agreement->end_date?->format('d/m/Y') }}</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Variación Presupuestal (+/-)</label>
                                <input type="number" step="0.01" name="amount_delta" class="form-control text-end"
                                    value="0.00">
                                <small class="text-muted">Monto actual: S/
                                    {{ number_format($agreement->total_amount, 2) }}</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Fecha de Suscripción</label>
                                <input type="date" name="signature_date" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Documento Escaneado de la Adenda (PDF/Word)</label>
                                <input type="file" name="file" class="form-control"
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold">Aplicar y Recalcular Convenio</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: NUEVO COMPROMISO -->
    <div class="modal fade" id="newObligationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('agreements.obligations.store', $agreement->id) }}" method="POST">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="fas fa-list-check me-1"></i> Registrar Compromiso /
                            Cláusula</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Cláusula Ref.</label>
                                <input type="text" name="clause_reference" class="form-control font-monospace"
                                    placeholder="Ej. Cuarta">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Parte Responsable <span
                                        class="text-danger">*</span></label>
                                <select name="responsible_party" class="form-select" required>
                                    <option value="OUR_INSTITUTION">Nuestra Institución (IESTP "FVC")</option>
                                    <option value="COUNTERPARTY">Contraparte
                                        ({{ $agreement->client?->nombres ?? 'Entidad' }})</option>
                                    <option value="MUTUAL">Responsabilidad Mutua / Conjunta</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Título del Compromiso <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control"
                                    placeholder="Ej. Facilitar campos agrícolas experimentales" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Descripción Detallada y Entregables</label>
                                <textarea name="description" rows="3" class="form-control"
                                    placeholder="Detalle técnico del compromiso acordado..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Fecha Límite de Cumplimiento (Opcional)</label>
                                <input type="date" name="due_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold">Registrar Cláusula</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: ACTUALIZAR ESTADO DE COMPROMISO -->
    <div class="modal fade" id="updateObligationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="updateObligationForm" method="POST">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold"><i class="fas fa-check-circle me-1"></i> Estado del Compromiso
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Compromiso:</label>
                            <div id="modalObTitle" class="fw-bold text-dark p-2 bg-light rounded"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Estado Operativo <span
                                    class="text-danger">*</span></label>
                            <select name="status" id="modalObStatus" class="form-select" required>
                                <option value="PENDING">Pendiente</option>
                                <option value="IN_PROGRESS">En Proceso / Ejecución</option>
                                <option value="COMPLETED">Cumplido / Completado</option>
                                <option value="WAIVED">Condonado / Dispensado</option>
                                <option value="BREACHED">Incumplido</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Evidencias y Notas de Verificación</label>
                            <textarea name="evidence_notes" id="modalObNotes" rows="3" class="form-control"
                                placeholder="Informe N°, acta de entrega, observaciones..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold">Actualizar Compromiso</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: SUBIR DOCUMENTO -->
    <div class="modal fade" id="uploadDocModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('agreements.documents.store', $agreement->id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="fas fa-upload me-1"></i> Adjuntar Documento al
                            Expediente</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tipo de Documento <span
                                    class="text-danger">*</span></label>
                            <select name="document_type" class="form-select" required>
                                <option value="CONVENIO_FIRMADO">Convenio Firmado</option>
                                <option value="ADENDA">Adenda</option>
                                <option value="INFORME_TECNICO">Informe Técnico</option>
                                <option value="RESOLUCION">Resolución Directoral</option>
                                <option value="ACTA_REUNION">Acta de Reunión / Coordinación</option>
                                <option value="INFORME_LIQUIDACION">Informe de Liquidación</option>
                                <option value="OTHER">Otro Documento</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Título / Nombre Identificador <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control"
                                placeholder="Ej. Informe de Avance Semestral 2026-I" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Archivo (PDF, Word, Imagen) <span
                                    class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            <small class="text-muted">Máx 20MB. Almacenamiento seguro privado.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold">Subir Archivo</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 6: CAMBIAR ESTADO MANUALMENTE -->
    @can('agreements.manage')
        <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('agreements.change_status', $agreement->id) }}" method="POST">
                    @csrf
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title fw-bold"><i class="fas fa-toggle-on me-1"></i> Cambio de Estado
                                Institucional</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nuevo Estado Operativo <span
                                        class="text-danger">*</span></label>
                                <select name="new_status" class="form-select" required>
                                    <option value="ACTIVE">Activo / Vigente</option>
                                    <option value="SETTLED">Liquidado (Cierre formal)</option>
                                    <option value="TERMINATED">Resuelto / Terminado</option>
                                    <option value="EXPIRED">Vencido</option>
                                    <option value="DRAFT">Retornar a Borrador</option>
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-bold">Motivo Sustentatorio (Auditoría Obligatoria) <span
                                        class="text-danger">*</span></label>
                                <textarea name="reason" rows="3" class="form-control"
                                    placeholder="Justifique el motivo del cambio de estado..." required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger fw-bold">Registrar Cambio de Estado</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const obModal = new bootstrap.Modal(document.getElementById('updateObligationModal'));
            const obForm = document.getElementById('updateObligationForm');
            const obTitle = document.getElementById('modalObTitle');
            const obStatus = document.getElementById('modalObStatus');
            const obNotes = document.getElementById('modalObNotes');

            document.querySelectorAll('.btn-update-obligation').forEach(button => {
                button.addEventListener('click', function() {
                    const obId = this.dataset.id;
                    obTitle.textContent = this.dataset.title;
                    obStatus.value = this.dataset.status;
                    obNotes.value = this.dataset.notes || '';
                    obForm.action =
                        "{{ url('agreements') }}/{{ $agreement->id }}/obligations/" + obId;
                    obModal.show();
                });
            });
        });
    </script>
@endpush
