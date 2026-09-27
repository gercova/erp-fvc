@extends('admin.layout')
@section('title', 'Comparativo Interanual de Producción')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Agropecuario y Forestal</li>
                    <li class="breadcrumb-item active" aria-current="page">Comparativo Interanual</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-chart-bar me-2 text-primary"></i>Comparativo Interanual de Producción
            </h1>
            <p class="text-muted small mb-0">Evolución mensual de tonelaje cosechado: Año Actual vs Año Anterior (Palma Aceitera, Granos, etc.)</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Imprimir Reporte
            </button>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="{{ route('agrolivestock.reports.year_over_year') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Actividad Productiva</label>
                    <select name="activity_id" class="form-select form-select-sm">
                        <option value="">-- Todas las Actividades --</option>
                        @foreach($activities as $act)
                            <option value="{{ $act->id }}" {{ $selectedActivityId == $act->id ? 'selected' : '' }}>
                                {{ $act->name }} ({{ $act->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Parcela Agrícola</label>
                    <select name="plot_id" class="form-select form-select-sm">
                        <option value="">-- Todas las Parcelas --</option>
                        @foreach($plots as $p)
                            <option value="{{ $p->id }}" {{ $selectedPlotId == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Año de Análisis</label>
                    <input type="number" name="current_year" class="form-control form-control-sm" value="{{ $currentYear }}" min="2020" max="2035">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                        <i class="fas fa-filter me-1"></i> Comparar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Año Actual ({{ $currentYear }})</div>
                <div class="h3 mb-0 fw-bold text-primary mt-1">{{ number_format($totalCurrent, 2) }} TM</div>
                <small class="text-muted">Producción acumulada anual</small>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Año Anterior ({{ $previousYear }})</div>
                <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($totalPrevious, 2) }} TM</div>
                <small class="text-muted">Producción del ciclo previo</small>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Variación Neta</div>
                <div class="h3 mb-0 fw-bold {{ $totalDiff >= 0 ? 'text-success' : 'text-danger' }} mt-1">
                    {{ $totalDiff >= 0 ? '+' : '' }}{{ number_format($totalDiff, 2) }} TM
                </div>
                <small class="text-muted">Diferencia volumétrica</small>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Variación Porcentual</div>
                        <div class="h3 mb-0 fw-bold {{ $totalVariationPct >= 0 ? 'text-success' : 'text-danger' }} mt-1">
                            {{ $totalVariationPct >= 0 ? '+' : '' }}{{ number_format($totalVariationPct, 1) }}%
                        </div>
                        <small class="text-muted">Crecimiento interanual</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: {{ $totalVariationPct >= 0 ? 'rgba(40, 167, 69, 0.1)' : 'rgba(220, 53, 69, 0.1)' }}; color: {{ $totalVariationPct >= 0 ? '#28a745' : '#dc3545' }};">
                        <i class="fas {{ $totalVariationPct >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Comparative Bar Chart Card -->
    <div class="card shadow-sm border-0 bg-white mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-chart-column me-2 text-primary"></i>Gráfico Comparativo Mensual: {{ $currentYear }} vs {{ $previousYear }} (Toneladas Métricas)
            </h6>
        </div>
        <div class="card-body p-3">
            <div style="height: 350px;">
                <canvas id="yoyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Comparison Table -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-table me-2 text-primary"></i>Desglose Mensual Detallado
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.9rem;">
                    <thead class="table-light text-center small text-uppercase text-muted">
                        <tr>
                            <th class="text-start">Mes</th>
                            <th>Año Anterior ({{ $previousYear }})</th>
                            <th>Año Actual ({{ $currentYear }})</th>
                            <th>Diferencia (TM)</th>
                            <th>% Variación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthlyComparison as $m)
                            <tr>
                                <td class="fw-bold text-dark">{{ $m['month_name'] }}</td>
                                <td class="text-end font-monospace">{{ number_format($m['previous_qty'], 2) }} TM</td>
                                <td class="text-end font-monospace fw-bold text-primary">{{ number_format($m['current_qty'], 2) }} TM</td>
                                <td class="text-end font-monospace fw-bold {{ $m['difference'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $m['difference'] >= 0 ? '+' : '' }}{{ number_format($m['difference'], 2) }} TM
                                </td>
                                <td class="text-center">
                                    @if($m['variation_pct'] > 0)
                                        <span class="badge bg-success text-white">+{{ number_format($m['variation_pct'], 1) }}%</span>
                                    @elseif($m['variation_pct'] < 0)
                                        <span class="badge bg-danger text-white">{{ number_format($m['variation_pct'], 1) }}%</span>
                                    @else
                                        <span class="badge bg-secondary text-white">0.0%</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light text-end fw-bold">
                        <tr>
                            <td class="text-start">TOTAL ANUAL CONSOLIDADO:</td>
                            <td>{{ number_format($totalPrevious, 2) }} TM</td>
                            <td class="text-primary">{{ number_format($totalCurrent, 2) }} TM</td>
                            <td class="{{ $totalDiff >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $totalDiff >= 0 ? '+' : '' }}{{ number_format($totalDiff, 2) }} TM
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $totalVariationPct >= 0 ? 'bg-success' : 'bg-danger' }}">
                                    {{ $totalVariationPct >= 0 ? '+' : '' }}{{ number_format($totalVariationPct, 1) }}%
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

@section('scripts')
<script>
$(document).ready(function() {
    const ctx = document.getElementById('yoyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [
                {
                    label: 'Año Anterior ({{ $previousYear }})',
                    data: {!! json_encode($chartPrevious) !!},
                    backgroundColor: 'rgba(108, 117, 125, 0.4)',
                    borderColor: 'rgba(108, 117, 125, 1)',
                    borderWidth: 1.5,
                    borderRadius: 4
                },
                {
                    label: 'Año Actual ({{ $currentYear }})',
                    data: {!! json_encode($chartCurrent) !!},
                    backgroundColor: 'rgba(0, 97, 242, 0.75)',
                    borderColor: 'rgba(0, 97, 242, 1)',
                    borderWidth: 1.5,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Toneladas Métricas (TM)'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' TM';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection
