@extends('admin.layout')

@section('title', 'Estado de Situación Financiera (Balance General)')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800"><i class="fas fa-landmark text-primary me-2"></i>Estado de Situación Financiera</h3>
            <p class="text-muted small mb-0">Balance General normalizado según el Plan Contable General Empresarial (PCGE) y NIIF.</p>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('accounting.statements.nature') }}" class="btn btn-outline-info btn-sm me-1">
                <i class="fas fa-chart-line me-1"></i> Por Naturaleza
            </a>
            <a href="{{ route('accounting.statements.function') }}" class="btn btn-outline-success btn-sm me-1">
                <i class="fas fa-tasks me-1"></i> Por Función
            </a>
            <a href="{{ route('accounting.period_closing.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-lock me-1"></i> Cierres Contables
            </a>
        </div>
    </div>

    <!-- Equation KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-primary border-3 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">TOTAL ACTIVO</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($report['totals']['total_assets'], 2) }}</div>
                            <span class="text-xs text-muted">Corr: S/ {{ number_format($report['totals']['total_current_assets'], 2) }} | No Corr: S/ {{ number_format($report['totals']['total_non_current_assets'], 2) }}</span>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-warning border-3 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">TOTAL PASIVO</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($report['totals']['total_liabilities'], 2) }}</div>
                            <span class="text-xs text-muted">Corr: S/ {{ number_format($report['totals']['total_current_liabilities'], 2) }} | No Corr: S/ {{ number_format($report['totals']['total_non_current_liabilities'], 2) }}</span>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-info border-3 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">PATRIMONIO NETO</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($report['totals']['total_equity'], 2) }}</div>
                            <span class="text-xs text-muted">Resultado Período: S/ {{ number_format($report['net_result'], 2) }}</span>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-{{ $report['totals']['is_balanced'] ? 'success' : 'danger' }} border-3 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs font-weight-bold text-{{ $report['totals']['is_balanced'] ? 'success' : 'danger' }} text-uppercase mb-1">ECUACIÓN CONTABLE</div>
                            <div class="h6 mb-0 font-weight-bold text-{{ $report['totals']['is_balanced'] ? 'success' : 'danger' }}">
                                @if($report['totals']['is_balanced'])
                                    <i class="fas fa-check-circle me-1"></i> A = P + PN (CUADRADO)
                                @else
                                    <i class="fas fa-exclamation-triangle me-1"></i> DESCUADRADO (S/ {{ number_format($report['totals']['imbalance'], 2) }})
                                @endif
                            </div>
                            <span class="text-xs text-muted">Pasivo + Patrimonio: S/ {{ number_format($report['totals']['total_liabilities_and_equity'], 2) }}</span>
                        </div>
                        <div class="col-auto">
                            <i class="fas {{ $report['totals']['is_balanced'] ? 'fa-shield-alt text-success' : 'fa-bell text-danger' }} fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!$report['totals']['is_balanced'])
    <div class="alert alert-danger shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
            <div>
                <h5 class="alert-heading mb-1">Alerta de Inconsistencia Contable</h5>
                <p class="mb-0">{{ $report['totals']['alert'] }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('accounting.statements.balance_sheet') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">Período Contable</label>
                    <select name="period_id" class="form-select form-select-sm">
                        <option value="">-- Todos / Fecha Manual --</option>
                        @foreach($periods as $p)
                            <option value="{{ $p->id }}" {{ ($filters['period_id'] ?? null) == $p->id ? 'selected' : '' }}>
                                {{ $p->period_code }} ({{ $p->status }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">Fecha de Corte</label>
                    <input type="date" name="as_of_date" class="form-control form-select-sm" value="{{ $filters['as_of_date'] ?? ($report['period']['to'] ?? date('Y-m-d')) }}">
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
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-2 text-end">
                    <a href="{{ route('accounting.statements.export_pdf', ['statement' => 'balance-sheet']) }}?{{ http_build_query(request()->all()) }}" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </a>
                    <a href="{{ route('accounting.statements.export_excel', ['statement' => 'balance-sheet']) }}?{{ http_build_query(request()->all()) }}" class="btn btn-success btn-sm ms-1">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statement Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold text-gray-800">
                <i class="fas fa-table me-2 text-primary"></i>Estado de Situación Financiera al {{ $report['period']['to'] }}
            </h5>
            @if($report['has_comparison'])
                <span class="badge bg-info text-white">Comparativo vs {{ $report['comparison']['period']['to'] }}</span>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45%;">Rubro / Partida Contable</th>
                            <th class="text-end" style="width: 18%;">Al {{ $report['period']['to'] }} (S/)</th>
                            @if($report['has_comparison'])
                                <th class="text-end" style="width: 18%;">Al {{ $report['comparison']['period']['to'] }} (S/)</th>
                                <th class="text-end" style="width: 10%;">Var. Abs. (S/)</th>
                                <th class="text-end" style="width: 9%;">Var. %</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['lines'] as $line)
                            @php
                                $isHdr = $line['line_type'] === 'HEADER';
                                $isSub = $line['line_type'] === 'SUBTOTAL';
                                $isTot = $line['line_type'] === 'TOTAL';
                                $rowClass = $isTot ? 'table-dark text-white fw-bold' : ($isSub ? 'table-secondary fw-bold' : ($isHdr ? 'table-light fw-bold text-primary text-uppercase pt-3' : ''));
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td style="padding-left: {{ $line['level'] * 1.5 }}rem;">
                                    @if($isHdr)
                                        <i class="fas fa-folder me-1 text-primary"></i>
                                    @elseif($isSub || $isTot)
                                        <i class="fas fa-chevron-right me-1 small"></i>
                                    @else
                                        <i class="fas fa-caret-right me-1 text-muted small"></i>
                                    @endif
                                    {{ $line['line_name'] }}
                                </td>
                                <td class="text-end font-monospace {{ ($line['amount'] ?? 0) < 0 ? 'text-danger' : '' }}">
                                    @if(!$isHdr)
                                        S/ {{ number_format($line['current_amount'], 2) }}
                                    @endif
                                </td>
                                @if($report['has_comparison'])
                                    <td class="text-end font-monospace">
                                        @if(!$isHdr)
                                            S/ {{ number_format($line['comparison_amount'], 2) }}
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace {{ ($line['variance_abs'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                        @if(!$isHdr && isset($line['variance_abs']))
                                            {{ $line['variance_abs'] >= 0 ? '+' : '' }}{{ number_format($line['variance_abs'], 2) }}
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace {{ ($line['variance_pct'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                        @if(!$isHdr && isset($line['variance_pct']))
                                            {{ $line['variance_pct'] >= 0 ? '+' : '' }}{{ number_format($line['variance_pct'], 2) }}%
                                        @endif
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
