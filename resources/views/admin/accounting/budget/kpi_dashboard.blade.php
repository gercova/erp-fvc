@extends('admin.layout')

@section('title', 'Tablero KPI Presupuestal - ' . $budget->code)

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('accounting.budgets.show', $budget->id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 mb-0 text-gray-800">Tablero KPI de Ejecución Presupuestal</h1>
                <span class="badge bg-secondary">{{ $budget->code }}</span>
            </div>
            <p class="text-muted small mb-0 mt-1">Ejercicio Fiscal: {{ $budget->fiscal_year }} | Matriz Centro de Costos × Mes (Consolidado Automático desde Asientos)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.budgets.export_excel', $budget->id) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar Matriz Excel
            </a>
            <a href="{{ route('accounting.budgets.export_pdf', $budget->id) }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> Exportar PDF
            </a>
        </div>
    </div>

    <!-- Top KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Presupuesto Vigente (PIM)</div>
                    <div class="fs-4 fw-bold text-dark">S/ {{ number_format($execution['total_current'], 2) }}</div>
                    <small class="text-muted">PIA Inicial: S/ {{ number_format($execution['total_allocated'], 2) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Comprometido</div>
                    <div class="fs-4 fw-bold text-warning">S/ {{ number_format($execution['total_committed'], 2) }}</div>
                    <small class="text-muted">Requerimientos / Órdenes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Devengado (Contabilidad)</div>
                    <div class="fs-4 fw-bold text-danger">S/ {{ number_format($execution['total_accrued'], 2) }}</div>
                    <small class="text-muted">{{ $execution['overall_percentage'] }}% del PIM total</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-4 border-success">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Girado / Pagado</div>
                    <div class="fs-4 fw-bold text-success">S/ {{ number_format($execution['total_paid'], 2) }}</div>
                    <small class="text-muted">Disponible: S/ {{ number_format($execution['total_available'], 2) }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Semáforo Status Distribution -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-speedometer2 me-1 text-primary"></i> Nivel de Ejecución General y Semáforo Institucional
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold">Progreso de Ejecución Presupuestal Global</span>
                        <span class="badge bg-{{ match($execution['traffic_light']) {
                            'RED' => 'danger',
                            'YELLOW' => 'warning text-dark',
                            default => 'success',
                        } }} fs-6 px-3">
                            {{ $execution['overall_percentage'] }}% ({{ $execution['traffic_light'] }})
                        </span>
                    </div>
                    <div class="progress mb-4" style="height: 20px;">
                        <div class="progress-bar bg-{{ match($execution['traffic_light']) {
                            'RED' => 'danger',
                            'YELLOW' => 'warning',
                            default => 'success',
                        } }}" role="progressbar" style="width: {{ min(100, $execution['overall_percentage']) }}%">
                            {{ $execution['overall_percentage'] }}%
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="p-2 border rounded bg-success bg-opacity-10 text-success">
                                <div class="fw-bold fs-5">{{ $execution['traffic_light_counts']['GREEN'] }}</div>
                                <small>Partidas Normales (&lt;70%)</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded bg-warning bg-opacity-10 text-warning">
                                <div class="fw-bold fs-5">{{ $execution['traffic_light_counts']['YELLOW'] }}</div>
                                <small>En Alerta (70% - {{ $budget->alert_threshold_percentage }}%)</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded bg-danger bg-opacity-10 text-danger">
                                <div class="fw-bold fs-5">{{ $execution['traffic_light_counts']['RED'] }}</div>
                                <small>Críticas / Excedidas (&ge;{{ $budget->alert_threshold_percentage }}%)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-bell-fill me-1 text-danger"></i> Configuración de Alertas
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Las alertas disparan notificaciones en base de datos a los responsables cuando una partida supera el umbral configurado.</p>
                    <ul class="list-group list-group-flush small mb-3">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Umbral Activo:</span>
                            <strong class="text-danger">{{ $budget->alert_threshold_percentage }}%</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Partidas en Riesgo:</span>
                            <strong>{{ $execution['traffic_light_counts']['RED'] }} partidas</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Disparo de Notificación:</span>
                            <span class="text-success"><i class="bi bi-check-circle-fill"></i> Una sola vez por partida</span>
                        </li>
                    </ul>
                    <form action="{{ route('accounting.budgets.check_alerts', $budget->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                            <i class="bi bi-arrow-repeat me-1"></i> Verificar Alertas y Notificar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Center Execution Matrix (Activity x Month) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">
                <i class="bi bi-grid-3x3-gap me-2 text-primary"></i> Matriz de Ejecución: Centros de Costo × Mes
            </h5>
            <small class="text-muted">Calculado automáticamente desde asientos contables publicados (Sin duplicación de datos)</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle text-center mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="align-middle text-start" style="min-width: 200px;">Actividad Productiva / Centro de Costo</th>
                            <th colspan="12">Meses del Ejercicio Fiscal {{ $budget->fiscal_year }} (Presupuestado / Ejecutado)</th>
                            <th rowspan="2" class="align-middle text-end" style="min-width: 100px;">Total PIM</th>
                            <th rowspan="2" class="align-middle text-end" style="min-width: 100px;">Total Ejec.</th>
                            <th rowspan="2" class="align-middle text-center" style="min-width: 80px;">% Ejec.</th>
                            <th rowspan="2" class="align-middle text-center" style="min-width: 70px;">Semáforo</th>
                        </tr>
                        <tr>
                            @for($m = 1; $m <= 12; $m++)
                                <th>{{ ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Set','Oct','Nov','Dic'][$m-1] }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matrix['activities'] as $actRow)
                            <tr>
                                <td class="text-start fw-semibold">
                                    {{ $actRow['activity_name'] }}
                                    <small class="d-block text-muted">{{ $actRow['cost_center_code'] ?? $actRow['activity_code'] }}</small>
                                </td>
                                @for($m = 1; $m <= 12; $m++)
                                    @php
                                        $cell = $actRow['months'][$m];
                                        $cellLight = $cell['traffic_light'];
                                        $cellBg = match($cellLight) {
                                            'RED' => 'bg-danger bg-opacity-10 text-danger',
                                            'YELLOW' => 'bg-warning bg-opacity-10 text-warning',
                                            default => '',
                                        };
                                    @endphp
                                    <td class="{{ $cellBg }}">
                                        @if($cell['budgeted'] > 0 || $cell['executed'] > 0)
                                            <div class="fw-bold">{{ number_format($cell['executed'], 0) }}</div>
                                            <small class="text-muted d-block">/ {{ number_format($cell['budgeted'], 0) }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endfor
                                <td class="text-end fw-bold">S/ {{ number_format($actRow['annual_budgeted'], 2) }}</td>
                                <td class="text-end fw-bold text-danger">S/ {{ number_format($actRow['annual_executed'], 2) }}</td>
                                <td class="text-center fw-bold">{{ $actRow['annual_percentage'] }}%</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ match($actRow['traffic_light']) {
                                        'RED' => 'danger',
                                        'YELLOW' => 'warning text-dark',
                                        default => 'success',
                                    } }}">
                                        {{ $actRow['traffic_light'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="17" class="text-center py-4 text-muted">
                                    No hay actividades productivas o centros de costo registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="text-start text-uppercase">Total Instituto</td>
                            @for($m = 1; $m <= 12; $m++)
                                @php $mTot = $matrix['monthly_totals'][$m]; @endphp
                                <td>
                                    <div>{{ number_format($mTot['executed'], 0) }}</div>
                                    <small class="text-muted d-block">/ {{ number_format($mTot['budgeted'], 0) }}</small>
                                </td>
                            @endfor
                            <td class="text-end">S/ {{ number_format($matrix['annual_grand_budget'], 2) }}</td>
                            <td class="text-end text-danger">S/ {{ number_format($matrix['annual_grand_executed'], 2) }}</td>
                            <td class="text-center">{{ $matrix['annual_grand_pct'] }}%</td>
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
</div>
@endsection
