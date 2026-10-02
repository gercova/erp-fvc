@extends('admin.layout')

@section('title', 'Antigüedad de Deuda - Cuentas por Cobrar (Clientes)')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800"><i class="fas fa-hand-holding-usd text-primary me-2"></i>Cuentas por Cobrar: Antigüedad de Deuda (Aging)</h3>
            <p class="text-muted small mb-0">Monitoreo de cartera vencida de clientes con comprobantes a crédito y pedidos de actividad APE.</p>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('treasury.aging.payables') }}" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-file-invoice-dollar me-1"></i> Ver Cuentas por Pagar (Proveedores)
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('treasury.aging.receivables') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small font-weight-bold mb-1">Filtrar por Cliente</label>
                    <select name="client_id" class="form-select form-select-sm">
                        <option value="">-- Todos los Clientes con Deuda --</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ $clientId == $c->id ? 'selected' : '' }}>
                                {{ $c->rzn_social_usuario }} ({{ $c->num_doc }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small font-weight-bold mb-1">Fecha de Corte</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate ?? date('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter me-1"></i> Aplicar Filtro
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col">
            <div class="card shadow-sm border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Cartera Pendiente</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($report['grand_totals']['total_pending'], 2) }}</div>
                    <small class="text-muted">{{ $report['items_count'] }} Documentos</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Corriente (0 - 30 Días)</div>
                    <div class="h5 mb-0 font-weight-bold text-success">S/ {{ number_format($report['grand_totals']['current'], 2) }}</div>
                    <small class="text-muted">Vigente sin mora</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Vencido (31 - 60 Días)</div>
                    <div class="h5 mb-0 font-weight-bold text-info">S/ {{ number_format($report['grand_totals']['days_31_60'], 2) }}</div>
                    <small class="text-muted">Mora temprana</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Vencido (61 - 90 Días)</div>
                    <div class="h5 mb-0 font-weight-bold text-warning">S/ {{ number_format($report['grand_totals']['days_61_90'], 2) }}</div>
                    <small class="text-muted">Mora media</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-left-danger h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Vencido (> 90 Días)</div>
                    <div class="h5 mb-0 font-weight-bold text-danger">S/ {{ number_format($report['grand_totals']['over_90'], 2) }}</div>
                    <small class="text-muted">Cobranza dudosa</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Aging Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-users me-1"></i> Desglose por Cliente</h6>
            <span class="badge bg-secondary">{{ count($report['entities']) }} Clientes con Saldo Pendiente</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>CLIENTE</th>
                            <th>DOC. IDENTIDAD</th>
                            <th class="text-end">TOTAL DEUDA</th>
                            <th class="text-end text-success">0-30 DÍAS</th>
                            <th class="text-end text-info">31-60 DÍAS</th>
                            <th class="text-end text-warning">61-90 DÍAS</th>
                            <th class="text-end text-danger">> 90 DÍAS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['entities'] as $entity)
                            <tr class="fw-bold">
                                <td>
                                    <span class="text-primary">{{ $entity['name'] }}</span>
                                    <small class="d-block text-muted fw-normal">{{ count($entity['details']) }} Documentos</small>
                                </td>
                                <td><code>{{ $entity['document'] }}</code></td>
                                <td class="text-end">S/ {{ number_format($entity['total_pending'], 2) }}</td>
                                <td class="text-end text-success">S/ {{ number_format($entity['current'], 2) }}</td>
                                <td class="text-end text-info">S/ {{ number_format($entity['days_31_60'], 2) }}</td>
                                <td class="text-end text-warning">S/ {{ number_format($entity['days_61_90'], 2) }}</td>
                                <td class="text-end text-danger">S/ {{ number_format($entity['over_90'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                                    No se registran deudas pendientes por cobrar para los criterios seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(!empty($report['entities']))
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <th colspan="2">TOTAL GENERAL CARTERA POR COBRAR</th>
                                <th class="text-end">S/ {{ number_format($report['grand_totals']['total_pending'], 2) }}</th>
                                <th class="text-end text-success">S/ {{ number_format($report['grand_totals']['current'], 2) }}</th>
                                <th class="text-end text-info">S/ {{ number_format($report['grand_totals']['days_31_60'], 2) }}</th>
                                <th class="text-end text-warning">S/ {{ number_format($report['grand_totals']['days_61_90'], 2) }}</th>
                                <th class="text-end text-danger">S/ {{ number_format($report['grand_totals']['over_90'], 2) }}</th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
