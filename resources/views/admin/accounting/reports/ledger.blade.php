@extends('admin.layout')

@section('title', 'Libro Mayor - Formato 6.1')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800"><i class="fas fa-balance-scale text-info me-2"></i>Libro Mayor (Formato SUNAT 6.1)</h3>
            <p class="text-muted small mb-0">Detalle de movimientos, saldo inicial y saldo acumulado por cuenta contable o rango de cuentas.</p>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('accounting.journal.index') }}" class="btn btn-outline-primary btn-sm me-1">
                <i class="fas fa-book me-1"></i> Libro Diario
            </a>
            <a href="{{ route('accounting.trial_balance.index') }}" class="btn btn-outline-success btn-sm me-1">
                <i class="fas fa-table me-1"></i> Balance de Comprobación
            </a>
            <a href="{{ route('accounting.chart_of_accounts.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-sitemap me-1"></i> PCGE
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-filter me-1"></i> Filtros de Consulta</h6>
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
                    <label for="filter-account" class="form-label small font-weight-bold">Cuenta Contable Específica</label>
                    <select id="filter-account" class="form-select form-select-sm">
                        <option value="">-- Todas las cuentas con movimiento --</option>
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter-account-from" class="form-label small font-weight-bold">Cuenta Desde</label>
                    <input type="text" id="filter-account-from" class="form-control form-control-sm" placeholder="Ej. 10">
                </div>
                <div class="col-md-2">
                    <label for="filter-account-to" class="form-label small font-weight-bold">Cuenta Hasta</label>
                    <input type="text" id="filter-account-to" class="form-control form-control-sm" placeholder="Ej. 19">
                </div>
                <div class="col-md-2">
                    <label for="filter-cost-center" class="form-label small font-weight-bold">Centro de Costos (APE)</label>
                    <select id="filter-cost-center" class="form-select form-select-sm">
                        <option value="">-- Todas --</option>
                        @foreach ($costCenters as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filter-third-party" class="form-label small font-weight-bold">RUC / DNI Tercero</label>
                    <input type="text" id="filter-third-party" class="form-control form-control-sm" placeholder="Documento tercero">
                </div>
                <div class="col-md-3">
                    <label for="filter-date-from" class="form-label small font-weight-bold">Fecha Desde</label>
                    <input type="date" id="filter-date-from" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label for="filter-date-to" class="form-label small font-weight-bold">Fecha Hasta</label>
                    <input type="date" id="filter-date-to" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-info text-white btn-sm w-100 me-1">
                        <i class="fas fa-search me-1"></i> Consultar Mayor
                    </button>
                    <button type="button" id="btn-reset" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-secondary shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Saldo Inicial Acumulado</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-opening">S/. 0.00</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-calendar-check fa-2x text-secondary opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Debe Período</div>
                            <div class="h5 mb-0 font-weight-bold text-success" id="kpi-debit">S/. 0.00</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-arrow-down fa-2x text-success opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Haber Período</div>
                            <div class="h5 mb-0 font-weight-bold text-danger" id="kpi-credit">S/. 0.00</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-arrow-up fa-2x text-danger opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-primary shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Saldo Final Neto</div>
                            <div class="h5 mb-0 font-weight-bold text-primary" id="kpi-final">S/. 0.00</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-balance-scale-right fa-2x text-primary opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Content Container -->
    <div id="ledger-container">
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>
                Cargando movimientos del Libro Mayor...
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function loadLedgerData() {
        let params = {
            period_id: $('#filter-period').val(),
            account_id: $('#filter-account').val(),
            account_from: $('#filter-account-from').val(),
            account_to: $('#filter-account-to').val(),
            cost_center_id: $('#filter-cost-center').val(),
            third_party_document: $('#filter-third-party').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            status: 'POSTED'
        };

        $('#ledger-container').html(`
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center py-5 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>
                    Cargando movimientos del Libro Mayor...
                </div>
            </div>
        `);

        $.getJSON("{{ route('accounting.ledger.data') }}", params, function(res) {
            if (!res.success) {
                $('#ledger-container').html('<div class="alert alert-danger">Error al cargar datos del Libro Mayor.</div>');
                return;
            }

            let data = res.data;
            let sum = data.summary;
            $('#kpi-opening').text('S/. ' + Number(sum.opening_balance).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#kpi-debit').text('S/. ' + Number(sum.period_debit).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#kpi-credit').text('S/. ' + Number(sum.period_credit).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#kpi-final').text('S/. ' + Number(sum.final_balance).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            let accounts = data.accounts;
            let keys = Object.keys(accounts);

            if (keys.length === 0) {
                $('#ledger-container').html(`
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-3x mb-3 text-gray-300 d-block"></i>
                            No se encontraron movimientos registrados en el Libro Mayor para los filtros indicados.
                        </div>
                    </div>
                `);
                return;
            }

            let html = '';
            keys.forEach(function(code) {
                let acc = accounts[code];
                html += `
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-primary font-monospace fs-6">${acc.account_code}</strong>
                                <span class="ms-2 font-weight-bold text-dark">${acc.account_name}</span>
                                <span class="badge bg-secondary ms-2">${acc.nature}</span>
                            </div>
                            <div>
                                <span class="small text-muted me-2">Saldo Final:</span>
                                <strong class="${acc.final_balance >= 0 ? 'text-success' : 'text-danger'} fs-6">
                                    S/. ${Number(acc.final_balance).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                </strong>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 0.82rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 90px;">Fecha</th>
                                            <th style="width: 140px;">N° Asiento</th>
                                            <th>Glosa / Concepto</th>
                                            <th style="width: 160px;">Tercero / Centro Costo</th>
                                            <th style="width: 120px;">Doc. Ref.</th>
                                            <th style="width: 110px;" class="text-end">Debe (S/.)</th>
                                            <th style="width: 110px;" class="text-end">Haber (S/.)</th>
                                            <th style="width: 120px;" class="text-end">Saldo Acumulado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="table-success font-weight-bold">
                                            <td class="text-center">-</td>
                                            <td class="text-center">-</td>
                                            <td colspan="3"><strong>SALDO INICIAL ACUMULADO</strong></td>
                                            <td class="text-end text-success">${acc.opening_debit > 0 ? Number(acc.opening_debit).toFixed(2) : '-'}</td>
                                            <td class="text-end text-danger">${acc.opening_credit > 0 ? Number(acc.opening_credit).toFixed(2) : '-'}</td>
                                            <td class="text-end font-weight-bold">S/. ${Number(acc.opening_balance).toFixed(2)}</td>
                                        </tr>
                `;

                if (acc.transactions.length === 0) {
                    html += `<tr><td colspan="8" class="text-center text-muted py-2">Sin movimientos en el período seleccionado.</td></tr>`;
                } else {
                    acc.transactions.forEach(function(tx) {
                        let debitStr = tx.debit > 0 ? Number(tx.debit).toFixed(2) : '-';
                        let creditStr = tx.credit > 0 ? Number(tx.credit).toFixed(2) : '-';
                        let docRef = tx.document_reference !== '-' ? `<small class="text-muted"><i class="fas fa-receipt me-1"></i>${tx.document_reference}</small>` : '';

                        html += `
                            <tr>
                                <td>${tx.entry_date}</td>
                                <td><strong>${tx.entry_number}</strong></td>
                                <td>${tx.concept}</td>
                                <td><small>${tx.third_party !== '-' ? tx.third_party : (tx.cost_center !== '-' ? tx.cost_center : '-')}</small></td>
                                <td>${docRef}</td>
                                <td class="text-end text-success">${debitStr}</td>
                                <td class="text-end text-danger">${creditStr}</td>
                                <td class="text-end font-weight-bold">${Number(tx.running_balance).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                }

                html += `
                                    </tbody>
                                    <tfoot class="table-secondary font-weight-bold">
                                        <tr>
                                            <td colspan="5" class="text-end">TOTALES DEL PERÍODO Y SALDO FINAL:</td>
                                            <td class="text-end text-success">S/. ${Number(acc.period_debit).toFixed(2)}</td>
                                            <td class="text-end text-danger">S/. ${Number(acc.period_credit).toFixed(2)}</td>
                                            <td class="text-end text-primary font-weight-bold" style="background: #e0f2fe;">
                                                S/. ${Number(acc.final_balance).toFixed(2)}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            });

            $('#ledger-container').html(html);
        }).fail(function() {
            $('#ledger-container').html('<div class="alert alert-danger">Error de comunicación con el servidor.</div>');
        });
    }

    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        loadLedgerData();
    });

    $('#btn-reset').on('click', function() {
        $('#filter-form')[0].reset();
        loadLedgerData();
    });

    $('#btn-export-pdf').on('click', function() {
        let qs = $.param({
            period_id: $('#filter-period').val(),
            account_id: $('#filter-account').val(),
            account_from: $('#filter-account-from').val(),
            account_to: $('#filter-account-to').val(),
            cost_center_id: $('#filter-cost-center').val(),
            third_party_document: $('#filter-third-party').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            status: 'POSTED'
        });
        window.open("{{ route('accounting.ledger.pdf') }}?" + qs, '_blank');
    });

    $('#btn-export-excel').on('click', function() {
        let qs = $.param({
            period_id: $('#filter-period').val(),
            account_id: $('#filter-account').val(),
            account_from: $('#filter-account-from').val(),
            account_to: $('#filter-account-to').val(),
            cost_center_id: $('#filter-cost-center').val(),
            third_party_document: $('#filter-third-party').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            status: 'POSTED'
        });
        window.location.href = "{{ route('accounting.ledger.excel') }}?" + qs;
    });

    loadLedgerData();
});
</script>
@endpush
