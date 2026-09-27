@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Registro de Movimientos: Ingresos y Egresos (APE)</h1>
            <p class="text-muted small mb-0">Imputación presupuestal por actividad, fuente de financiamiento y trazabilidad con comprobantes core.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('productive_activities.cost_center.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-chart-line me-1"></i> Ver Centro de Costos
            </a>
            <button type="button" class="btn btn-primary btn-sm shadow-sm" id="btnNewTransaction">
                <i class="fas fa-plus me-1"></i> Registrar Movimiento
            </button>
        </div>
    </div>

    <!-- Quick Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-start-lg border-start-success shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Ingresos Registrados</div>
                            <div class="h4 mb-0 fw-bold text-success font-monospace" id="cardTotalIncome">S/ {{ number_format($totalIncome, 2) }}</div>
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
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Total Egresos Registrados</div>
                            <div class="h4 mb-0 fw-bold text-danger font-monospace" id="cardTotalExpense">S/ {{ number_format($totalExpense, 2) }}</div>
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
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Saldo Neto del Período</div>
                            <div class="h4 mb-0 fw-bold font-monospace {{ $netBalance >= 0 ? 'text-primary' : 'text-danger' }}" id="cardNetBalance">
                                S/ {{ number_format($netBalance, 2) }}
                            </div>
                        </div>
                        <div class="text-primary opacity-50"><i class="fas fa-balance-scale fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Table -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <div class="row g-2 align-items-center">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Actividad Productiva:</label>
                    <select class="form-select form-select-sm" id="filterActivity">
                        <option value="">-- Todas las Actividades --</option>
                        @foreach($activities as $act)
                            <option value="{{ $act->id }}" {{ $selectedActivityId == $act->id ? 'selected' : '' }}>
                                {{ $act->code }} - {{ $act->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label small text-muted mb-1">Tipo de Flujo:</label>
                    <select class="form-select form-select-sm" id="filterType">
                        <option value="">-- Todos --</option>
                        <option value="INCOME">Ingresos (+)</option>
                        <option value="EXPENSE">Egresos (-)</option>
                    </select>
                </div>

                <div class="col-lg-3 col-md-4">
                    <label class="form-label small text-muted mb-1">Fuente de Financiamiento:</label>
                    <select class="form-select form-select-sm" id="filterFundSource">
                        <option value="">-- Todas las Cuentas / Fondos --</option>
                        @foreach($fundSources as $fund)
                            <option value="{{ $fund->id }}">{{ $fund->name }} ({{ $fund->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label small text-muted mb-1">Año:</label>
                    <select class="form-select form-select-sm" id="filterYear">
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                        <option value="2026" selected>2026</option>
                        <option value="2027">2027</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label small text-muted mb-1">Mes:</label>
                    <select class="form-select form-select-sm" id="filterMonth">
                        <option value="">-- Todos los Meses --</option>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Setiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle w-100" id="transactionsTable" style="font-size: 0.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;">Código</th>
                            <th style="width: 85px;">Tipo</th>
                            <th style="width: 90px;">Fecha</th>
                            <th style="width: 70px;">Período</th>
                            <th>Actividad Productiva</th>
                            <th>Categoría Clasificadora</th>
                            <th>Concepto / Glosa</th>
                            <th>Fuente Fondos</th>
                            <th style="width: 110px;">Comprobante</th>
                            <th style="width: 110px;" class="text-end">Monto</th>
                            <th style="width: 75px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Registrar / Editar Movimiento -->
<div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title h6 mb-0" id="modalTitle"><i class="fas fa-file-invoice-dollar me-2"></i> Registrar Movimiento Económico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="transactionForm">
                @csrf
                <input type="hidden" name="id" id="trxId">

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Actividad Productiva <span class="text-danger">*</span></label>
                            <select class="form-select" name="productive_activity_id" id="trxActivity" required>
                                <option value="">-- Seleccionar Actividad --</option>
                                @foreach($activities as $act)
                                    <option value="{{ $act->id }}">{{ $act->code }} - {{ $act->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipo de Movimiento <span class="text-danger">*</span></label>
                            <select class="form-select" name="transaction_type" id="trxType" required>
                                <option value="INCOME">INGRESO (+)</option>
                                <option value="EXPENSE">EGRESO / GASTO (-)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Categoría Jerárquica <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" id="trxCategory" required>
                                <option value="">-- Seleccionar Categoría --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" data-type="{{ $cat->type }}">
                                        {{ $cat->code }} - {{ $cat->name }} {{ $cat->parent ? '(' . $cat->parent->name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fuente de Financiamiento / Banco <span class="text-danger">*</span></label>
                            <select class="form-select" name="fund_source_id" id="trxFundSource" required>
                                <option value="">-- Seleccionar Fuente --</option>
                                @foreach($fundSources as $fund)
                                    <option value="{{ $fund->id }}">{{ $fund->name }} ({{ $fund->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Monto (S/) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" min="0.01" class="form-control font-monospace fw-bold" name="amount" id="trxAmount" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fecha de Movimiento <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="transaction_date" id="trxDate" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">Mes Período</label>
                            <select class="form-select" name="period_month" id="trxPeriodMonth" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">Año</label>
                            <input type="number" class="form-control" name="period_year" id="trxPeriodYear" value="{{ date('Y') }}" required>
                        </div>
                    </div>

                    <!-- Enlace opcional a Documento Core Existente para Evitar Duplicidad -->
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-dark"><i class="fas fa-link me-1"></i> Vincular a Comprobante Core (Opcional - Evita Duplicidad)</span>
                            <span class="badge bg-secondary">Trazabilidad</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="small text-muted mb-1">Origen Core:</label>
                                <select class="form-select form-select-sm" id="coreDocType">
                                    <option value="">Ninguno (Registro Manual)</option>
                                    <option value="billings">Factura/Boleta Emitida (SUNAT)</option>
                                    <option value="sale_notes">Nota de Venta Core</option>
                                    <option value="buys">Compra / Orden de Compra</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="small text-muted mb-1">Buscar y Vincular Comprobante:</label>
                                <select class="form-select form-select-sm" id="coreDocSelect" disabled>
                                    <option value="">-- Seleccione primero el origen --</option>
                                </select>
                                <input type="hidden" name="billing_id" id="trxBillingId">
                                <input type="hidden" name="sale_note_id" id="trxSaleNoteId">
                                <input type="hidden" name="buy_id" id="trxBuyId">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tipo Comprobante</label>
                            <input type="text" class="form-control form-control-sm" name="voucher_type" id="trxVoucherType" placeholder="p. ej. FACTURA, BOLETA, DJ">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">N° Comprobante / Voucher</label>
                            <input type="text" class="form-control form-control-sm" name="voucher_number" id="trxVoucherNumber" placeholder="p. ej. F001-00234">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Beneficiario / Pagador</label>
                            <input type="text" class="form-control form-control-sm" name="beneficiary_or_payer" id="trxBeneficiary" placeholder="Nombre o Razón Social">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold">Concepto / Glosa Detallada <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="concept" id="trxConcept" rows="2" placeholder="Detalle la descripción del gasto o motivo del ingreso..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm" id="btnSaveTransaction">
                        <i class="fas fa-save me-1"></i> Guardar Movimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let table = $('#transactionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("productive_activities.transactions.get") }}',
            data: function(d) {
                d.productive_activity_id = $('#filterActivity').val();
                d.transaction_type = $('#filterType').val();
                d.fund_source_id = $('#filterFundSource').val();
                d.year = $('#filterYear').val();
                d.month = $('#filterMonth').val();
            }
        },
        columns: [
            { data: 'transaction_code', name: 'transaction_code', className: 'font-monospace small fw-bold' },
            { data: 'type_badge', name: 'transaction_type', orderable: false, searchable: false },
            { data: 'transaction_date', name: 'transaction_date', className: 'font-monospace small' },
            { data: 'period_display', name: 'period_month', searchable: false },
            { data: 'activity.name', name: 'activity.name', defaultContent: '<span class="text-muted">Sin actividad</span>' },
            { data: 'category.name', name: 'category.name', defaultContent: '<span class="text-muted">General</span>' },
            { data: 'concept', name: 'concept', className: 'small' },
            { data: 'fund_source.code', name: 'fundSource.code', defaultContent: '<span class="text-muted">S/F</span>' },
            { data: 'reference_badge', name: 'voucher_number', orderable: false, searchable: false },
            { data: 'formatted_amount', name: 'amount', className: 'text-end font-monospace' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[2, 'desc']]
    });

    $('#filterActivity, #filterType, #filterFundSource, #filterYear, #filterMonth').on('change', function() {
        table.draw();
    });

    // Abrir modal de nuevo movimiento
    $('#btnNewTransaction').on('click', function() {
        $('#transactionForm')[0].reset();
        $('#trxId').val('');
        $('#modalTitle').html('<i class="fas fa-file-invoice-dollar me-2"></i> Registrar Movimiento Económico');
        $('#coreDocSelect').prop('disabled', true).html('<option value="">-- Seleccione primero el origen --</option>');
        $('#trxBillingId, #trxSaleNoteId, #trxBuyId').val('');
        $('#transactionModal').modal('show');
    });

    // Búsqueda de comprobantes core según origen seleccionado
    $('#coreDocType').on('change', function() {
        let type = $(this).val();
        let select = $('#coreDocSelect');
        select.prop('disabled', true).html('<option value="">Cargando documentos...</option>');
        $('#trxBillingId, #trxSaleNoteId, #trxBuyId').val('');

        if (!type) {
            select.html('<option value="">-- Ninguno --</option>');
            return;
        }

        $.ajax({
            url: '{{ route("productive_activities.transactions.search_core") }}',
            data: { type: type },
            success: function(items) {
                select.prop('disabled', false).html('<option value="">-- Seleccione el comprobante --</option>');
                items.forEach(function(item) {
                    select.append('<option value="' + item.id + '" data-amount="' + item.amount + '" data-type="' + item.voucher_type + '" data-number="' + item.voucher_number + '">' + item.text + '</option>');
                });
            }
        });
    });

    // Al seleccionar documento core, autocompletar monto y comprobante
    $('#coreDocSelect').on('change', function() {
        let opt = $(this).find(':selected');
        let type = $('#coreDocType').val();
        let id = $(this).val();

        if (id) {
            $('#trxAmount').val(opt.data('amount'));
            $('#trxVoucherType').val(opt.data('type'));
            $('#trxVoucherNumber').val(opt.data('number'));

            if (type === 'billings') $('#trxBillingId').val(id);
            if (type === 'sale_notes') $('#trxSaleNoteId').val(id);
            if (type === 'buys') $('#trxBuyId').val(id);
        }
    });

    // Guardar transacción
    $('#transactionForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSaveTransaction');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

        let id = $('#trxId').val();
        let url = id ? '{{ url("productive-activities/transactions") }}/' + id : '{{ route("productive_activities.transactions.store") }}';
        let method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function(resp) {
                $('#transactionModal').modal('hide');
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Movimiento');
                Swal.fire('¡Éxito!', resp.message, 'success');
                table.ajax.reload(null, false);
            },
            error: function(err) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Movimiento');
                let msg = err.responseJSON ? err.responseJSON.message : 'Error al guardar el movimiento.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // Editar transacción
    $(document).on('click', '.btn-edit-trx', function() {
        let trx = $(this).data('trx');
        $('#trxId').val(trx.id);
        $('#modalTitle').html('<i class="fas fa-edit me-2"></i> Editar Movimiento ' + trx.transaction_code);
        $('#trxActivity').val(trx.productive_activity_id);
        $('#trxType').val(trx.transaction_type);
        $('#trxCategory').val(trx.category_id);
        $('#trxFundSource').val(trx.fund_source_id);
        $('#trxAmount').val(trx.amount);
        $('#trxDate').val(trx.transaction_date.substring(0, 10));
        $('#trxPeriodMonth').val(trx.period_month);
        $('#trxPeriodYear').val(trx.period_year);
        $('#trxVoucherType').val(trx.voucher_type);
        $('#trxVoucherNumber').val(trx.voucher_number);
        $('#trxBeneficiary').val(trx.beneficiary_or_payer);
        $('#trxConcept').val(trx.concept);
        $('#trxBillingId').val(trx.billing_id || '');
        $('#trxSaleNoteId').val(trx.sale_note_id || '');
        $('#trxBuyId').val(trx.buy_id || '');
        $('#transactionModal').modal('show');
    });

    // Anular transacción
    $(document).on('click', '.btn-delete-trx', function() {
        let id = $(this).data('id');
        let code = $(this).data('code');

        Swal.fire({
            title: '¿Anular Movimiento?',
            text: 'Se anulará el movimiento ' + code + ' y se revertirá el saldo de la fuente de fondos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e81500',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("productive_activities.transactions.delete") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id
                    },
                    success: function(resp) {
                        Swal.fire('Anulado', resp.message, 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        Swal.fire('Error', 'No se pudo anular el movimiento.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
