@extends('admin.layout')

@section('title', 'Flujo de Caja - Método Directo')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800"><i class="fas fa-chart-line text-primary me-2"></i>Flujo de Caja: Método Directo</h3>
            <p class="text-muted small mb-0">Comparativa Proyectado vs. Real clasificado por Operación, Inversión y Financiamiento.</p>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('treasury.cash_flow.export', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'fund_source_id' => $fundSourceId, 'activity_id' => $activityId]) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel me-1"></i> Exportar a Excel
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('treasury.cash_flow.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small font-weight-bold mb-1">Fecha Desde</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small font-weight-bold mb-1">Fecha Hasta</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small font-weight-bold mb-1">Fuente de Financiamiento</label>
                    <select name="fund_source_id" class="form-select form-select-sm">
                        <option value="">-- Todas las Fuentes --</option>
                        @foreach($fundSources as $fund)
                            <option value="{{ $fund->id }}" {{ $fundSourceId == $fund->id ? 'selected' : '' }}>
                                {{ $fund->code }} - {{ $fund->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small font-weight-bold mb-1">Actividad Productiva</label>
                    <select name="activity_id" class="form-select form-select-sm">
                        <option value="">-- Todas las Actividades --</option>
                        @foreach($activities as $act)
                            <option value="{{ $act->id }}" {{ $activityId == $act->id ? 'selected' : '' }}>
                                {{ $act->code }} - {{ $act->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reconciliation & Balances Banner -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-left-secondary h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Saldo Inicial Caja y Bancos</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($report['initial_balance'], 2) }}</div>
                    <small class="text-muted">Al inicio del período</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Flujo Neto del Período</div>
                    <div class="h5 mb-0 font-weight-bold {{ $report['summary']['actual_net_flow'] >= 0 ? 'text-success' : 'text-danger' }}">
                        S/ {{ number_format($report['summary']['actual_net_flow'], 2) }}
                    </div>
                    <small class="text-muted">Ingresos S/ {{ number_format($report['summary']['actual_inflows'], 2) }} - Egresos S/ {{ number_format($report['summary']['actual_outflows'], 2) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Saldo Final Caja y Bancos</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($report['final_balance'], 2) }}</div>
                    <small class="text-muted">Al cierre del período</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm {{ $report['reconciliation']['is_reconciled'] ? 'border-left-success bg-success-subtle' : 'border-left-danger bg-danger-subtle' }} h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold {{ $report['reconciliation']['is_reconciled'] ? 'text-success' : 'text-danger' }} text-uppercase mb-1">
                        Conciliación de Variación
                    </div>
                    <div class="h5 mb-0 font-weight-bold {{ $report['reconciliation']['is_reconciled'] ? 'text-success' : 'text-danger' }}">
                        {{ $report['reconciliation']['is_reconciled'] ? '✓ CONCILIADO EXACTO' : '⚠ DISCREPANCIA' }}
                    </div>
                    <small class="{{ $report['reconciliation']['is_reconciled'] ? 'text-success' : 'text-danger' }}">
                        Dif: S/ {{ number_format($report['reconciliation']['difference'], 2) }} (Flujo Neto = Δ Saldos)
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Row -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-2">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-bar me-1"></i> Comparativa Real vs. Proyectado por Categoría</h6>
        </div>
        <div class="card-body">
            <canvas id="cashFlowChart" height="75"></canvas>
        </div>
    </div>

    <!-- Direct Method Breakdown Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-2">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-table me-1"></i> Detalle del Flujo de Caja (Método Directo)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 50%;">CATEGORÍA Y CONCEPTO</th>
                            <th class="text-end" style="width: 16%;">REAL (S/)</th>
                            <th class="text-end" style="width: 16%;">PROYECTADO (S/)</th>
                            <th class="text-end" style="width: 18%;">VARIACIÓN (S/)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['categories'] as $catKey => $cat)
                            <tr class="table-secondary fw-bold">
                                <td colspan="4"><i class="fas fa-folder-open me-2 text-primary"></i>{{ $cat['name'] }}</td>
                            </tr>
                            
                            <!-- Inflows -->
                            <tr class="table-light fw-bold">
                                <td colspan="4" class="ps-4 text-success"><i class="fas fa-arrow-down me-1"></i> INGRESOS DE EFECTIVO</td>
                            </tr>
                            @forelse($cat['actual']['inflows'] as $inflow)
                                @php
                                    $projAmount = 0.00;
                                    foreach($cat['projected']['inflows'] as $p) {
                                        if($p['concept'] === $inflow['concept']) {
                                            $projAmount = $p['amount'];
                                            break;
                                        }
                                    }
                                    $var = $inflow['amount'] - $projAmount;
                                @endphp
                                <tr>
                                    <td class="ps-5">{{ $inflow['concept'] }}</td>
                                    <td class="text-end font-weight-bold text-success">S/ {{ number_format($inflow['amount'], 2) }}</td>
                                    <td class="text-end text-muted">S/ {{ number_format($projAmount, 2) }}</td>
                                    <td class="text-end {{ $var >= 0 ? 'text-success' : 'text-danger' }}">
                                        S/ {{ number_format($var, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="ps-5 text-muted small" colspan="4">Sin ingresos registrados</td>
                                </tr>
                            @endforelse

                            <!-- Outflows -->
                            <tr class="table-light fw-bold">
                                <td colspan="4" class="ps-4 text-danger"><i class="fas fa-arrow-up me-1"></i> EGRESOS DE EFECTIVO</td>
                            </tr>
                            @forelse($cat['actual']['outflows'] as $outflow)
                                @php
                                    $projAmount = 0.00;
                                    foreach($cat['projected']['outflows'] as $p) {
                                        if($p['concept'] === $outflow['concept']) {
                                            $projAmount = $p['amount'];
                                            break;
                                        }
                                    }
                                    $var = $outflow['amount'] - $projAmount;
                                @endphp
                                <tr>
                                    <td class="ps-5">{{ $outflow['concept'] }}</td>
                                    <td class="text-end font-weight-bold text-danger">S/ {{ number_format($outflow['amount'], 2) }}</td>
                                    <td class="text-end text-muted">S/ {{ number_format($projAmount, 2) }}</td>
                                    <td class="text-end {{ $var <= 0 ? 'text-success' : 'text-danger' }}">
                                        S/ {{ number_format($var, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="ps-5 text-muted small" colspan="4">Sin egresos registrados</td>
                                </tr>
                            @endforelse

                            <!-- Subtotal Category -->
                            <tr class="table-warning fw-bold">
                                <td class="ps-3">FLUJO NETO {{ $cat['name'] }}</td>
                                <td class="text-end {{ $cat['actual']['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    S/ {{ number_format($cat['actual']['net'], 2) }}
                                </td>
                                <td class="text-end text-muted">S/ {{ number_format($cat['projected']['net'], 2) }}</td>
                                <td class="text-end {{ $cat['variance']['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    S/ {{ number_format($cat['variance']['net'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-dark fw-bold">
                        <tr>
                            <th>FLUJO NETO TOTAL DEL PERÍODO</th>
                            <th class="text-end">S/ {{ number_format($report['summary']['actual_net_flow'], 2) }}</th>
                            <th class="text-end">S/ {{ number_format($report['summary']['projected_net_flow'], 2) }}</th>
                            <th class="text-end">S/ {{ number_format($report['summary']['variance_net'], 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('cashFlowChart');
    if (!ctx) return;

    const chartData = @json($report['chart_data']);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.categories,
            datasets: [
                {
                    label: 'Flujo Real (S/)',
                    data: chartData.actual,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Flujo Proyectado (S/)',
                    data: chartData.projected,
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'S/ ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
