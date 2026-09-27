@extends('admin.layout')
@section('title', 'Desglose de Estructura de Costos por Categoría')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Agropecuario y Forestal</li>
                    <li class="breadcrumb-item active" aria-current="page">Desglose de Costos</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-pie-chart me-2 text-danger"></i>Estructura de Costos por Categoría
            </h1>
            <p class="text-muted small mb-0">Análisis porcentual del gasto operativo (Mano de Obra 69%, Alimento 14%, Insumos y Sanidad) según metodología del informe económico institucional</p>
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
            <form action="{{ route('agrolivestock.reports.cost_breakdown') }}" method="GET" class="row g-2 align-items-end">
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
                    <label class="form-label small fw-bold text-muted mb-1">Fecha Desde</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Fecha Hasta</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                        <i class="fas fa-filter me-1"></i> Analizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Costo Total Capturado</div>
                <div class="h3 mb-0 fw-bold text-dark mt-1">S/ {{ number_format($totalCapturedCost, 2) }}</div>
                <small class="text-muted">Personal + Insumos + Alimentos</small>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Personal y Jornales</div>
                <div class="h3 mb-0 fw-bold text-primary mt-1">
                    {{ $breakdown[0]['percentage'] }}%
                </div>
                <small class="text-muted">S/ {{ number_format($breakdown[0]['amount'], 2) }} (Meta institucional ~69%)</small>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Alimento Concentrado (Feed)</div>
                <div class="h3 mb-0 fw-bold text-pink mt-1" style="color: #e83e8c;">
                    {{ $breakdown[1]['percentage'] }}%
                </div>
                <small class="text-muted">S/ {{ number_format($breakdown[1]['amount'], 2) }} (Meta institucional ~14%)</small>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Fertilizantes e Insumos</div>
                <div class="h3 mb-0 fw-bold text-success mt-1">
                    {{ round($breakdown[2]['percentage'] + $breakdown[3]['percentage'] + $breakdown[4]['percentage'], 1) }}%
                </div>
                <small class="text-muted">Agroquímicos, sanidad y semillas</small>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <!-- Horizontal Bar Chart -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>Distribución Comparativa del Gasto por Categoría
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div style="height: 300px;">
                        <canvas id="costBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Donut Chart -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>Estructura Porcentual (%)
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div style="height: 300px;">
                        <canvas id="costDoughnutChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Breakdown Table -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-table me-2 text-primary"></i>Matriz de Costos por Categoría Presupuestal
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th>Categoría Presupuestal</th>
                            <th class="text-end">Monto Total Ejecutado</th>
                            <th class="text-center" style="width: 250px;">Participación Porcentual (%)</th>
                            <th class="text-center">Estado vs Benchmark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($breakdown as $item)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <span class="badge {{ $item['badge'] }} me-2">&nbsp;</span>
                                        {{ $item['category'] }}
                                    </div>
                                </td>
                                <td class="text-end font-monospace fw-bold fs-6">S/ {{ number_format($item['amount'], 2) }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $item['percentage'] }}%; background-color: {{ $item['color'] }};" aria-valuenow="{{ $item['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span class="fw-bold small font-monospace">{{ number_format($item['percentage'], 1) }}%</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if(str_contains($item['category'], 'Personal'))
                                        <span class="badge bg-light text-dark border">Informe Ref: 69%</span>
                                    @elseif(str_contains($item['category'], 'Alimento'))
                                        <span class="badge bg-light text-dark border">Informe Ref: 14%</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Insumos Varios</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold text-end">
                        <tr>
                            <td class="text-start">TOTAL CONSOLIDADO:</td>
                            <td class="font-monospace text-danger fs-6">S/ {{ number_format($totalCapturedCost, 2) }}</td>
                            <td class="text-center font-monospace">100.0%</td>
                            <td>-</td>
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
    const categories = {!! json_encode(array_column($breakdown, 'category')) !!};
    const amounts = {!! json_encode(array_column($breakdown, 'amount')) !!};
    const percentages = {!! json_encode(array_column($breakdown, 'percentage')) !!};
    const colors = {!! json_encode(array_column($breakdown, 'color')) !!};

    // 1. Horizontal Bar Chart
    const ctxBar = document.getElementById('costBarChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [{
                label: 'Monto en Soles (S/)',
                data: amounts,
                backgroundColor: colors,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Monto Ejecutado (S/)'
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'S/ ' + context.parsed.x.toLocaleString('es-PE', {minimumFractionDigits: 2});
                        }
                    }
                }
            }
        }
    });

    // 2. Doughnut Chart
    const ctxPie = document.getElementById('costDoughnutChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: categories,
            datasets: [{
                data: percentages,
                backgroundColor: colors,
                borderWidth: 2,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection
