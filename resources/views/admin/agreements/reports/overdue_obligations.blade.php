@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Compromisos y Obligaciones Vencidas</h1>
            <p class="text-muted small mb-0">Tablero de control para compromisos vencidos de convenios (Nuestra Institución y Contraparte).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('agreements.reports.overdue_obligations.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger btn-sm shadow-sm">
                <i class="fas fa-file-pdf me-1"></i> Exportar PDF
            </a>
            <a href="{{ route('agreements.index') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver a Convenios
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('agreements.reports.overdue_obligations') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold mb-1">Convenio</label>
                    <select name="agreement_id" class="form-select form-select-sm">
                        <option value="">-- Todos los Convenios --</option>
                        @foreach($agreements as $ag)
                            <option value="{{ $ag->id }}" {{ ($filters['agreement_id'] ?? '') == $ag->id ? 'selected' : '' }}>
                                {{ $ag->code }} - {{ Str::limit($ag->title, 40) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold mb-1">Parte Responsable</label>
                    <select name="responsible_party" class="form-select form-select-sm">
                        <option value="">-- Todas las Partes --</option>
                        <option value="OUR_INSTITUTION" {{ ($filters['responsible_party'] ?? '') === 'OUR_INSTITUTION' ? 'selected' : '' }}>Nuestra Institución</option>
                        <option value="COUNTERPARTY" {{ ($filters['responsible_party'] ?? '') === 'COUNTERPARTY' ? 'selected' : '' }}>Contraparte</option>
                        <option value="MUTUAL" {{ ($filters['responsible_party'] ?? '') === 'MUTUAL' ? 'selected' : '' }}>Mancomunado / Mutuo</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('agreements.reports.overdue_obligations') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-danger shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Total Vencidos</div>
                            <div class="h4 mb-0 fw-bold text-danger">{{ $reportData['total_overdue'] }}</div>
                            <small class="text-muted">Compromisos no regularizados</small>
                        </div>
                        <div class="text-danger opacity-50"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-primary shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Nuestra Institución</div>
                            <div class="h4 mb-0 fw-bold text-primary">{{ $reportData['institution_count'] }}</div>
                            <small class="text-muted">Responsabilidad interna</small>
                        </div>
                        <div class="text-primary opacity-50"><i class="fas fa-building fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-info shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Contraparte</div>
                            <div class="h4 mb-0 fw-bold text-info">{{ $reportData['counterparty_count'] }}</div>
                            <small class="text-muted">Responsabilidad externa</small>
                        </div>
                        <div class="text-info opacity-50"><i class="fas fa-user-friends fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-warning shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Mancomunados</div>
                            <div class="h4 mb-0 fw-bold text-warning">{{ $reportData['joint_count'] }}</div>
                            <small class="text-muted">Responsabilidad compartida</small>
                        </div>
                        <div class="text-warning opacity-50"><i class="fas fa-users-cog fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-danger"><i class="fas fa-clock me-2"></i>Compromisos con Vencimiento Superado</h6>
            <span class="badge bg-danger">{{ count($reportData['obligations']) }} registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Convenio</th>
                            <th>Compromiso / Obligación</th>
                            <th>Parte Responsable</th>
                            <th>Responsable</th>
                            <th class="text-center">Fecha Límite</th>
                            <th class="text-center">Días Retraso</th>
                            <th class="text-center">Evidencia</th>
                            <th class="text-center pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['obligations'] as $obl)
                            <tr>
                                <td class="ps-3 fw-bold">
                                    <a href="{{ route('agreements.show', $obl->agreement_id) }}" class="text-decoration-none">
                                        {{ $obl->agreement?->code }}
                                    </a><br>
                                    <small class="text-muted">{{ Str::limit($obl->agreement?->title, 30) }}</small>
                                </td>
                                <td>
                                    <strong>{{ $obl->title }}</strong>
                                    @if($obl->description)
                                        <br><small class="text-muted">{{ Str::limit($obl->description, 60) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($obl->responsible_party === 'OUR_INSTITUTION')
                                        <span class="badge bg-primary">Nuestra Institución</span>
                                    @elseif($obl->responsible_party === 'COUNTERPARTY')
                                        <span class="badge bg-secondary">Contraparte</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Mancomunado</span>
                                    @endif
                                </td>
                                <td>{{ $obl->responsibleUser?->name ?? 'No asignado' }}</td>
                                <td class="text-center text-danger fw-bold">
                                    {{ $obl->due_date ? \Carbon\Carbon::parse($obl->due_date)->format('d/m/Y') : 'Sin fecha' }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger py-1 px-2">
                                        {{ $obl->days_overdue ?? 0 }} días
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($obl->evidence_file_path)
                                        <span class="badge bg-success"><i class="fas fa-file-check me-1"></i> Adjunta</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Pendiente</span>
                                    @endif
                                </td>
                                <td class="text-center pe-3">
                                    <a href="{{ route('agreements.show', $obl->agreement_id) }}" class="btn btn-outline-primary btn-sm py-0 px-2" title="Ir a convenio">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-success">
                                    <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                                    ¡Excelente! No hay obligaciones vencidas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
