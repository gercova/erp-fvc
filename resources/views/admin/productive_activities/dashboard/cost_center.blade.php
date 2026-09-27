@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Tablero de Centros de Costos: Actividades Productivas (APE)</h1>
            <p class="text-muted small mb-0">Consolidado general de ingresos, egresos y saldos por actividad y mes &bull; Formato Institucional FVC.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#importHistoricalModal">
                <i class="fas fa-file-import me-1"></i> Importar Histórico Excel
            </button>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-success btn-sm dropdown-toggle shadow-sm" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-file-excel me-1"></i> Exportar a Excel
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('productive_activities.cost_center.export_excel', ['year' => $year, 'type' => $selectedType, 'fund_source_id' => $selectedFundSourceId]) }}">
                            <i class="fas fa-table me-2 text-success"></i> Formato Resumen (Matriz Actividad × Mes)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('productive_activities.cost_center.export_detailed', ['year' => $year, 'type' => $selectedType, 'fund_source_id' => $selectedFundSourceId]) }}">
                            <i class="fas fa-copy me-2 text-primary"></i> Informe Completo Multi-Hoja (Hoja por Actividad)
                        </a>
                    </li>
                </ul>
            </div>
            <a href="{{ route('productive_activities.transactions.index') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="fas fa-plus me-1"></i> Registrar Movimiento
            </a>
        </div>
    </div>

    <!-- Segmented Controls & Filters Bar -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('productive_activities.cost_center.index') }}" method="GET" id="filterForm">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <!-- Segmented Control de Tipo de Actividad -->
                    <div class="btn-group btn-group-sm flex-wrap" role="group">
                        @foreach ($types as $key => $label)
                            <a href="{{ route('productive_activities.cost_center.index', array_merge(request()->query(), ['type' => $key])) }}"
                               class="btn {{ $selectedType === $key ? 'btn-primary active text-white' : 'btn-outline-secondary' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>

                    <!-- Selectores de Año y Fuente de Fondos -->
                    <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                        <input type="hidden" name="type" value="{{ $selectedType }}">

                        <div class="input-group input-group-sm" style="width: 140px;">
                            <span class="input-group-text bg-light"><i class="fas fa-calendar-alt text-muted"></i></span>
                            <select class="form-select form-select-sm" name="year" onchange="this.form.submit()">
                                <option value="2024" {{ $year == 2024 ? 'selected' : '' }}>2024</option>
                                <option value="2025" {{ $year == 2025 ? 'selected' : '' }}>2025</option>
                                <option value="2026" {{ $year == 2026 ? 'selected' : '' }}>2026</option>
                                <option value="2027" {{ $year == 2027 ? 'selected' : '' }}>2027</option>
                            </select>
                        </div>

                        <div class="input-group input-group-sm" style="width: 230px;">
                            <span class="input-group-text bg-light"><i class="fas fa-university text-muted"></i></span>
                            <select class="form-select form-select-sm" name="fund_source_id" onchange="this.form.submit()">
                                <option value="">-- Todas las Fuentes --</option>
                                @foreach($fundSources as $fund)
                                    <option value="{{ $fund->id }}" {{ $selectedFundSourceId == $fund->id ? 'selected' : '' }}>
                                        {{ $fund->name }} ({{ $fund->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-start-lg border-start-success shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Ingresos Consolidados ({{ $year }})</div>
                            <div class="h3 mb-0 fw-bold text-success font-monospace">S/ {{ number_format($matrixData['grand_totals']['income'], 2) }}</div>
                        </div>
                        <div class="text-success opacity-50"><i class="fas fa-arrow-down fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-start-lg border-start-danger shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Egresos Consolidados ({{ $year }})</div>
                            <div class="h3 mb-0 fw-bold text-danger font-monospace">S/ {{ number_format($matrixData['grand_totals']['expense'], 2) }}</div>
                        </div>
                        <div class="text-danger opacity-50"><i class="fas fa-arrow-up fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-start-lg border-start-primary shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Superávit / Saldo Neto Consolidado</div>
                            <div class="h3 mb-0 fw-bold font-monospace {{ $matrixData['grand_totals']['balance'] >= 0 ? 'text-primary' : 'text-danger' }}">
                                S/ {{ number_format($matrixData['grand_totals']['balance'], 2) }}
                            </div>
                        </div>
                        <div class="text-primary opacity-50"><i class="fas fa-balance-scale fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row: Monthly Comparative & Activity Comparative (Requisito Germán Cotrina) -->
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="fas fa-chart-column me-2"></i> Evolución Mensual: Ingresos vs. Egresos y Saldo (Año {{ $year }})
                    </h6>
                    <span class="badge bg-light text-muted border font-monospace small">Consolidado Mensual</span>
                </div>
                <div class="card-body">
                    <div style="height: 330px;">
                        <canvas id="monthlyComparativeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="fas fa-chart-bar me-2"></i> Ingresos vs. Egresos por Actividad
                    </h6>
                    <span class="badge bg-light text-muted border font-monospace small">Distribución APE</span>
                </div>
                <div class="card-body">
                    <div style="height: 330px;">
                        <canvas id="activityComparativeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cross-Tabulation Matrix: Activity × Month -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-primary">
                <i class="fas fa-table me-2"></i> Matriz Económica Mensualizada: Actividades × Meses (Año {{ $year }})
            </h6>
            <span class="badge bg-light text-muted border font-monospace">Layout: Resumen Institucional</span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 680px;">
                <table class="table table-bordered table-hover table-sm align-middle mb-0 text-nowrap" style="font-size: 0.80rem;">
                    <thead class="table-dark sticky-top" style="z-index: 5;">
                        <tr class="text-center align-middle">
                            <th rowspan="2" style="min-width: 170px;" class="text-start ps-3">Actividad Productiva</th>
                            <th rowspan="2" style="width: 75px;">C. Costo</th>
                            <th rowspan="2" style="width: 85px;">Tipo</th>
                            @php
                                $monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic'];
                            @endphp
                            @foreach($monthNames as $mIdx => $mName)
                                <th colspan="3" class="border-start">{{ $mName }}</th>
                            @endforeach
                            <th colspan="3" class="border-start bg-secondary text-white">TOTAL EJERCICIO {{ $year }}</th>
                        </tr>
                        <tr class="text-center small" style="font-size: 0.70rem;">
                            @for($m = 1; $m <= 12; $m++)
                                <th class="text-success border-start">Ing</th>
                                <th class="text-danger">Egr</th>
                                <th class="text-info fw-bold">Sal</th>
                            @endfor
                            <th class="text-success border-start bg-dark">Total Ing</th>
                            <th class="text-danger bg-dark">Total Egr</th>
                            <th class="text-warning fw-bold bg-dark">Saldo Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matrixData['activities'] as $act)
                            <tr>
                                <td class="fw-bold ps-3 text-start">
                                    <a href="{{ route('productive_activities.show', $act['id']) }}" class="text-dark text-decoration-none hover-primary">
                                        {{ $act['name'] }}
                                    </a>
                                    <div class="text-muted small fw-normal">{{ $act['code'] }} &bull; {{ $act['head_name'] }}</div>
                                </td>
                                <td class="text-center text-muted font-monospace small">{{ $act['cost_center'] }}</td>
                                <td class="text-center"><span class="badge bg-light text-dark border small">{{ $act['type'] }}</span></td>

                                @for($m = 1; $m <= 12; $m++)
                                    @php
                                        $mData = $act['months'][$m] ?? ['income' => 0, 'expense' => 0, 'balance' => 0];
                                    @endphp
                                    <td class="text-end border-start font-monospace {{ $mData['income'] > 0 ? 'text-success' : 'text-muted' }}">
                                        {{ $mData['income'] > 0 ? number_format($mData['income'], 2) : '-' }}
                                    </td>
                                    <td class="text-end font-monospace {{ $mData['expense'] > 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ $mData['expense'] > 0 ? number_format($mData['expense'], 2) : '-' }}
                                    </td>
                                    <td class="text-end font-monospace fw-bold {{ $mData['balance'] < 0 ? 'text-danger' : ($mData['balance'] > 0 ? 'text-primary' : 'text-muted') }}">
                                        {{ $mData['balance'] != 0 ? number_format($mData['balance'], 2) : '-' }}
                                    </td>
                                @endfor

                                <!-- Totales Anuales de la Actividad -->
                                <td class="text-end fw-bold text-success border-start bg-light font-monospace">
                                    {{ number_format($act['total_income'], 2) }}
                                </td>
                                <td class="text-end fw-bold text-danger bg-light font-monospace">
                                    {{ number_format($act['total_expense'], 2) }}
                                </td>
                                <td class="text-end fw-bold font-monospace bg-light {{ $act['final_balance'] < 0 ? 'text-danger' : 'text-primary' }}">
                                    {{ number_format($act['final_balance'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="42" class="text-center py-4 text-muted">
                                    No se encontraron actividades productivas registradas para el filtro seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-secondary sticky-bottom fw-bold" style="z-index: 4;">
                        <tr class="align-middle">
                            <td colspan="3" class="text-end pe-3 text-uppercase">
                                TOTALES CONSOLIDADOS INSTITUCIONALES (APE):
                            </td>
                            @for($m = 1; $m <= 12; $m++)
                                @php
                                    $mTot = $matrixData['month_totals'][$m] ?? ['income' => 0, 'expense' => 0, 'balance' => 0];
                                @endphp
                                <td class="text-end text-success border-start font-monospace">
                                    {{ $mTot['income'] > 0 ? number_format($mTot['income'], 2) : '-' }}
                                </td>
                                <td class="text-end text-danger font-monospace">
                                    {{ $mTot['expense'] > 0 ? number_format($mTot['expense'], 2) : '-' }}
                                </td>
                                <td class="text-end font-monospace {{ $mTot['balance'] < 0 ? 'text-danger' : 'text-primary' }}">
                                    {{ $mTot['balance'] != 0 ? number_format($mTot['balance'], 2) : '-' }}
                                </td>
                            @endfor
                            <td class="text-end text-success border-start bg-primary-soft font-monospace">
                                {{ number_format($matrixData['grand_totals']['income'], 2) }}
                            </td>
                            <td class="text-end text-danger bg-primary-soft font-monospace">
                                {{ number_format($matrixData['grand_totals']['expense'], 2) }}
                            </td>
                            <td class="text-end text-primary fw-bolder bg-primary-soft font-monospace">
                                {{ number_format($matrixData['grand_totals']['balance'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Importación de Archivo Histórico Excel -->
<div class="modal fade" id="importHistoricalModal" tabindex="-1" aria-labelledby="importHistoricalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="importHistoricalModalLabel">
                    <i class="fas fa-file-import me-2"></i> Importador de Formatos Históricos Excel (APE)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importExcelForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-3">
                        <i class="fas fa-info-circle fa-2x me-3 text-info"></i>
                        <div class="small">
                            <strong>Motor Inteligente de Migración APE:</strong> Procesa archivos con múltiples hojas por actividad (ejm: Palma, Peces, Cerdos, Bovinos, Vivero, Servicios). Detecta automáticamente bloques de <em>Ingresos/Egresos</em>, columnas de meses y fuentes de financiamiento.
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Archivo Excel Histórico (.xlsx / .xls) <span class="text-danger">*</span></label>
                            <input type="file" name="excel_file" id="excelFileInput" class="form-control" accept=".xlsx,.xls" required>
                            <div class="form-text">Máximo 20MB. Debe contener una hoja por actividad con columnas de meses.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Año Fiscal Asignado</label>
                            <select name="year" id="importYearInput" class="form-select">
                                <option value="2024">2024</option>
                                <option value="2025" selected>2025 (Histórico)</option>
                                <option value="2026">2026</option>
                                <option value="2027">2027</option>
                            </select>
                        </div>
                    </div>

                    <!-- Progress & Results Area -->
                    <div id="importLoadingState" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary mb-2" role="status" style="width: 3rem; height: 3rem;"></div>
                        <p class="text-muted fw-bold mb-0">Procesando hojas, reconociendo categorías e importando transacciones...</p>
                        <small class="text-muted">Esto puede tardar unos segundos dependiendo del volumen del archivo.</small>
                    </div>

                    <div id="importResultsContainer" class="d-none">
                        <hr>
                        <h6 class="fw-bold text-success mb-3"><i class="fas fa-check-circle me-1"></i> Resumen de la Migración Realizada</h6>
                        <div class="row g-2 mb-3 text-center">
                            <div class="col-3">
                                <div class="p-2 border rounded bg-light">
                                    <div class="small text-muted">Hojas Procesadas</div>
                                    <div class="h5 fw-bold text-dark mb-0" id="resSheets">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 border rounded bg-light">
                                    <div class="small text-muted">Filas de Rubros</div>
                                    <div class="h5 fw-bold text-primary mb-0" id="resRows">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 border rounded bg-light">
                                    <div class="small text-muted">Trx Creadas</div>
                                    <div class="h5 fw-bold text-info mb-0" id="resTrx">0</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 border rounded bg-light">
                                    <div class="small text-muted">Monto Total</div>
                                    <div class="h5 fw-bold text-success mb-0" id="resAmount">S/ 0.00</div>
                                </div>
                            </div>
                        </div>

                        <!-- Supuestos Aplicados -->
                        <div class="mb-3">
                            <h6 class="fw-bold text-dark small mb-1"><i class="fas fa-lightbulb text-warning me-1"></i> Supuestos Técnicos Aplicados:</h6>
                            <ul class="small text-muted mb-0 ps-3" id="resAssumptions"></ul>
                        </div>

                        <!-- Observaciones Pendientes de Revisión Manual -->
                        <div id="pendingReviewSection" class="d-none">
                            <div class="alert alert-warning border-0 p-2 mb-2">
                                <strong class="small text-dark"><i class="fas fa-exclamation-triangle me-1"></i> Filas que requieren revisión manual:</strong>
                            </div>
                            <div class="table-responsive" style="max-height: 200px;">
                                <table class="table table-bordered table-sm table-striped small align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Hoja</th>
                                            <th>Fila</th>
                                            <th>Col</th>
                                            <th>Concepto</th>
                                            <th>Valor</th>
                                            <th>Motivo</th>
                                        </tr>
                                    </thead>
                                    <tbody id="resPendingRows"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" id="btnSubmitImport" class="btn btn-primary btn-sm shadow-sm">
                        <i class="fas fa-upload me-1"></i> Iniciar Migración
                    </button>
                    <button type="button" id="btnReloadDashboard" class="btn btn-success btn-sm shadow-sm d-none" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-1"></i> Actualizar Tablero
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Datos para Gráficos
    const chartData = @json($matrixData['chart_data']);
    const actChartData = @json($matrixData['activity_chart_data']);

    // --- GRÁFICO 1: Comparativa Mensual de Ingresos vs Egresos y Saldo ---
    const ctxMonthly = document.getElementById('monthlyComparativeChart').getContext('2d');
    new Chart(ctxMonthly, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Ingresos (S/)',
                    data: chartData.incomes,
                    backgroundColor: 'rgba(25, 135, 84, 0.75)',
                    borderColor: 'rgb(25, 135, 84)',
                    borderWidth: 1,
                    borderRadius: 4,
                    order: 2,
                },
                {
                    label: 'Egresos (S/)',
                    data: chartData.expenses,
                    backgroundColor: 'rgba(220, 53, 69, 0.75)',
                    borderColor: 'rgb(220, 53, 69)',
                    borderWidth: 1,
                    borderRadius: 4,
                    order: 2,
                },
                {
                    label: 'Saldo Neto (S/)',
                    data: chartData.balances,
                    type: 'line',
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2.5,
                    pointBackgroundColor: 'rgb(13, 110, 253)',
                    pointRadius: 4,
                    fill: false,
                    tension: 0.25,
                    order: 1,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { boxWidth: 12, font: { size: 11 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let val = context.parsed.y || 0;
                            return context.dataset.label + ': S/ ' + val.toLocaleString('es-PE', { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'S/ ' + value.toLocaleString('es-PE');
                        }
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // --- GRÁFICO 2: Comparativa por Actividad Productiva (Ingresos vs Egresos) ---
    const ctxActivity = document.getElementById('activityComparativeChart').getContext('2d');
    new Chart(ctxActivity, {
        type: 'bar',
        data: {
            labels: actChartData.labels.map(l => l.length > 20 ? l.substring(0, 20) + '...' : l),
            datasets: [
                {
                    label: 'Ingresos (S/)',
                    data: actChartData.incomes,
                    backgroundColor: 'rgba(25, 135, 84, 0.8)',
                    borderColor: 'rgb(25, 135, 84)',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Egresos (S/)',
                    data: actChartData.expenses,
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderColor: 'rgb(220, 53, 69)',
                    borderWidth: 1,
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { boxWidth: 12, font: { size: 11 } }
                },
                tooltip: {
                    callbacks: {
                        title: function(items) {
                            const idx = items[0].dataIndex;
                            return actChartData.labels[idx];
                        },
                        label: function(context) {
                            let val = context.parsed.y || 0;
                            return context.dataset.label + ': S/ ' + val.toLocaleString('es-PE', { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'S/ ' + value.toLocaleString('es-PE');
                        }
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    ticks: { font: { size: 10 } },
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Manejo de la Importación de Archivo Excel vía AJAX
    const form = document.getElementById('importExcelForm');
    const loadingState = document.getElementById('importLoadingState');
    const resultsContainer = document.getElementById('importResultsContainer');
    const btnSubmit = document.getElementById('btnSubmitImport');
    const btnReload = document.getElementById('btnReloadDashboard');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const fileInput = document.getElementById('excelFileInput');
        if (!fileInput.files.length) {
            Swal.fire('Atención', 'Seleccione un archivo Excel para continuar.', 'warning');
            return;
        }

        const formData = new FormData(form);

        // Mostrar estado de carga
        loadingState.classList.remove('d-none');
        resultsContainer.classList.add('d-none');
        btnSubmit.disabled = true;

        fetch("{{ route('productive_activities.cost_center.import_excel') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            loadingState.classList.add('d-none');
            btnSubmit.disabled = false;

            if (data.status === 'success') {
                const s = data.summary;
                resultsContainer.classList.remove('d-none');
                btnReload.classList.remove('d-none');

                document.getElementById('resSheets').innerText = s.total_sheets_processed;
                document.getElementById('resRows').innerText = s.total_rows_imported;
                document.getElementById('resTrx').innerText = s.total_transactions_created;
                document.getElementById('resAmount').innerText = 'S/ ' + s.total_amount_imported.toLocaleString('es-PE', { minimumFractionDigits: 2 });

                // Llenar Supuestos
                const assumptionsList = document.getElementById('resAssumptions');
                assumptionsList.innerHTML = '';
                (s.assumptions_applied || []).forEach(a => {
                    const li = document.createElement('li');
                    li.innerText = a;
                    assumptionsList.appendChild(li);
                });

                // Llenar Filas Pendientes
                const pendingSection = document.getElementById('pendingReviewSection');
                const pendingTable = document.getElementById('resPendingRows');
                pendingTable.innerHTML = '';

                if (s.pending_manual_review && s.pending_manual_review.length > 0) {
                    pendingSection.classList.remove('d-none');
                    s.pending_manual_review.forEach(p => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><strong>${p.sheet}</strong></td>
                            <td>${p.row}</td>
                            <td>${p.col}</td>
                            <td>${p.concept || '-'}</td>
                            <td class="text-danger font-monospace">${p.value}</td>
                            <td class="text-muted">${p.reason}</td>
                        `;
                        pendingTable.appendChild(tr);
                    });
                } else {
                    pendingSection.classList.add('d-none');
                }

                Swal.fire('¡Importación Exitosa!', data.message, 'success');
            } else {
                Swal.fire('Error en la Importación', data.message || 'Ocurrió un error al procesar el archivo.', 'error');
            }
        })
        .catch(err => {
            loadingState.classList.add('d-none');
            btnSubmit.disabled = false;
            Swal.fire('Error del Servidor', 'No se pudo comunicar con el servicio de migración.', 'error');
            console.error(err);
        });
    });
});
</script>
@endsection
