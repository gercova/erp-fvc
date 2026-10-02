@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-xl px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title">
                        <div class="page-header-icon"><i data-feather="check-circle"></i></div>
                        Reporte de Comprobantes Emitidos y Estados
                    </h1>
                </div>
                <div class="col-12 col-xl-auto mb-3">
                    <button type="button" id="btnExportPdf" class="btn btn-danger btn-sm me-2">
                        <i class="ri-file-pdf-line me-1"></i> Exportar PDF
                    </button>
                    <button type="button" id="btnExportExcel" class="btn btn-success btn-sm">
                        <i class="ri-file-excel-line me-1"></i> Exportar Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-xl px-4 mt-4">
    <div class="card mb-4">
        <div class="card-body">
            <form id="formReport" class="row gx-3 align-items-end">
                <div class="col-md-4">
                    <label class="small mb-1">Rango de Fechas</label>
                    <div class="input-group">
                        <input type="date" id="start_date" name="start_date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}">
                        <span class="input-group-text">a</span>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="{{ now()->endOfMonth()->toDateString() }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="small mb-1">Almacén</label>
                    <select id="id_almacen" name="id_almacen" class="form-select">
                        <option value="">Todos los almacenes</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small mb-1">Estado</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="vigente">Vigentes</option>
                        <option value="anulado">Anulados</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ri-search-line me-1"></i> Consultar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card border-start-lg border-start-primary h-100">
                <div class="card-body">
                    <div class="small text-muted">Total Emitido Vigente</div>
                    <div class="h3" id="kpi-total-emitido">{{ $signo }} 0.00</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-start-lg border-start-danger h-100">
                <div class="card-body">
                    <div class="small text-muted">Total Anulado</div>
                    <div class="h3" id="kpi-total-anulado">{{ $signo }} 0.00</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-start-lg border-start-info h-100">
                <div class="card-body">
                    <div class="small text-muted">Cantidad Total Comprobantes</div>
                    <div class="h3" id="kpi-cantidad-total">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="vouchersTable" width="100%">
                    <thead class="table-light">
                        <tr>
                            <th>Origen</th>
                            <th>Tipo Comprobante</th>
                            <th>Nro. Documento</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Almacén</th>
                            <th class="text-end">Base Gravada</th>
                            <th class="text-end">IGV</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Inserted dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const table = $('#vouchersTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'print']
    });

    function loadData() {
        const start = document.getElementById("start_date").value;
        const end = document.getElementById("end_date").value;
        const warehouse = document.getElementById("id_almacen").value;
        const status = document.getElementById("status").value;

        fetch(`{{ route('report.operations.issued_vouchers.data') }}?start_date=${start}&end_date=${end}&id_almacen=${warehouse}&status=${status}`)
            .then(res => res.json())
            .then(data => {
                const signo = data.signo || "S/";
                table.clear();

                data.vouchers.forEach(row => {
                    let statusBadge = '<span class="badge bg-success">Vigente</span>';
                    if (row.anulado) {
                        statusBadge = '<span class="badge bg-danger">Anulado</span>';
                    } else if (row.estado === 'Canjeado') {
                        statusBadge = '<span class="badge bg-warning text-dark">Canjeado</span>';
                    }

                    const originBadge = row.origin === 'billing' 
                        ? '<span class="badge bg-primary">Comprobante</span>' 
                        : '<span class="badge bg-secondary">Nota Venta</span>';

                    table.row.add([
                        originBadge,
                        row.tipo_comprobante,
                        `<strong>${row.numero_documento}</strong>`,
                        row.fecha_emision,
                        `${row.cliente_documento ? row.cliente_documento + ' - ' : ''}${row.cliente}`,
                        row.almacen,
                        `${signo} ${parseFloat(row.subtotal).toFixed(2)}`,
                        `${signo} ${parseFloat(row.igv).toFixed(2)}`,
                        `<strong>${signo} ${parseFloat(row.total).toFixed(2)}</strong>`,
                        statusBadge
                    ]);
                });
                table.draw();

                document.getElementById('kpi-total-emitido').innerText = `${signo} ${parseFloat(data.total_emitido || 0).toFixed(2)}`;
                document.getElementById('kpi-total-anulado').innerText = `${signo} ${parseFloat(data.total_anulado || 0).toFixed(2)}`;
                document.getElementById('kpi-cantidad-total').innerText = data.cantidad_total || 0;
            });
    }

    document.getElementById("formReport").addEventListener("submit", function(e) {
        e.preventDefault();
        loadData();
    });

    document.getElementById("btnExportPdf").addEventListener("click", function() {
        const start = document.getElementById("start_date").value;
        const end = document.getElementById("end_date").value;
        const warehouse = document.getElementById("id_almacen").value;
        const status = document.getElementById("status").value;
        window.location.href = `{{ route('report.operations.issued_vouchers.pdf') }}?start_date=${start}&end_date=${end}&id_almacen=${warehouse}&status=${status}`;
    });

    document.getElementById("btnExportExcel").addEventListener("click", function() {
        const start = document.getElementById("start_date").value;
        const end = document.getElementById("end_date").value;
        const warehouse = document.getElementById("id_almacen").value;
        const status = document.getElementById("status").value;
        window.location.href = `{{ route('report.operations.issued_vouchers.excel') }}?start_date=${start}&end_date=${end}&id_almacen=${warehouse}&status=${status}`;
    });

    loadData();
});
</script>
@endsection
