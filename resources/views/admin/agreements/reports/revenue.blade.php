@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Seguimiento de Ingresos por Convenio</h1>
            <p class="text-muted small mb-0">Consolidado de cronogramas de recaudación: Programado vs. Facturado vs. Cobrado vs. Libro Mayor.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('agreements.reports.revenue.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger btn-sm shadow-sm">
                <i class="fas fa-file-pdf me-1"></i> Exportar PDF
            </a>
            <a href="{{ route('agreements.reports.revenue.excel', request()->query()) }}" class="btn btn-outline-success btn-sm shadow-sm">
                <i class="fas fa-file-excel me-1"></i> Exportar CSV/Excel
            </a>
            <a href="{{ route('agreements.index') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver a Convenios
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('agreements.reports.revenue') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Convenio</label>
                    <select name="agreement_id" class="form-select form-select-sm">
                        <option value="">-- Todos los Convenios --</option>
                        @foreach($agreements as $ag)
                            <option value="{{ $ag->id }}" {{ ($filters['agreement_id'] ?? '') == $ag->id ? 'selected' : '' }}>
                                {{ $ag->code }} - {{ Str::limit($ag->title, 35) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Contraparte / Cliente</label>
                    <select name="client_id" class="form-select form-select-sm">
                        <option value="">-- Todas las Contrapartes --</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ ($filters['client_id'] ?? '') == $c->id ? 'selected' : '' }}>
                                {{ $c->nombres }} ({{ $c->nro_documento }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Centro de Costos / Actividad</label>
                    <select name="productive_activity_id" class="form-select form-select-sm">
                        <option value="">-- Todas las Actividades --</option>
                        @foreach($activities as $act)
                            <option value="{{ $act->id }}" {{ ($filters['productive_activity_id'] ?? '') == $act->id ? 'selected' : '' }}>
                                {{ $act->code }} - {{ $act->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('agreements.reports.revenue') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-primary shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Ingreso Programado</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">S/ {{ number_format($reportData['grand_total_scheduled'], 2) }}</div>
                            <small class="text-muted">Total cuotas pactadas</small>
                        </div>
                        <div class="text-primary opacity-50"><i class="fas fa-calendar-alt fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-info shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Ingreso Facturado</div>
                            <div class="h4 mb-0 fw-bold text-info">S/ {{ number_format($reportData['grand_total_invoiced'], 2) }}</div>
                            <small class="text-muted">Comprobantes emitidos</small>
                        </div>
                        <div class="text-info opacity-50"><i class="fas fa-file-invoice-dollar fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-success shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Ingreso Cobrado</div>
                            <div class="h4 mb-0 fw-bold text-success">S/ {{ number_format($reportData['grand_total_collected'], 2) }}</div>
                            <small class="text-muted">Recaudado efectivamente</small>
                        </div>
                        <div class="text-success opacity-50"><i class="fas fa-check-circle fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-danger shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Saldo por Cobrar</div>
                            <div class="h4 mb-0 fw-bold text-danger">S/ {{ number_format($reportData['grand_total_pending'], 2) }}</div>
                            <small class="text-muted">Pendiente de ingreso</small>
                        </div>
                        <div class="text-danger opacity-50"><i class="fas fa-hourglass-half fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accounting Track B Reconciliation Alert -->
    <div class="alert alert-info d-flex align-items-center py-2 mb-3 shadow-sm" role="alert">
        <i class="fas fa-balance-scale fs-4 me-3 text-primary"></i>
        <div>
            <strong>Integración Contable Track B (Libro Mayor):</strong>
            Los ingresos de convenios son registrados exclusivamente a través del flujo de facturación.
            Total Créditos en Cuenta 70 (Ventas/Servicios) para estos comprobantes:
            <span class="badge bg-primary fs-6 ms-2">S/ {{ number_format($reportData['gl_revenue_credits'], 2) }}</span>
            <span class="text-muted ms-2 small">(Se garantiza no-duplicidad de asientos contables).</span>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-table me-2"></i>Desglose por Convenio y Actividad</h6>
            <span class="badge bg-secondary">{{ count($reportData['rows']) }} convenios registrados</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Código</th>
                            <th>Convenio</th>
                            <th>Contraparte</th>
                            <th>Centro de Costos</th>
                            <th class="text-end">Programado</th>
                            <th class="text-end">Facturado</th>
                            <th class="text-end">Cobrado</th>
                            <th class="text-end">Saldo Pendiente</th>
                            <th class="text-center">Cumplimiento</th>
                            <th class="text-center pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['rows'] as $row)
                            <tr>
                                <td class="ps-3 fw-bold">
                                    <a href="{{ route('agreements.show', $row['agreement_id']) }}" class="text-decoration-none">
                                        {{ $row['agreement_code'] }}
                                    </a>
                                </td>
                                <td>{{ Str::limit($row['agreement_title'], 40) }}</td>
                                <td>
                                    {{ $row['counterparty'] }}<br>
                                    <small class="text-muted">{{ $row['counterparty_doc'] }}</small>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $row['productive_activity'] }}</span></td>
                                <td class="text-end fw-semibold">S/ {{ number_format($row['scheduled'], 2) }}</td>
                                <td class="text-end text-info fw-semibold">S/ {{ number_format($row['invoiced'], 2) }}</td>
                                <td class="text-end text-success fw-bold">S/ {{ number_format($row['collected'], 2) }}</td>
                                <td class="text-end text-danger fw-semibold">S/ {{ number_format($row['pending'], 2) }}</td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                            <div class="progress-bar {{ $row['compliance_pct'] >= 100 ? 'bg-success' : 'bg-primary' }}" 
                                                 role="progressbar" 
                                                 style="width: {{ min(100, $row['compliance_pct']) }}%;"></div>
                                        </div>
                                        <small class="fw-bold">{{ $row['compliance_pct'] }}%</small>
                                    </div>
                                </td>
                                <td class="text-center pe-3">
                                    <a href="{{ route('agreements.show', $row['agreement_id']) }}" class="btn btn-outline-primary btn-sm py-0 px-2" title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle fa-2x mb-2 d-block text-secondary"></i>
                                    No se encontraron convenios con cuotas programadas bajo los filtros indicados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="4" class="text-end ps-3">TOTALES CONSOLIDADOS:</td>
                            <td class="text-end text-primary">S/ {{ number_format($reportData['grand_total_scheduled'], 2) }}</td>
                            <td class="text-end text-info">S/ {{ number_format($reportData['grand_total_invoiced'], 2) }}</td>
                            <td class="text-end text-success">S/ {{ number_format($reportData['grand_total_collected'], 2) }}</td>
                            <td class="text-end text-danger">S/ {{ number_format($reportData['grand_total_pending'], 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
