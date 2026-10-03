@extends('admin.layout')

@section('title', 'Estado de Resultados por Función')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800"><i class="fas fa-tasks text-success me-2"></i>Estado de Resultados por Función</h3>
            <p class="text-muted small mb-0">Estructura operativa y funcional de costos de venta y gastos de administración y ventas.</p>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('accounting.statements.balance_sheet') }}" class="btn btn-outline-primary btn-sm me-1">
                <i class="fas fa-landmark me-1"></i> Balance General
            </a>
            <a href="{{ route('accounting.statements.nature') }}" class="btn btn-outline-info btn-sm me-1">
                <i class="fas fa-chart-line me-1"></i> Por Naturaleza
            </a>
            <a href="{{ route('accounting.period_closing.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-lock me-1"></i> Cierres Contables
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-primary border-3 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">UTILIDAD BRUTA</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($report['totals']['utilidad_bruta'] ?? 0, 2) }}</div>
                    <span class="text-xs text-muted">Ventas Netas - Costo de Ventas</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-info border-3 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">UTILIDAD OPERATIVA</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($report['totals']['utilidad_operativa'] ?? 0, 2) }}</div>
                    <span class="text-xs text-muted">Bruta - Gastos Adm. y Ventas</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-warning border-3 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">ANTES DE IMPUESTO</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($report['totals']['resultado_antes_imp'] ?? 0, 2) }}</div>
                    <span class="text-xs text-muted">Operativa ± Otros ± Financieros</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-{{ ($report['totals']['resultado_neto'] ?? 0) >= 0 ? 'success' : 'danger' }} border-3 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-{{ ($report['totals']['resultado_neto'] ?? 0) >= 0 ? 'success' : 'danger' }} text-uppercase mb-1">RESULTADO NETO</div>
                    <div class="h5 mb-0 font-weight-bold text-{{ ($report['totals']['resultado_neto'] ?? 0) >= 0 ? 'success' : 'danger' }}">
                        S/ {{ number_format($report['totals']['resultado_neto'] ?? 0, 2) }}
                    </div>
                    <span class="text-xs text-muted">{{ ($report['totals']['resultado_neto'] ?? 0) >= 0 ? 'Utilidad Neta' : 'Pérdida Neta' }} Final</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('accounting.statements.function') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">Período Contable</label>
                    <select name="period_id" class="form-select form-select-sm">
                        <option value="">-- Todos / Fechas Manuales --</option>
                        @foreach($periods as $p)
                            <option value="{{ $p->id }}" {{ ($filters['period_id'] ?? null) == $p->id ? 'selected' : '' }}>
                                {{ $p->period_code }} ({{ $p->status }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">Desde</label>
                    <input type="date" name="date_from" class="form-control form-select-sm" value="{{ $filters['date_from'] ?? ($report['period']['from'] ?? date('Y-m-01')) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">Hasta</label>
                    <input type="date" name="date_to" class="form-control form-select-sm" value="{{ $filters['date_to'] ?? ($report['period']['to'] ?? date('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">Comparar Con Período</label>
                    <select name="comp_period_id" class="form-select form-select-sm">
                        <option value="">-- Sin Comparativo --</option>
                        @foreach($periods as $p)
                            <option value="{{ $p->id }}" {{ ($comparisonFilters['period_id'] ?? null) == $p->id ? 'selected' : '' }}>
                                Comparar con {{ $p->period_code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button type="submit" class="btn btn-primary btn-sm me-1">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('accounting.statements.export_pdf', ['statement' => 'function']) }}?{{ http_build_query(request()->all()) }}" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf"></i>
                    </a>
                    <a href="{{ route('accounting.statements.export_excel', ['statement' => 'function']) }}?{{ http_build_query(request()->all()) }}" class="btn btn-success btn-sm ms-1">
                        <i class="fas fa-file-excel"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold text-gray-800">
                <i class="fas fa-list me-2 text-success"></i>Estado de Resultados por Función ({{ $report['period']['from'] }} al {{ $report['period']['to'] }})
            </h5>
            @if($report['has_comparison'])
                <span class="badge bg-info text-white">Comparativo vs {{ $report['comparison']['period']['from'] }} al {{ $report['comparison']['period']['to'] }}</span>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50%;">Función Operacional</th>
                            <th class="text-end" style="width: 18%;">Actual (S/)</th>
                            @if($report['has_comparison'])
                                <th class="text-end" style="width: 18%;">Anterior (S/)</th>
                                <th class="text-end" style="width: 7%;">Var. (S/)</th>
                                <th class="text-end" style="width: 7%;">Var. %</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['lines'] as $line)
                            @php
                                $isSub = $line['line_type'] === 'SUBTOTAL';
                                $isTot = $line['line_type'] === 'TOTAL';
                                $rowClass = $isTot ? 'table-success fw-bold text-dark fs-6' : ($isSub ? 'table-light fw-bold text-dark' : '');
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td style="padding-left: {{ $line['level'] * 1.5 }}rem;">
                                    @if($isTot)
                                        <i class="fas fa-award text-success me-1"></i>
                                    @elseif($isSub)
                                        <i class="fas fa-calculator text-muted me-1"></i>
                                    @else
                                        <i class="fas fa-caret-right text-muted me-1"></i>
                                    @endif
                                    {{ $line['line_name'] }}
                                </td>
                                <td class="text-end font-monospace {{ ($line['current_amount'] ?? 0) < 0 ? 'text-danger' : '' }}">
                                    S/ {{ number_format($line['current_amount'], 2) }}
                                </td>
                                @if($report['has_comparison'])
                                    <td class="text-end font-monospace">
                                        S/ {{ number_format($line['comparison_amount'], 2) }}
                                    </td>
                                    <td class="text-end font-monospace {{ ($line['variance_abs'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ ($line['variance_abs'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($line['variance_abs'] ?? 0, 2) }}
                                    </td>
                                    <td class="text-end font-monospace {{ ($line['variance_pct'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ ($line['variance_pct'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($line['variance_pct'] ?? 0, 2) }}%
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
