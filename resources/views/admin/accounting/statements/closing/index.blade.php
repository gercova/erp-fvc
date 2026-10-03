@extends('admin.layout')

@section('title', 'Cierre y Apertura de Períodos Contables')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800"><i class="fas fa-lock text-secondary me-2"></i>Cierre y Apertura de Períodos Contables</h3>
            <p class="text-muted small mb-0">Gestión de cierres mensuales, cierre anual definitivo, reaperturas auditadas y flujo de firmas institucionales.</p>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('accounting.statements.balance_sheet') }}" class="btn btn-outline-primary btn-sm me-1">
                <i class="fas fa-landmark me-1"></i> Balance General
            </a>
            <a href="{{ route('accounting.trial_balance.index') }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-table me-1"></i> Balance Comprobación
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Year Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('accounting.period_closing.index') }}" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label small text-muted mb-0 fw-bold">Año Fiscal:</label>
                </div>
                <div class="col-auto">
                    <select name="fiscal_year" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ $fiscalYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Periods Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="m-0 font-weight-bold text-gray-800">
                <i class="fas fa-calendar-alt me-2 text-primary"></i>Períodos Contables del Ejercicio {{ $fiscalYear }}
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Período</th>
                            <th>Rango de Fechas</th>
                            <th>Estado</th>
                            <th>Cierre / Bloqueo</th>
                            <th>Asientos Cierre / Apertura</th>
                            <th>Flujo Aprobación</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($periods as $period)
                            @php
                                $badgeClass = match($period->status) {
                                    'OPEN'        => 'bg-success text-white',
                                    'SOFT_CLOSED' => 'bg-info text-white',
                                    'CLOSED'      => 'bg-warning text-dark',
                                    'LOCKED'      => 'bg-danger text-white',
                                    default       => 'bg-secondary text-white',
                                };
                                $latestClosure = $period->closures->first();
                            @endphp
                            <tr>
                                <td class="fw-bold font-monospace">
                                    {{ $period->period_code }}
                                    @if($period->month == 12)
                                        <span class="badge bg-dark ms-1">Fin Ejercicio</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $period->start_date ? $period->start_date->format('d/m/Y') : '' }} al {{ $period->end_date ? $period->end_date->format('d/m/Y') : '' }}
                                </td>
                                <td>
                                    <span class="badge {{ $badgeClass }} px-2 py-1">{{ $period->status }}</span>
                                </td>
                                <td class="small">
                                    @if($period->closed_at)
                                        <i class="fas fa-user-lock text-muted me-1"></i>{{ $period->closedBy?->nombres ?? 'Usuario' }}<br>
                                        <span class="text-muted">{{ $period->closed_at->format('d/m/Y H:i') }}</span>
                                    @else
                                        <span class="text-muted">Sin cerrar</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($period->closingEntry)
                                        <div><i class="fas fa-file-invoice text-danger me-1"></i>Cierre: {{ $period->closingEntry->entry_number }}</div>
                                    @endif
                                    @if($period->openingEntry)
                                        <div><i class="fas fa-door-open text-success me-1"></i>Apertura: {{ $period->openingEntry->entry_number }}</div>
                                    @endif
                                    @if(!$period->closingEntry && !$period->openingEntry)
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($latestClosure)
                                        <span class="badge bg-secondary">{{ $latestClosure->status }}</span>
                                        <small class="d-block text-muted">{{ $latestClosure->closure_type }}</small>
                                    @else
                                        <span class="text-muted small">No remitido</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($period->isOpen())
                                        <!-- Botón Cierre Mensual -->
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalClose{{ $period->id }}">
                                            <i class="fas fa-lock me-1"></i> Cerrar
                                        </button>

                                        @if($period->month == 12)
                                            <!-- Botón Cierre Anual -->
                                            <button type="button" class="btn btn-danger btn-sm ms-1" data-bs-toggle="modal" data-bs-target="#modalAnnual{{ $period->id }}">
                                                <i class="fas fa-archive me-1"></i> Cierre Anual
                                            </button>
                                        @endif

                                        <!-- Botón Enviar a Firmas -->
                                        <form method="POST" action="{{ route('accounting.period_closing.submit_approval', $period->id) }}" class="d-inline ms-1">
                                            @csrf
                                            <input type="hidden" name="closure_type" value="{{ $period->month == 12 ? 'ANNUAL' : 'MONTHLY' }}">
                                            <button type="submit" class="btn btn-outline-info btn-sm" onclick="return confirm('¿Enviar este período al flujo de firmas institucionales (DocumentApprovalService)?')">
                                                <i class="fas fa-signature me-1"></i> Firmas
                                            </button>
                                        </form>
                                    @elseif($period->isClosed() || $period->isLocked())
                                        <!-- Botón Reapertura Auditada -->
                                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalReopen{{ $period->id }}">
                                            <i class="fas fa-unlock me-1"></i> Reabrir
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            <!-- Modal Cierre Mensual -->
                            <div class="modal fade" id="modalClose{{ $period->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('accounting.period_closing.close', $period->id) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-lock text-warning me-2"></i>Cierre Mensual - Período {{ $period->period_code }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Al cerrar el período, este cambiará a estado <strong>CLOSED</strong>. No se podrán registrar nuevas operaciones en estas fechas sin regularización.</p>
                                                <div class="mb-3">
                                                    <label class="form-label">Notas o Justificación:</label>
                                                    <textarea name="notes" class="form-control" rows="3" placeholder="Ej. Cierre de operaciones comerciales mensual y conciliación conforme."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-warning"><i class="fas fa-lock me-1"></i> Confirmar Cierre</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Cierre Anual -->
                            <div class="modal fade" id="modalAnnual{{ $period->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('accounting.period_closing.annual_close', $period->id) }}">
                                            @csrf
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title"><i class="fas fa-archive me-2"></i>Cierre Anual Definitivo - Ejercicio {{ $period->fiscal_year }}</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i> Este proceso generará automáticamente:
                                                    <ul class="mb-0 mt-1">
                                                        <li>Asiento de cancelación de cuentas de resultados (6x, 7x, 8x, 9x contra 59/89).</li>
                                                        <li>Asiento de cierre patrimonial de balance general.</li>
                                                        <li>Asiento de apertura para el ejercicio fiscal siguiente ({{ $period->fiscal_year + 1 }}).</li>
                                                    </ul>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Observaciones:</label>
                                                    <textarea name="notes" class="form-control" rows="2" placeholder="Cierre contable anual regular."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-danger"><i class="fas fa-archive me-1"></i> Ejecutar Cierre Anual</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Reapertura Auditada -->
                            <div class="modal fade" id="modalReopen{{ $period->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('accounting.period_closing.reopen', $period->id) }}">
                                            @csrf
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title"><i class="fas fa-unlock me-2"></i>Reapertura Auditada - Período {{ $period->period_code }}</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>La reapertura permite modificar asientos y agregar transacciones. Quedará registrada de forma inmutable en el registro de auditoría con su usuario e IP.</p>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-danger">Motivo Obligatorio de Reapertura: *</label>
                                                    <textarea name="reason" class="form-control" rows="3" required placeholder="Especifique el motivo técnico/legal o ajuste extraordinario requerido..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-success"><i class="fas fa-unlock me-1"></i> Confirmar Reapertura</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No se encontraron períodos configurados para el ejercicio {{ $fiscalYear }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
