@extends('admin.layout')

@section('title', 'Libro Diario - Formato 5.1')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800"><i class="fas fa-book text-primary me-2"></i>Libro Diario (Formato SUNAT 5.1)</h3>
            <p class="text-muted small mb-0">Registro cronológico y secuencial de todas las operaciones contables asentadas en el ERP-FVC.</p>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('accounting.ledger.index') }}" class="btn btn-outline-info btn-sm me-1">
                <i class="fas fa-balance-scale me-1"></i> Libro Mayor
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
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter me-1"></i> Filtros de Búsqueda</h6>
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
                                {{ $period->period_code }} ({{ $period->fiscal_year }}-{{ str_pad($period->month, 2, '0', STR_PAD_LEFT) }}) - {{ $period->status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter-date-from" class="form-label small font-weight-bold">Desde</label>
                    <input type="date" id="filter-date-from" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label for="filter-date-to" class="form-label small font-weight-bold">Hasta</label>
                    <input type="date" id="filter-date-to" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label for="filter-voucher-type" class="form-label small font-weight-bold">Tipo de Asiento</label>
                    <select id="filter-voucher-type" class="form-select form-select-sm">
                        <option value="">-- Todos los tipos --</option>
                        @foreach ($voucherTypes as $vt)
                            <option value="{{ $vt->value }}">{{ $vt->value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100 me-1">
                        <i class="fas fa-search me-1"></i> Filtrar
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
            <div class="card border-left-primary shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Debe (S/.)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-debit">S/. 0.00</div>
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
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Haber (S/.)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-credit">S/. 0.00</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-arrow-up fa-2x text-danger opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Asientos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-count">0</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-list-ol fa-2x text-info opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow-sm h-100 py-2" id="kpi-status-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Estado de Partida Doble</div>
                            <div class="h5 mb-0 font-weight-bold text-success" id="kpi-status">CUADRADO</div>
                        </div>
                        <div class="col-auto" id="kpi-status-icon"><i class="fas fa-check-circle fa-2x text-success"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-gray-800">
                <i class="fas fa-table me-1"></i> Asientos Contables Registrados
            </h6>
            <span class="badge bg-secondary" id="records-badge">0 registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="journal-table" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">N°</th>
                            <th style="width: 100px;">Fecha</th>
                            <th style="width: 140px;">N° Asiento</th>
                            <th>Cuenta Contable</th>
                            <th>Glosa / Detalle</th>
                            <th style="width: 120px;" class="text-end">Debe (S/.)</th>
                            <th style="width: 120px;" class="text-end">Haber (S/.)</th>
                        </tr>
                    </thead>
                    <tbody id="journal-body">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>
                                Cargando información del Libro Diario...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-secondary font-weight-bold" id="journal-foot" style="display: none;">
                        <tr>
                            <td colspan="5" class="text-end font-weight-bold">TOTALES GENERALES (S/.):</td>
                            <td class="text-end font-weight-bold text-success" id="foot-debit">0.00</td>
                            <td class="text-end font-weight-bold text-danger" id="foot-credit">0.00</td>
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
    function loadJournalData() {
        let params = {
            period_id: $('#filter-period').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            voucher_type: $('#filter-voucher-type').val(),
            status: 'POSTED'
        };

        $('#journal-body').html(`
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>
                    Cargando información del Libro Diario...
                </td>
            </tr>
        `);

        $.getJSON("{{ route('accounting.journal.data') }}", params, function(res) {
            if (!res.success) {
                $('#journal-body').html('<tr><td colspan="7" class="text-center text-danger py-4">Error al cargar datos</td></tr>');
                return;
            }

            let data = res.data;
            $('#kpi-debit').text('S/. ' + Number(data.grand_total_debit).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#kpi-credit').text('S/. ' + Number(data.grand_total_credit).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#kpi-count').text(data.entries_count);

            if (data.is_balanced) {
                $('#kpi-status-card').removeClass('border-left-danger').addClass('border-left-success');
                $('#kpi-status').text('CUADRADO').removeClass('text-danger').addClass('text-success');
                $('#kpi-status-icon').html('<i class="fas fa-check-circle fa-2x text-success"></i>');
            } else {
                $('#kpi-status-card').removeClass('border-left-success').addClass('border-left-danger');
                $('#kpi-status').text('DESCUADRADO').removeClass('text-success').addClass('text-danger');
                $('#kpi-status-icon').html('<i class="fas fa-exclamation-triangle fa-2x text-danger"></i>');
            }

            $('#foot-debit').text(Number(data.grand_total_debit).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#foot-credit').text(Number(data.grand_total_credit).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#records-badge').text(data.entries_count + ' asientos');

            if (!data.entries || data.entries.length === 0) {
                $('#journal-body').html('<tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron asientos contables registrados con los filtros seleccionados.</td></tr>');
                $('#journal-foot').hide();
                return;
            }

            let html = '';
            data.entries.forEach(function(entry) {
                html += `
                    <tr class="table-light font-weight-bold" style="border-top: 2px solid #cbd5e1;">
                        <td class="text-center"><span class="badge bg-primary text-white">${entry.seq_number}</span></td>
                        <td><i class="far fa-calendar-alt text-muted me-1"></i>${entry.entry_date}</td>
                        <td><strong>${entry.entry_number}</strong></td>
                        <td colspan="2">
                            <span class="badge bg-secondary me-1">${entry.entry_type}</span>
                            <strong>${entry.concept}</strong>
                            ${entry.period_code !== '-' ? `<small class="text-muted ms-1">(${entry.period_code})</small>` : ''}
                        </td>
                        <td class="text-end font-weight-bold text-success">${Number(entry.total_debit).toFixed(2)}</td>
                        <td class="text-end font-weight-bold text-danger">${Number(entry.total_credit).toFixed(2)}</td>
                    </tr>
                `;

                entry.lines.forEach(function(line) {
                    let debitStr = line.debit > 0 ? Number(line.debit).toFixed(2) : '-';
                    let creditStr = line.credit > 0 ? Number(line.credit).toFixed(2) : '-';
                    let thirdPartyBadge = line.third_party !== '-' ? `<span class="badge bg-light text-dark border ms-1"><i class="fas fa-user-tag me-1"></i>${line.third_party}</span>` : '';
                    let ccBadge = line.cost_center !== '-' ? `<span class="badge bg-light text-primary border ms-1"><i class="fas fa-project-diagram me-1"></i>${line.cost_center}</span>` : '';
                    let docRef = line.document_reference !== '-' ? `<small class="text-muted d-block"><i class="fas fa-receipt me-1"></i>Ref: ${line.document_reference}</small>` : '';

                    html += `
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="text-muted small">${docRef}</td>
                            <td>
                                <code><strong>${line.account_code}</strong></code>
                                <span class="ms-1">${line.account_name}</span>
                            </td>
                            <td>
                                <span>${line.glosa}</span>
                                ${thirdPartyBadge}
                                ${ccBadge}
                            </td>
                            <td class="text-end text-success">${debitStr}</td>
                            <td class="text-end text-danger">${creditStr}</td>
                        </tr>
                    `;
                });
            });

            $('#journal-body').html(html);
            $('#journal-foot').show();
        }).fail(function() {
            $('#journal-body').html('<tr><td colspan="7" class="text-center text-danger py-4">Error al consultar el servidor</td></tr>');
        });
    }

    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        loadJournalData();
    });

    $('#btn-reset').on('click', function() {
        $('#filter-form')[0].reset();
        loadJournalData();
    });

    $('#btn-export-pdf').on('click', function() {
        let qs = $.param({
            period_id: $('#filter-period').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            voucher_type: $('#filter-voucher-type').val(),
            status: 'POSTED'
        });
        window.open("{{ route('accounting.journal.pdf') }}?" + qs, '_blank');
    });

    $('#btn-export-excel').on('click', function() {
        let qs = $.param({
            period_id: $('#filter-period').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            voucher_type: $('#filter-voucher-type').val(),
            status: 'POSTED'
        });
        window.location.href = "{{ route('accounting.journal.excel') }}?" + qs;
    });

    loadJournalData();
});
</script>
@endpush
