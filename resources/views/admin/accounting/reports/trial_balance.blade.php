@extends('admin.layout')

@section('title', 'Balance de Comprobación - Hoja de Trabajo')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800"><i class="fas fa-table text-success me-2"></i>Balance de Comprobación</h3>
            <p class="text-muted small mb-0">Hoja de trabajo contable con sumas del mayor, saldos deudores y acreedores, y verificación automática de partida doble.</p>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('accounting.journal.index') }}" class="btn btn-outline-primary btn-sm me-1">
                <i class="fas fa-book me-1"></i> Libro Diario
            </a>
            <a href="{{ route('accounting.ledger.index') }}" class="btn btn-outline-info btn-sm me-1">
                <i class="fas fa-balance-scale me-1"></i> Libro Mayor
            </a>
            <a href="{{ route('accounting.chart_of_accounts.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-sitemap me-1"></i> PCGE
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h6 class="m-0 font-weight-bold text-success me-3"><i class="fas fa-layer-group me-1"></i> Nivel de Agregación:</h6>
                <div class="btn-group btn-group-sm" role="group" id="digits-group">
                    <button type="button" class="btn btn-outline-success active" data-digits="2">2 Dígitos (Cuentas)</button>
                    <button type="button" class="btn btn-outline-success" data-digits="3">3 Dígitos (Subcuentas)</button>
                    <button type="button" class="btn btn-outline-success" data-digits="4">4 Dígitos (Divisionarias)</button>
                    <button type="button" class="btn btn-outline-success" data-digits="">Movimiento</button>
                </div>
            </div>
            <div class="btn-group">
                <button type="button" id="btn-export-pdf" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf me-1"></i> PDF (A4 Horizontal)
                </button>
                <button type="button" id="btn-export-excel" class="btn btn-success btn-sm ms-1">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </button>
            </div>
        </div>
        <div class="card-body py-3">
            <form id="filter-form" class="row g-3">
                <input type="hidden" id="filter-digits" value="2">
                <div class="col-md-3">
                    <label for="filter-period" class="form-label small font-weight-bold">Período Contable</label>
                    <select id="filter-period" class="form-select form-select-sm">
                        <option value="">-- Todos los períodos --</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" {{ $defaultPeriod && $defaultPeriod->id == $period->id ? 'selected' : '' }}>
                                {{ $period->period_code }} ({{ $period->fiscal_year }}-{{ str_pad($period->month, 2, '0', STR_PAD_LEFT) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter-cost-center" class="form-label small font-weight-bold">Centro de Costos (APE)</label>
                    <select id="filter-cost-center" class="form-select form-select-sm">
                        <option value="">-- Todas las actividades --</option>
                        @foreach ($costCenters as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter-date-from" class="form-label small font-weight-bold">Fecha Desde</label>
                    <input type="date" id="filter-date-from" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label for="filter-date-to" class="form-label small font-weight-bold">Fecha Hasta</label>
                    <input type="date" id="filter-date-to" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success btn-sm w-100 me-1">
                        <i class="fas fa-sync-alt me-1"></i> Actualizar
                    </button>
                    <button type="button" id="btn-reset" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Verification Alert Banner -->
    <div id="verification-banner" class="alert alert-success d-flex align-items-center mb-4 py-2 px-3 shadow-sm" role="alert">
        <i class="fas fa-check-circle fa-2x me-3" id="verification-icon"></i>
        <div>
            <h6 class="alert-heading mb-0 font-weight-bold" id="verification-title">VERIFICACIÓN AUTOMÁTICA: CUADRADO</h6>
            <div class="small" id="verification-desc">
                Las Sumas del Mayor (Debe y Haber) coinciden exactamente y los Saldos (Deudor y Acreedor) están perfectamente balanceados.
            </div>
        </div>
    </div>

    <!-- KPIs Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Sumas Debe (S/.)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-sum-debit">S/. 0.00</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-arrow-down fa-2x text-primary opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Sumas Haber (S/.)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-sum-credit">S/. 0.00</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-arrow-up fa-2x text-danger opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Saldo Deudor Total</div>
                            <div class="h5 mb-0 font-weight-bold text-success" id="kpi-saldo-deudor">S/. 0.00</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-plus-circle fa-2x text-success opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Saldo Acreedor Total</div>
                            <div class="h5 mb-0 font-weight-bold text-warning" id="kpi-saldo-acreedor">S/. 0.00</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-minus-circle fa-2x text-warning opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-gray-800">
                <i class="fas fa-balance-scale me-1"></i> Estructura de Cuentas y Saldos
            </h6>
            <span class="badge bg-secondary" id="accounts-badge">0 cuentas</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tb-table" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" style="width: 100px;" class="text-center align-middle">Código</th>
                            <th rowspan="2" class="align-middle">Denominación de la Cuenta</th>
                            <th colspan="2" class="text-center bg-light border-bottom font-weight-bold">SUMAS DEL MAYOR</th>
                            <th colspan="2" class="text-center bg-light border-bottom font-weight-bold">SALDOS</th>
                        </tr>
                        <tr>
                            <th style="width: 130px;" class="text-end">Debe (S/.)</th>
                            <th style="width: 130px;" class="text-end">Haber (S/.)</th>
                            <th style="width: 130px;" class="text-end">Deudor (S/.)</th>
                            <th style="width: 130px;" class="text-end">Acreedor (S/.)</th>
                        </tr>
                    </thead>
                    <tbody id="tb-body">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>
                                Calculando balance de comprobación...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-secondary font-weight-bold" id="tb-foot" style="display: none;">
                        <tr>
                            <td colspan="2" class="text-end font-weight-bold">TOTALES GENERALES (S/.):</td>
                            <td class="text-end font-weight-bold text-success" id="foot-sum-debit">0.00</td>
                            <td class="text-end font-weight-bold text-danger" id="foot-sum-credit">0.00</td>
                            <td class="text-end font-weight-bold text-success" id="foot-saldo-deudor">0.00</td>
                            <td class="text-end font-weight-bold text-danger" id="foot-saldo-acreedor">0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#digits-group button').on('click', function() {
        $('#digits-group button').removeClass('active');
        $(this).addClass('active');
        $('#filter-digits').val($(this).data('digits'));
        loadTrialBalanceData();
    });

    function loadTrialBalanceData() {
        let params = {
            period_id: $('#filter-period').val(),
            cost_center_id: $('#filter-cost-center').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            digits: $('#filter-digits').val(),
            status: 'POSTED'
        };

        $('#tb-body').html(`
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>
                    Calculando balance de comprobación...
                </td>
            </tr>
        `);

        $.getJSON("{{ route('accounting.trial_balance.data') }}", params, function(res) {
            if (!res.success) {
                $('#tb-body').html('<tr><td colspan="6" class="text-center text-danger py-4">Error al calcular balance</td></tr>');
                return;
            }

            let data = res.data;
            let totals = data.totals;
            let verification = data.verification;

            $('#kpi-sum-debit').text('S/. ' + Number(totals.total_debit).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#kpi-sum-credit').text('S/. ' + Number(totals.total_credit).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#kpi-saldo-deudor').text('S/. ' + Number(totals.saldo_deudor).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#kpi-saldo-acreedor').text('S/. ' + Number(totals.saldo_acreedor).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            $('#foot-sum-debit').text(Number(totals.total_debit).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#foot-sum-credit').text(Number(totals.total_credit).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#foot-saldo-deudor').text(Number(totals.saldo_deudor).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#foot-saldo-acreedor').text(Number(totals.saldo_acreedor).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            $('#accounts-badge').text(data.accounts.length + ' cuentas');

            if (verification.is_balanced) {
                $('#verification-banner').removeClass('alert-danger').addClass('alert-success');
                $('#verification-icon').removeClass('fa-exclamation-triangle text-danger').addClass('fa-check-circle text-success');
                $('#verification-title').text('VERIFICACIÓN AUTOMÁTICA: CUADRADO').removeClass('text-danger').addClass('text-success');
                $('#verification-desc').text(`Sumas del Mayor balanceadas (S/. ${Number(totals.total_debit).toFixed(2)}) y Saldos balanceados (S/. ${Number(totals.saldo_deudor).toFixed(2)}).`);
            } else {
                $('#verification-banner').removeClass('alert-success').addClass('alert-danger');
                $('#verification-icon').removeClass('fa-check-circle text-success').addClass('fa-exclamation-triangle text-danger');
                $('#verification-title').text('ALERTA: BALANCE DESCUADRADO').removeClass('text-success').addClass('text-danger');
                $('#verification-desc').text(`Diferencia en Sumas: S/. ${Number(verification.debit_credit_diff).toFixed(2)} | Diferencia en Saldos: S/. ${Number(verification.balances_diff).toFixed(2)}.`);
            }

            if (!data.accounts || data.accounts.length === 0) {
                $('#tb-body').html('<tr><td colspan="6" class="text-center py-4 text-muted">No se encontraron movimientos contables registrados en el período seleccionado.</td></tr>');
                $('#tb-foot').hide();
                return;
            }

            let html = '';
            data.accounts.forEach(function(acc) {
                let debitStr = acc.total_debit > 0 ? Number(acc.total_debit).toFixed(2) : '-';
                let creditStr = acc.total_credit > 0 ? Number(acc.total_credit).toFixed(2) : '-';
                let sDeudorStr = acc.saldo_deudor > 0 ? Number(acc.saldo_deudor).toFixed(2) : '-';
                let sAcreedorStr = acc.saldo_acreedor > 0 ? Number(acc.saldo_acreedor).toFixed(2) : '-';

                html += `
                    <tr>
                        <td class="text-center"><code><strong>${acc.code}</strong></code></td>
                        <td>${acc.name}</td>
                        <td class="text-end text-success">${debitStr}</td>
                        <td class="text-end text-danger">${creditStr}</td>
                        <td class="text-end font-weight-bold text-success">${sDeudorStr}</td>
                        <td class="text-end font-weight-bold text-danger">${sAcreedorStr}</td>
                    </tr>
                `;
            });

            $('#tb-body').html(html);
            $('#tb-foot').show();
        }).fail(function() {
            $('#tb-body').html('<tr><td colspan="6" class="text-center text-danger py-4">Error de comunicación con el servidor</td></tr>');
        });
    }

    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        loadTrialBalanceData();
    });

    $('#btn-reset').on('click', function() {
        $('#filter-form')[0].reset();
        $('#filter-digits').val('2');
        $('#digits-group button').removeClass('active');
        $('#digits-group button[data-digits="2"]').addClass('active');
        loadTrialBalanceData();
    });

    $('#btn-export-pdf').on('click', function() {
        let qs = $.param({
            period_id: $('#filter-period').val(),
            cost_center_id: $('#filter-cost-center').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            digits: $('#filter-digits').val(),
            status: 'POSTED'
        });
        window.open("{{ route('accounting.trial_balance.pdf') }}?" + qs, '_blank');
    });

    $('#btn-export-excel').on('click', function() {
        let qs = $.param({
            period_id: $('#filter-period').val(),
            cost_center_id: $('#filter-cost-center').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            digits: $('#filter-digits').val(),
            status: 'POSTED'
        });
        window.location.href = "{{ route('accounting.trial_balance.excel') }}?" + qs;
    });

    loadTrialBalanceData();
});
</script>
@endpush
