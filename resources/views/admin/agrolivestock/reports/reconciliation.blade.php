@extends('admin.layout')
@section('title', 'Conciliación: Rendimiento en Campo vs Facturación Oficial SUNAT')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Agropecuario y Forestal</li>
                    <li class="breadcrumb-item active" aria-current="page">Conciliación Campo vs Facturación</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-balance-scale me-2 text-warning"></i>Conciliación de Cosechas en Campo vs Facturación SUNAT
            </h1>
            <p class="text-muted small mb-0">Cruce de pesos reportados por balanza/correo electrónico en el fundo vs. peso neto liquidado y facturado oficialmente (tolerancia a mermas e impurezas)</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Imprimir Reporte
            </button>
        </div>
    </div>

    <!-- Alert Box Explaining Business Rule and Context -->
    <div class="alert alert-light border border-warning border-start-4 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-circle text-warning fs-3 me-3"></i>
            <div>
                <strong class="d-block text-dark">Control de Discrepancias de Peso (Puntos Críticos de Control):</strong>
                <span class="small text-muted">
                    Este reporte resuelve la inconsistencia advertida en reunión respecto a los reportes de peso emitidos por balanza de campo vía correo electrónico frente a las liquidaciones formales de compra y facturas electrónicas de plantas extractoras o compradores. Permite vincular el comprobante oficial y justificar mermas técnicas (humedad, impurezas y descuentos de tara).
                </span>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="{{ route('agrolivestock.reports.reconciliation') }}" method="GET" class="row g-2 align-items-end">
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
                    <label class="form-label small fw-bold text-muted mb-1">Estado de Conciliación</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- Todos los Estados --</option>
                        <option value="MATCHED" {{ $selectedStatus === 'MATCHED' ? 'selected' : '' }}>Conciliado (Sin Diferencia)</option>
                        <option value="DISCREPANCY" {{ $selectedStatus === 'DISCREPANCY' ? 'selected' : '' }}>Con Discrepancia / Merma</option>
                        <option value="PENDING_INVOICE" {{ $selectedStatus === 'PENDING_INVOICE' ? 'selected' : '' }}>Pendiente de Facturación</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Desde</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Hasta</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Peso Reportado en Campo</div>
                <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($totalFieldTonnage, 2) }} TM</div>
                <small class="text-muted">Tickets de balanza en fundo / correo</small>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Peso Facturado Oficial</div>
                <div class="h3 mb-0 fw-bold text-success mt-1">{{ number_format($totalInvoicedTonnage, 2) }} TM</div>
                <small class="text-muted">Liquidado en Facturas / Boletas SUNAT</small>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Diferencia / Merma Neta</div>
                <div class="h3 mb-0 fw-bold {{ abs($totalDiscrepancyTonnage) < 1 ? 'text-success' : 'text-danger' }} mt-1">
                    {{ number_format($totalDiscrepancyTonnage, 2) }} TM
                </div>
                <small class="text-muted">Diferencial acumulado</small>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">% Discrepancia Global</div>
                <div class="h3 mb-0 fw-bold {{ abs($globalDiscrepancyPct) < 3 ? 'text-success' : 'text-danger' }} mt-1">
                    {{ number_format($globalDiscrepancyPct, 2) }}%
                </div>
                <small class="text-muted">{{ $discrepancyCount }} con alerta | {{ $pendingCount }} pendientes</small>
            </div>
        </div>
    </div>

    <!-- Reconciliation Detailed Table -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-table me-2 text-warning"></i>Bitácora de Cosechas y Liquidaciones de Venta
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th>Fecha Cosecha</th>
                            <th>Ticket Campo</th>
                            <th>Parcela / Actividad</th>
                            <th class="text-end">Peso Campo (TM)</th>
                            <th>Comprobante SUNAT</th>
                            <th class="text-end">Peso Facturado (TM)</th>
                            <th class="text-end">Diferencia (TM)</th>
                            <th class="text-center">% Merma</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $rec)
                            <tr>
                                <td class="font-monospace fw-bold">{{ $rec->harvest_date->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-light text-dark font-monospace border">
                                        {{ $rec->field_ticket_code ?? 'S/N' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $rec->plot->name ?? 'Parcela' }}</div>
                                    <small class="text-muted">{{ $rec->plot->activity->name ?? '' }}</small>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark">
                                    {{ number_format($rec->field_reported_tonnage, 3) }} TM
                                </td>
                                <td>
                                    @if($rec->billing)
                                        <span class="badge bg-info text-white">
                                            <i class="fas fa-file-invoice me-1"></i>{{ $rec->billing->serie }}-{{ $rec->billing->numero }}
                                        </span>
                                    @elseif($rec->saleNote)
                                        <span class="badge bg-secondary text-white">
                                            <i class="fas fa-receipt me-1"></i>NV-{{ $rec->saleNote->id }}
                                        </span>
                                    @else
                                        <span class="text-muted small"><em>Sin factura vinculada</em></span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace fw-bold text-success">
                                    {{ $rec->invoiced_tonnage > 0 ? number_format($rec->invoiced_tonnage, 3) . ' TM' : '-' }}
                                </td>
                                <td class="text-end font-monospace fw-bold {{ abs($rec->weight_difference_tonnage) > 0.05 ? 'text-danger' : 'text-success' }}">
                                    {{ $rec->invoiced_tonnage > 0 ? number_format($rec->weight_difference_tonnage, 3) . ' TM' : '-' }}
                                </td>
                                <td class="text-center">
                                    @if($rec->invoiced_tonnage > 0)
                                        @if(abs($rec->discrepancy_percent) > 3)
                                            <span class="badge bg-danger text-white">{{ number_format($rec->discrepancy_percent, 1) }}%</span>
                                        @else
                                            <span class="badge bg-success text-white">{{ number_format($rec->discrepancy_percent, 1) }}%</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">{!! $rec->reconciliation_status_badge !!}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-warning btn-reconcile"
                                        data-id="{{ $rec->id }}"
                                        data-ticket="{{ $rec->field_ticket_code }}"
                                        data-field-ton="{{ $rec->field_reported_tonnage }}"
                                        data-billing-id="{{ $rec->billing_id }}"
                                        data-invoiced-ton="{{ $rec->invoiced_tonnage }}"
                                        data-notes="{{ $rec->reconciliation_notes }}"
                                        title="Vincular Comprobante / Conciliar">
                                        <i class="fas fa-link me-1"></i> Conciliar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    No se encontraron registros de rendimiento agrícola para los filtros aplicados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold text-end">
                        <tr>
                            <td colspan="3" class="text-start">TOTALES ACUMULADOS:</td>
                            <td class="font-monospace text-dark">{{ number_format($totalFieldTonnage, 3) }} TM</td>
                            <td>-</td>
                            <td class="font-monospace text-success">{{ number_format($totalInvoicedTonnage, 3) }} TM</td>
                            <td class="font-monospace {{ abs($totalDiscrepancyTonnage) > 1 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($totalDiscrepancyTonnage, 3) }} TM
                            </td>
                            <td class="text-center">{{ number_format($globalDiscrepancyPct, 1) }}%</td>
                            <td colspan="2">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Conciliar Factura -->
<div class="modal fade" id="modalReconcile" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formReconcile">
            @csrf
            <input type="hidden" id="recon_yield_log_id" name="yield_log_id">

            <div class="modal-content">
                <div class="modal-header bg-warning text-dark py-2">
                    <h5 class="modal-title fs-6 fw-bold">
                        <i class="fas fa-link me-2"></i>Conciliar Cosecha con Factura Oficial
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="alert alert-light border small mb-3">
                        <div><strong>Boleto / Ticket de Campo:</strong> <span id="recon_ticket_label" class="font-monospace fw-bold"></span></div>
                        <div><strong>Peso Reportado en Campo:</strong> <span id="recon_field_label" class="font-monospace fw-bold text-dark"></span> TM</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Comprobante de Venta SUNAT (Factura / Boleta)</label>
                        <select name="billing_id" id="recon_billing_id" class="form-select form-select-sm">
                            <option value="">-- Sin Comprobante Aún --</option>
                            @foreach($recentBillings as $b)
                                <option value="{{ $b->id }}">
                                    {{ $b->serie }}-{{ $b->numero }} ({{ $b->fecha_emision ? $b->fecha_emision->format('d/m/Y') : '' }}) - {{ $b->client->nombres ?? 'Cliente' }} (S/ {{ number_format($b->total, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Peso Facturado / Liquidado por Comprador (TM) <span class="text-danger">*</span></label>
                        <input type="number" step="0.001" name="invoiced_tonnage" id="recon_invoiced_ton" class="form-control form-control-sm fw-bold" placeholder="0.000" required>
                        <small class="text-muted">Ingrese el tonelaje neto que figura en la factura oficial o liquidación</small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Notas de Conciliación / Justificación de Merma</label>
                        <textarea name="notes" id="recon_notes" rows="2" class="form-control form-control-sm" placeholder="Ej. Descuento de 3.2% por humedad/impurezas según liquidación de planta extractora..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-semibold text-dark">
                        <i class="fas fa-check me-1"></i> Guardar Conciliación
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $(document).on('click', '.btn-reconcile', function() {
        const d = $(this).data();
        $('#recon_yield_log_id').val(d.id);
        $('#recon_ticket_label').text(d.ticket || 'S/N');
        $('#recon_field_label').text(parseFloat(d.fieldTon).toFixed(3));
        $('#recon_billing_id').val(d.billingId || '');
        $('#recon_invoiced_ton').val(d.invoicedTon > 0 ? parseFloat(d.invoicedTon).toFixed(3) : parseFloat(d.fieldTon).toFixed(3));
        $('#recon_notes').val(d.notes || '');

        $('#modalReconcile').modal('show');
    });

    $('#formReconcile').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('agrolivestock.reports.link_invoice') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(resp) {
                if (resp.success) {
                    $('#modalReconcile').modal('hide');
                    Swal.fire('Conciliado', resp.message, 'success').then(() => location.reload());
                }
            },
            error: function(err) {
                Swal.fire('Error', 'No se pudo guardar la conciliación.', 'error');
            }
        });
    });
});
</script>
@endsection
