@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary font-monospace">{{ $agreement->code }}</span>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Tablero de Cumplimiento</h1>
            </div>
            <p class="text-muted small mb-0">{{ $agreement->title }} — Contraparte: {{ $agreement->client?->nombres }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('agreements.show', $agreement->id) }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver a Detalle
            </a>
        </div>
    </div>

    <!-- Overall Compliance Score Card -->
    <div class="card shadow-sm mb-4 border-start-lg border-start-primary">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3 text-center border-end">
                    <div class="text-xs fw-bold text-uppercase text-muted mb-1">Índice de Cumplimiento Global</div>
                    <div class="display-5 fw-bold {{ $compliance_percentage >= 80 ? 'text-success' : ($compliance_percentage >= 50 ? 'text-primary' : 'text-warning') }}">
                        {{ $compliance_percentage }}%
                    </div>
                    <span class="badge {{ $compliance_percentage >= 100 ? 'bg-success' : ($compliance_percentage >= 50 ? 'bg-primary' : 'bg-warning text-dark') }}">
                        {{ $compliance_percentage >= 100 ? 'Completado' : ($compliance_percentage > 0 ? 'En Progreso' : 'Sin Iniciar') }}
                    </span>
                </div>
                <div class="col-md-9 px-4">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <small class="text-muted text-uppercase fw-bold d-block">Obligaciones Totales</small>
                            <span class="fs-5 fw-bold">{{ $total_obligations }}</span>
                            <div class="small text-muted">Completadas: <strong class="text-success">{{ $completed_obligations }}</strong></div>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted text-uppercase fw-bold d-block">Compromisos Vencidos</small>
                            <span class="fs-5 fw-bold text-danger">{{ $overdue_obligations }}</span>
                            <div class="small text-muted">Pendientes: {{ $pending_obligations }}</div>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted text-uppercase fw-bold d-block">Efectividad Financiera</small>
                            <span class="fs-5 fw-bold text-success">
                                {{ $scheduled_revenue > 0 ? round(($collected_revenue / $scheduled_revenue) * 100, 1) : 0 }}%
                            </span>
                            <div class="small text-muted">Cobrado: S/ {{ number_format($collected_revenue, 2) }}</div>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 10px;">
                        <div class="progress-bar {{ $compliance_percentage >= 80 ? 'bg-success' : 'bg-primary' }}" 
                             role="progressbar" 
                             style="width: {{ min(100, $compliance_percentage) }}%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Schedule & Obligations breakdown -->
    <div class="row g-4">
        <!-- Financial Revenue Breakdown -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-money-bill-wave me-2"></i>Seguimiento Financiero de Cuotas</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-muted d-block">Programado</small>
                                <strong class="fs-6">S/ {{ number_format($scheduled_revenue, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-muted d-block">Facturado</small>
                                <strong class="fs-6 text-info">S/ {{ number_format($invoiced_revenue, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-muted d-block">Cobrado</small>
                                <strong class="fs-6 text-success">S/ {{ number_format($collected_revenue, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-muted d-block">Saldo Pendiente</small>
                                <strong class="fs-6 text-danger">S/ {{ number_format($pending_revenue, 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    <h6 class="small fw-bold text-muted text-uppercase mb-2">Cuotas del Convenio</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>N°</th>
                                    <th>F. Vence</th>
                                    <th>Hito / Condición</th>
                                    <th class="text-end">Monto</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($agreement->installments as $inst)
                                    <tr>
                                        <td class="fw-bold">{{ $inst->installment_number }}</td>
                                        <td>{{ $inst->due_date ? \Carbon\Carbon::parse($inst->due_date)->format('d/m/Y') : '-' }}</td>
                                        <td><small>{{ $inst->milestone_condition ?: ($inst->description ?: 'Cuota estándar') }}</small></td>
                                        <td class="text-end fw-semibold">S/ {{ number_format($inst->amount, 2) }}</td>
                                        <td class="text-center">
                                            @php
                                                $st = $inst->status instanceof \App\Enums\InstallmentStatus ? $inst->status->value : $inst->status;
                                            @endphp
                                            <span class="badge {{ $st === 'COLLECTED' || $st === 'PAID' ? 'bg-success' : ($st === 'INVOICED' ? 'bg-info' : ($st === 'OVERDUE' ? 'bg-danger' : 'bg-warning text-dark')) }}">
                                                {{ $st }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-2">Sin cuotas programadas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Obligations Breakdown by Party -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-tasks me-2"></i>Cumplimiento de Obligaciones por Parte</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small fw-bold mb-1">
                            <span>Nuestra Institución ({{ $institution_obligations->where('status', 'COMPLETED')->count() }}/{{ $institution_obligations->count() }})</span>
                            <span>{{ $institution_obligations->count() > 0 ? round(($institution_obligations->where('status', 'COMPLETED')->count() / $institution_obligations->count()) * 100) : 100 }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: {{ $institution_obligations->count() > 0 ? round(($institution_obligations->where('status', 'COMPLETED')->count() / $institution_obligations->count()) * 100) : 100 }}%;"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between small fw-bold mb-1">
                            <span>Contraparte ({{ $counterparty_obligations->where('status', 'COMPLETED')->count() }}/{{ $counterparty_obligations->count() }})</span>
                            <span>{{ $counterparty_obligations->count() > 0 ? round(($counterparty_obligations->where('status', 'COMPLETED')->count() / $counterparty_obligations->count()) * 100) : 100 }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: {{ $counterparty_obligations->count() > 0 ? round(($counterparty_obligations->where('status', 'COMPLETED')->count() / $counterparty_obligations->count()) * 100) : 100 }}%;"></div>
                        </div>
                    </div>

                    <h6 class="small fw-bold text-muted text-uppercase mb-2">Listado de Obligaciones</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Compromiso</th>
                                    <th>Parte</th>
                                    <th>F. Límite</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($agreement->obligations as $obl)
                                    <tr>
                                        <td>
                                            <strong>{{ $obl->title }}</strong><br>
                                            <small class="text-muted">{{ $obl->responsibleUser?->name ?? 'Sin responsable asignado' }}</small>
                                        </td>
                                        <td><small>{{ $obl->responsible_party }}</small></td>
                                        <td><small>{{ $obl->due_date ? \Carbon\Carbon::parse($obl->due_date)->format('d/m/Y') : '-' }}</small></td>
                                        <td class="text-center">
                                            @if($obl->status === 'COMPLETED')
                                                <span class="badge bg-success">Completado</span>
                                            @elseif($obl->status === 'OVERDUE' || $obl->isOverdue())
                                                <span class="badge bg-danger">Vencido</span>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ $obl->status }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-2">Sin compromisos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
