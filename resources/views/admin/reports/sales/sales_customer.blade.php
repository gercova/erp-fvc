@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-xl px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title">
                        <div class="page-header-icon"><i data-feather="users"></i></div>
                        Reporte de Ventas por Cliente
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
                    <label class="small mb-1">Cliente</label>
                    <select id="id_cliente" name="id_cliente" class="form-select select2">
                        <option value="">Todos los clientes</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->nro_documento }} - {{ $client->nombres }}</option>
                        @endforeach
                    </select>
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
                    <div class="small text-muted">Ventas Totales</div>
                    <div class="h3" id="kpi-total-ventas">{{ $signo }} 0.00</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-start-lg border-start-success h-100">
                <div class="card-body">
                    <div class="small text-muted">Clientes / Compras</div>
                    <div class="h3" id="kpi-total-transacciones">0</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-start-lg border-start-info h-100">
                <div class="card-body">
                    <div class="small text-muted">Ticket Promedio Global</div>
                    <div class="h3" id="kpi-ticket-promedio">{{ $signo }} 0.00</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="customerSalesTable" width="100%">
                    <thead class="table-light">
                        <tr>
                            <th>Nro. Documento</th>
                            <th>Cliente</th>
                            <th class="text-center">Cant. Compras</th>
                            <th class="text-end">Base Gravada</th>
                            <th class="text-end">IGV</th>
                            <th class="text-end">Total Ventas</th>
                            <th class="text-end">Ticket Promedio</th>
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
    const table = $('#customerSalesTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'print']
    });

    function loadData() {
        const start = document.getElementById("start_date").value;
        const end = document.getElementById("end_date").value;
        const client = document.getElementById("id_cliente").value;
        const warehouse = document.getElementById("id_almacen").value;

        fetch(`{{ route('report.sales.customer.data') }}?start_date=${start}&end_date=${end}&id_cliente=${client}&id_almacen=${warehouse}`)
            .then(res => res.json())
            .then(data => {
                const signo = data.signo || "S/";
                table.clear();

                data.sales.forEach(row => {
                    table.row.add([
                        row.cliente_documento || '-',
                        row.cliente || 'CLIENTE GENERAL',
                        `<span class="badge bg-secondary">${row.cantidad_compras}</span>`,
                        `${signo} ${parseFloat(row.subtotal).toFixed(2)}`,
                        `${signo} ${parseFloat(row.igv).toFixed(2)}`,
                        `<strong>${signo} ${parseFloat(row.total_ventas).toFixed(2)}</strong>`,
                        `${signo} ${parseFloat(row.ticket_promedio).toFixed(2)}`
                    ]);
                });
                table.draw();

                document.getElementById('kpi-total-ventas').innerText = `${signo} ${parseFloat(data.total_ventas || 0).toFixed(2)}`;
                document.getElementById('kpi-total-transacciones').innerText = data.total_transacciones || 0;
                const avg = data.total_transacciones > 0 ? (data.total_ventas / data.total_transacciones) : 0;
                document.getElementById('kpi-ticket-promedio').innerText = `${signo} ${avg.toFixed(2)}`;
            });
    }

    document.getElementById("formReport").addEventListener("submit", function(e) {
        e.preventDefault();
        loadData();
    });

    document.getElementById("btnExportPdf").addEventListener("click", function() {
        const start = document.getElementById("start_date").value;
        const end = document.getElementById("end_date").value;
        const client = document.getElementById("id_cliente").value;
        const warehouse = document.getElementById("id_almacen").value;
        window.location.href = `{{ route('report.sales.customer.pdf') }}?start_date=${start}&end_date=${end}&id_cliente=${client}&id_almacen=${warehouse}`;
    });

    document.getElementById("btnExportExcel").addEventListener("click", function() {
        const start = document.getElementById("start_date").value;
        const end = document.getElementById("end_date").value;
        const client = document.getElementById("id_cliente").value;
        const warehouse = document.getElementById("id_almacen").value;
        window.location.href = `{{ route('report.sales.customer.excel') }}?start_date=${start}&end_date=${end}&id_cliente=${client}&id_almacen=${warehouse}`;
    });

    loadData();
});
</script>
@endsection
