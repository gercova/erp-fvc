@extends('admin.layout')

@section('title', 'Presupuestos Institucionales')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Gestión de Presupuestos (PIM / PIA)</h1>
            <p class="text-muted small mb-0">Control de asignaciones, modificaciones presupuestales y ejecución por etapas (Comprometido, Devengado y Girado).</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newBudgetModal">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Presupuesto
            </button>
        </div>
    </div>

    <!-- Filter by Year -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('accounting.budgets.index') }}" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="col-form-label fw-bold small text-muted">Ejercicio Fiscal:</label>
                </div>
                <div class="col-auto">
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Budget Cards / List -->
    <div class="row g-4">
        @forelse($budgets as $budget)
            @php
                $summary = $summaries[$budget->id] ?? [];
                $pct = $summary['overall_percentage'] ?? 0;
                $trafficLight = $summary['traffic_light'] ?? 'GREEN';
                $badgeClass = match($trafficLight) {
                    'RED' => 'danger',
                    'YELLOW' => 'warning',
                    default => 'success',
                };
            @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card shadow-sm h-100 border-0 border-top border-4 border-{{ $badgeClass }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-secondary text-white">{{ $budget->code }}</span>
                                <span class="badge bg-light text-dark ms-1">Año {{ $budget->fiscal_year }}</span>
                            </div>
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

                        <h5 class="card-title text-dark fw-bold mb-3">{{ $budget->name }}</h5>

                        <div class="row g-2 text-center mb-3">
                            <div class="col-6">
                                <div class="p-2 bg-light rounded">
                                    <small class="text-muted d-block">Presupuesto Vigente (PIM)</small>
                                    <span class="fw-bold fs-6">S/ {{ number_format($summary['total_current'] ?? 0, 2) }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded">
                                    <small class="text-muted d-block">Devengado (Ejecutado)</small>
                                    <span class="fw-bold fs-6 text-{{ $badgeClass }}">S/ {{ number_format($summary['total_accrued'] ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Ejecución Presupuestal</span>
                                <span class="fw-bold text-{{ $badgeClass }}">{{ $pct }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-{{ $badgeClass }}" role="progressbar" style="width: {{ min(100, $pct) }}%"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between small text-muted border-top pt-2">
                            <span>Disponible: S/ {{ number_format($summary['total_available'] ?? 0, 2) }}</span>
                            <span>Umbral Alerta: {{ $budget->alert_threshold_percentage }}%</span>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 d-flex justify-content-between pt-0 pb-3">
                        <a href="{{ route('accounting.budgets.show', $budget->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i> Ver Detalle
                        </a>
                        <div class="btn-group">
                            <a href="{{ route('accounting.budgets.kpi_dashboard', $budget->id) }}" class="btn btn-sm btn-outline-secondary" title="Tablero KPI">
                                <i class="bi bi-graph-up"></i> KPI
                            </a>
                            <a href="{{ route('accounting.budgets.export_excel', $budget->id) }}" class="btn btn-sm btn-outline-success" title="Excel">
                                <i class="bi bi-file-earmark-excel"></i>
                            </a>
                            <a href="{{ route('accounting.budgets.export_pdf', $budget->id) }}" class="btn btn-sm btn-outline-danger" title="PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info border-0 shadow-sm text-center py-4">
                    <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                    No se encontraron presupuestos registrados para el año fiscal {{ $year }}.
                </div>
            </div>
        @endforelse
    </div>
</div>

<!-- Modal Nuevo Presupuesto -->
<div class="modal fade" id="newBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('accounting.budgets.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Nuevo Presupuesto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ejercicio Fiscal <span class="text-danger">*</span></label>
                        <input type="number" name="fiscal_year" class="form-control" value="{{ $year }}" required min="2020" max="2050">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Presupuesto <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="Presupuesto Institucional {{ $year }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Código Identificador</label>
                        <input type="text" name="code" class="form-control" placeholder="Ej: PIM-{{ $year }}-01">
                        <small class="text-muted">Si se deja vacío, se generará automáticamente.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Umbral de Alerta (%) <span class="text-danger">*</span></label>
                        <input type="number" name="alert_threshold_percentage" class="form-control" value="90.00" step="0.01" min="1" max="100" required>
                        <small class="text-muted">Porcentaje a partir del cual el sistema emite alerta semafórica roja y notificación.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notas u Observaciones</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Presupuesto</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
