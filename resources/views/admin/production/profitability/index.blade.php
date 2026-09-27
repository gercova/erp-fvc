@extends('admin.layout')
@section('title', 'Reporte de Rentabilidad por Producto y Actividad')

@section('styles')
<style>
    .metric-profit-card {
        border-radius: 8px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        transition: transform 0.15s ease;
    }
    .metric-profit-card:hover {
        transform: translateY(-2px);
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Producción</li>
                    <li class="breadcrumb-item active" aria-current="page">Reporte de Rentabilidad</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-chart-line me-2 text-primary"></i>Reporte de Rentabilidad por Producto y Actividad
            </h1>
            <p class="text-muted small mb-0">Cruce de costos capturados (insumos y mano de obra) versus ingresos reales por ventas (Comprobantes y Notas de Venta)</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Imprimir Reporte
            </button>
        </div>
    </div>

    <!-- Filters Bar Form -->
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="{{ route('production.profitability.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
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
                    <label class="form-label small fw-bold text-muted mb-1">Producto</label>
                    <select name="product_id" class="form-select form-select-sm">
                        <option value="">-- Todos los Productos --</option>
                        @foreach($producedItems as $p)
                            <option value="{{ $p->id }}" {{ $selectedProductId == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->activity->name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Fecha Desde</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Fecha Hasta</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                        <i class="fas fa-filter me-1"></i> Filtrar Reporte
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Global Metrics Summary -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-profit-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Volumen Total Cosechado</div>
                        <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($totalGlobalVolume, 2) }}</div>
                        <small class="text-muted">Unidades / Kilos cosechados</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(0, 97, 242, 0.1); color: var(--bs-primary);">
                        <i class="fas fa-weight fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-profit-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Costo Total Capturado</div>
                        <div class="h3 mb-0 fw-bold text-danger mt-1">S/ {{ number_format($totalGlobalCost, 2) }}</div>
                        <small class="text-muted">Insumos + M.O. + Indirectos</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-coins fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-profit-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Ingresos por Ventas Reales</div>
                        <div class="h3 mb-0 fw-bold text-success mt-1">S/ {{ number_format($totalGlobalRevenue, 2) }}</div>
                        <small class="text-muted">Facturación SUNAT + Notas POS</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="fas fa-hand-holding-usd fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-profit-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Margen Bruto Global</div>
                        <div class="h3 mb-0 fw-bold {{ $totalGlobalMargin >= 0 ? 'text-success' : 'text-danger' }} mt-1">
                            S/ {{ number_format($totalGlobalMargin, 2) }}
                        </div>
                        <small class="fw-bold {{ $totalGlobalRoi >= 0 ? 'text-success' : 'text-danger' }}">
                            Rentabilidad (ROI): {{ number_format($totalGlobalRoi, 1) }}%
                        </small>
                    </div>
                    <div class="p-3 rounded-3" style="background: {{ $totalGlobalMargin >= 0 ? 'rgba(25, 135, 84, 0.1)' : 'rgba(220, 53, 69, 0.1)' }}; color: {{ $totalGlobalMargin >= 0 ? '#198754' : '#dc3545' }};">
                        <i class="fas {{ $totalGlobalMargin >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }} fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Rule Proration Notice -->
    <div class="alert alert-light border border-info border-start-4 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle text-info fs-4 me-3"></i>
            <div>
                <strong class="d-block text-dark">Nota Metodológica de Prorrateo de Costos Compartidos:</strong>
                <span class="small text-muted">
                    Los costos de insumos generales, mano de obra y gastos operativos (overhead) registrados a nivel de actividad se distribuyen de manera equitativa entre los productos cosechados de la misma línea. Para un costeo por absorción ABC más exacto, se requiere definir la regla de negocio institucional de ponderación (por volumen cosechado o facturación relativa).
                </span>
            </div>
        </div>
    </div>

    <!-- Profitability Detailed Table Card -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-table me-1 text-primary"></i> Desglose de Rentabilidad por Producto y Actividad
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.9rem;">
                    <thead class="table-light text-center text-uppercase small text-muted">
                        <tr>
                            <th class="text-start">Actividad / Producto</th>
                            <th>Vol. Cosecha</th>
                            <th>Costo Insumos</th>
                            <th>Costo Mano Obra</th>
                            <th>Gastos Indirectos (Overhead)</th>
                            <th>Costo Total</th>
                            <th>Costo Unit.</th>
                            <th>Ventas Reales</th>
                            <th>Margen Bruto</th>
                            <th>Margen s/ Venta</th>
                            <th>Rentab. (ROI)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $row)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $row['product_name'] }}</div>
                                    <small class="badge bg-light text-dark border">{{ $row['activity_name'] }}</small>
                                    @if(!$row['is_published'])
                                        <small class="badge bg-warning text-dark ms-1">No en POS</small>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">
                                    {{ number_format($row['volume_harvested'], 2) }} <small class="text-muted">{{ $row['unit'] }}</small>
                                </td>
                                <td class="text-end text-muted">S/ {{ number_format($row['input_cost'], 2) }}</td>
                                <td class="text-end text-muted">S/ {{ number_format($row['labor_cost'], 2) }}</td>
                                <td class="text-end text-muted">S/ {{ number_format($row['overhead_cost'], 2) }}</td>
                                <td class="text-end fw-bold text-danger">S/ {{ number_format($row['total_cost'], 2) }}</td>
                                <td class="text-end font-monospace">S/ {{ number_format($row['unit_cost'], 2) }}</td>
                                <td class="text-end fw-bold text-success">
                                    S/ {{ number_format($row['sales_revenue'], 2) }}
                                    <div class="small fw-normal text-muted">({{ number_format($row['sales_quantity'], 2) }} {{ $row['unit'] }})</div>
                                </td>
                                <td class="text-end fw-bold {{ $row['gross_margin'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    S/ {{ number_format($row['gross_margin'], 2) }}
                                </td>
                                <td class="text-center fw-semibold {{ $row['margin_percent'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($row['margin_percent'], 1) }}%
                                </td>
                                <td class="text-center">
                                    @if($row['roi_percent'] >= 0)
                                        <span class="badge bg-success text-white">+{{ number_format($row['roi_percent'], 1) }}%</span>
                                    @else
                                        <span class="badge bg-danger text-white">{{ number_format($row['roi_percent'], 1) }}%</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    No se encontraron registros de producción ni ventas para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light text-end fw-bold">
                        <tr>
                            <td class="text-start">TOTALES CONSOLIDADOS:</td>
                            <td>{{ number_format($totalGlobalVolume, 2) }}</td>
                            <td colspan="3">-</td>
                            <td class="text-danger">S/ {{ number_format($totalGlobalCost, 2) }}</td>
                            <td>-</td>
                            <td class="text-success">S/ {{ number_format($totalGlobalRevenue, 2) }}</td>
                            <td class="{{ $totalGlobalMargin >= 0 ? 'text-success' : 'text-danger' }}">S/ {{ number_format($totalGlobalMargin, 2) }}</td>
                            <td>-</td>
                            <td class="text-center">
                                <span class="badge {{ $totalGlobalRoi >= 0 ? 'bg-success' : 'bg-danger' }}">
                                    {{ $totalGlobalRoi >= 0 ? '+' : '' }}{{ number_format($totalGlobalRoi, 1) }}%
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
