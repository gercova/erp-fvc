@extends('admin.layout')

@section('title', 'Detalle de Presupuesto - ' . $budget->code)

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('accounting.budgets.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 mb-0 text-gray-800">{{ $budget->name }}</h1>
                <span class="badge bg-secondary">{{ $budget->code }}</span>
                <span class="badge bg-{{ match($budget->status) {
                    'ACTIVE' => 'success',
                    'APPROVED', 'APROBADO' => 'info',
                    'EN_REVISION' => 'warning text-dark',
                    'CLOSED' => 'dark',
                    default => 'secondary',
                } }}">
                    {{ $budget->status }}
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Ejercicio Fiscal: {{ $budget->fiscal_year }} | Umbral de Alerta Semafórica: {{ $budget->alert_threshold_percentage }}%</p>
        </div>

        <div class="d-flex gap-2">
            @if($budget->isDraft())
                <form action="{{ route('accounting.budgets.submit_approval', $budget->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning text-dark btn-sm">
                        <i class="bi bi-send-check me-1"></i> Enviar a Aprobación (Tipo 11)
                    </button>
                </form>
            @elseif($budget->isApproved() && !$budget->isActive())
                <form action="{{ route('accounting.budgets.activate', $budget->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle me-1"></i> Activar Presupuesto
                    </button>
                </form>
            @endif

            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLineModal">
                <i class="bi bi-plus-lg me-1"></i> Agregar Partida
            </button>
            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#addModModal">
                <i class="bi bi-arrow-left-right me-1"></i> Modificación Presupuestal
            </button>
            <a href="{{ route('accounting.budgets.kpi_dashboard', $budget->id) }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-graph-up me-1"></i> Tablero KPI
            </a>
            <div class="btn-group">
                <a href="{{ route('accounting.budgets.export_excel', $budget->id) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
                </a>
                <a href="{{ route('accounting.budgets.export_pdf', $budget->id) }}" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                </a>
            </div>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
                <div class="card-body p-3">
                    <small class="text-muted d-block text-uppercase fw-bold">Presupuesto Inicial</small>
                    <span class="fs-5 fw-bold text-dark">S/ {{ number_format($execution['total_allocated'], 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 border-start border-4 border-info h-100">
                <div class="card-body p-3">
                    <small class="text-muted d-block text-uppercase fw-bold">PIM (Vigente)</small>
                    <span class="fs-5 fw-bold text-primary">S/ {{ number_format($execution['total_current'], 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 border-start border-4 border-warning h-100">
                <div class="card-body p-3">
                    <small class="text-muted d-block text-uppercase fw-bold">Comprometido</small>
                    <span class="fs-5 fw-bold text-warning">S/ {{ number_format($execution['total_committed'], 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 border-start border-4 border-danger h-100">
                <div class="card-body p-3">
                    <small class="text-muted d-block text-uppercase fw-bold">Devengado (Asientos)</small>
                    <span class="fs-5 fw-bold text-danger">S/ {{ number_format($execution['total_accrued'], 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <small class="text-muted d-block text-uppercase fw-bold">Girado / Pagado</small>
                    <span class="fs-5 fw-bold text-success">S/ {{ number_format($execution['total_paid'], 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 border-start border-4 border-secondary h-100">
                <div class="card-body p-3">
                    <small class="text-muted d-block text-uppercase fw-bold">Saldo Disponible</small>
                    <span class="fs-5 fw-bold text-dark">S/ {{ number_format($execution['total_available'], 2) }}</span>
                    <small class="d-block text-muted">Ejecución: {{ $execution['overall_percentage'] }}%</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Traffic Light Badges -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('accounting.budgets.show', $budget->id) }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">Filtrar por Mes:</label>
                    <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Todos los Meses --</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                Mes {{ $m }} ({{ DateTime::createFromFormat('!m', $m)->format('F') }})
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-0">Filtrar por Centro de Costos / Actividad:</label>
                    <select name="activity_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Todas las Actividades --</option>
                        @foreach($activities as $act)
                            <option value="{{ $act->id }}" {{ $activityId == $act->id ? 'selected' : '' }}>
                                {{ $act->code }} - {{ $act->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 d-flex justify-content-end align-items-end gap-2">
                    <div class="d-flex align-items-center gap-2 small">
                        <span class="badge bg-success"><i class="bi bi-circle-fill me-1"></i> Normal: {{ $execution['traffic_light_counts']['GREEN'] }}</span>
                        <span class="badge bg-warning text-dark"><i class="bi bi-circle-fill me-1"></i> Alerta: {{ $execution['traffic_light_counts']['YELLOW'] }}</span>
                        <span class="badge bg-danger"><i class="bi bi-circle-fill me-1"></i> Crítico: {{ $execution['traffic_light_counts']['RED'] }}</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabs: Budget Lines & Modifications -->
    <ul class="nav nav-tabs mb-3" id="budgetTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="lines-tab" data-bs-toggle="tab" data-bs-target="#lines-pane" type="button" role="tab">
                <i class="bi bi-list-check me-1"></i> Partidas y Ejecución ({{ count($execution['lines']) }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="mods-tab" data-bs-toggle="tab" data-bs-target="#mods-pane" type="button" role="tab">
                <i class="bi bi-clock-history me-1"></i> Historial de Modificaciones ({{ $budget->modifications->count() }})
            </button>
        </li>
    </ul>

    <div class="tab-content" id="budgetTabsContent">
        <!-- Lines Table -->
        <div class="tab-pane fade show active" id="lines-pane" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Mes</th>
                                <th>Cuenta / Partida</th>
                                <th>Centro de Costo</th>
                                <th>Fuente</th>
                                <th class="text-end">Pres. Inicial</th>
                                <th class="text-end">Modif.</th>
                                <th class="text-end">PIM</th>
                                <th class="text-end text-warning">Comprometido</th>
                                <th class="text-end text-danger">Devengado</th>
                                <th class="text-end text-success">Girado</th>
                                <th class="text-end">Disponible</th>
                                <th class="text-center">% Ejec.</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($execution['lines'] as $line)
                                @php
                                    $light = $line['traffic_light'];
                                    $pillClass = match($light) {
                                        'RED' => 'bg-danger text-white',
                                        'YELLOW' => 'bg-warning text-dark',
                                        default => 'bg-success text-white',
                                    };
                                @endphp
                                <tr>
                                    <td><span class="badge bg-light text-dark">M{{ $line['period_month'] }}</span></td>
                                    <td>
                                        <div class="fw-bold">{{ $line['account_code'] ?? 'Sin cuenta' }}</div>
                                        <small class="text-muted">{{ $line['category_name'] ?? '' }}</small>
                                    </td>
                                    <td>
                                        <small class="fw-semibold">{{ $line['cost_center_code'] ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $line['fund_source_id'] ? 'FTE-'.$line['fund_source_id'] : 'Institucional' }}</small>
                                    </td>
                                    <td class="text-end">S/ {{ number_format($line['allocated_amount'], 2) }}</td>
                                    <td class="text-end text-muted">
                                        {{ $line['modified_amount'] >= 0 ? '+' : '' }}{{ number_format($line['modified_amount'], 2) }}
                                    </td>
                                    <td class="text-end fw-bold">S/ {{ number_format($line['current_amount'], 2) }}</td>
                                    <td class="text-end text-warning">S/ {{ number_format($line['committed_amount'], 2) }}</td>
                                    <td class="text-end text-danger fw-bold">S/ {{ number_format($line['accrued_amount'], 2) }}</td>
                                    <td class="text-end text-success">S/ {{ number_format($line['paid_amount'], 2) }}</td>
                                    <td class="text-end fw-bold">S/ {{ number_format($line['available_amount'], 2) }}</td>
                                    <td class="text-center">
                                        <span class="fw-bold">{{ $line['execution_percentage'] }}%</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill {{ $pillClass }} px-2 py-1">
                                            @if($light === 'RED')
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Crítico
                                            @elseif($light === 'YELLOW')
                                                <i class="bi bi-exclamation-circle me-1"></i> Alerta
                                            @else
                                                <i class="bi bi-check-circle me-1"></i> Normal
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="text-center py-4 text-muted">
                                        No hay partidas presupuestales registradas con los filtros seleccionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4" class="text-uppercase">Total General</td>
                                <td class="text-end">S/ {{ number_format($execution['total_allocated'], 2) }}</td>
                                <td class="text-end">S/ {{ number_format($execution['total_modified'], 2) }}</td>
                                <td class="text-end">S/ {{ number_format($execution['total_current'], 2) }}</td>
                                <td class="text-end text-warning">S/ {{ number_format($execution['total_committed'], 2) }}</td>
                                <td class="text-end text-danger">S/ {{ number_format($execution['total_accrued'], 2) }}</td>
                                <td class="text-end text-success">S/ {{ number_format($execution['total_paid'], 2) }}</td>
                                <td class="text-end">S/ {{ number_format($execution['total_available'], 2) }}</td>
                                <td class="text-center">{{ $execution['overall_percentage'] }}%</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ match($execution['traffic_light']) {
                                        'RED' => 'danger',
                                        'YELLOW' => 'warning text-dark',
                                        default => 'success',
                                    } }}">
                                        {{ $execution['traffic_light'] }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modifications Pane -->
        <div class="tab-pane fade" id="mods-pane" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Código</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Partida Origen</th>
                                <th>Partida Destino</th>
                                <th class="text-end">Monto</th>
                                <th>Justificación</th>
                                <th>Usuario</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($budget->modifications as $mod)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $mod->modification_code }}</span></td>
                                    <td>{{ $mod->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ match($mod->type) {
                                            'ALLOCATION' => 'success',
                                            'CANCELLATION' => 'danger',
                                            'TRANSFER' => 'primary',
                                            default => 'secondary',
                                        } }}">
                                            {{ match($mod->type) {
                                                'ALLOCATION' => 'Ampliación',
                                                'CANCELLATION' => 'Reducción',
                                                'TRANSFER' => 'Transferencia',
                                                default => $mod->type,
                                            } }}
                                        </span>
                                    </td>
                                    <td>{{ $mod->sourceLine ? ($mod->sourceLine->account_code . ' (M' . $mod->sourceLine->period_month . ')') : '-' }}</td>
                                    <td>{{ $mod->destinationLine ? ($mod->destinationLine->account_code . ' (M' . $mod->destinationLine->period_month . ')') : '-' }}</td>
                                    <td class="text-end fw-bold">S/ {{ number_format($mod->amount, 2) }}</td>
                                    <td><small>{{ $mod->justification }}</small></td>
                                    <td><small>{{ $mod->createdByUser?->nombres ?? 'Sistema' }}</small></td>
                                    <td><span class="badge bg-light text-dark">{{ $mod->status }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        No se han registrado modificaciones para este presupuesto.
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

<!-- Modal Agregar Partida -->
<div class="modal fade" id="addLineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('accounting.budgets.lines.store', $budget->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Partida Presupuestal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Mes Presupuestal <span class="text-danger">*</span></label>
                            <select name="period_month" class="form-select" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">Mes {{ $m }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Cuenta Contable (PCGE)</label>
                            <select name="chart_of_account_id" class="form-select">
                                <option value="">-- Seleccionar Cuenta --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Centro de Costo / Actividad</label>
                            <select name="productive_activity_id" class="form-select">
                                <option value="">-- Institucional / General --</option>
                                @foreach($activities as $act)
                                    <option value="{{ $act->id }}">{{ $act->code }} - {{ $act->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fuente de Financiamiento</label>
                            <select name="fund_source_id" class="form-select">
                                <option value="">-- Todas las Fuentes --</option>
                                @foreach($fundSources as $fs)
                                    <option value="{{ $fs->id }}">{{ $fs->code }} - {{ $fs->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Monto Asignado Inicial (S/) <span class="text-danger">*</span></label>
                            <input type="number" name="allocated_amount" class="form-control" step="0.01" min="0" required placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rubro / Categoría (Opcional)</label>
                            <input type="text" name="category_name" class="form-control" placeholder="Ej: Materiales y Útiles">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Notas u Observaciones</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Partida</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modificación Presupuestal -->
<div class="modal fade" id="addModModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('accounting.budgets.modifications.store', $budget->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Modificación Presupuestal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tipo de Modificación <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" id="modTypeSelect" required>
                                <option value="ALLOCATION">Ampliación / Crédito Suplementario</option>
                                <option value="CANCELLATION">Reducción / Anulación</option>
                                <option value="TRANSFER">Transferencia entre Partidas</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Monto de la Modificación (S/) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                        </div>

                        <div class="col-md-6" id="sourceLineGroup">
                            <label class="form-label fw-bold">Partida Origen (a deducir)</label>
                            <select name="source_budget_line_id" class="form-select">
                                <option value="">-- Seleccionar Partida Origen --</option>
                                @foreach($budget->lines as $bl)
                                    <option value="{{ $bl->id }}">
                                        M{{ $bl->period_month }} | {{ $bl->account_code }} | S/ {{ number_format($bl->current_amount, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6" id="destLineGroup">
                            <label class="form-label fw-bold">Partida Destino (a incrementar)</label>
                            <select name="destination_budget_line_id" class="form-select">
                                <option value="">-- Seleccionar Partida Destino --</option>
                                @foreach($budget->lines as $bl)
                                    <option value="{{ $bl->id }}">
                                        M{{ $bl->period_month }} | {{ $bl->account_code }} | S/ {{ number_format($bl->current_amount, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Justificación Técnica / Legal <span class="text-danger">*</span></label>
                            <textarea name="justification" class="form-control" rows="3" required placeholder="Detalle el motivo institucional o resolución de la modificación..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Aplicar Modificación</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
