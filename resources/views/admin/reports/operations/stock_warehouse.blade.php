@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-xl px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title">
                        <div class="page-header-icon"><i data-feather="archive"></i></div>
                        Reporte de Stock por Almacén
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
                    <label class="small mb-1">Almacén</label>
                    <select id="id_almacen" name="id_almacen" class="form-select">
                        <option value="">Todos los almacenes</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small mb-1">Categoría</label>
                    <select id="id_categoria" name="id_categoria" class="form-select">
                        <option value="">Todas las categorías</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small mb-1">Buscar Producto</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Nombre o código...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ri-search-line me-1"></i> Filtrar
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
                    <div class="small text-muted">Stock Total de Unidades</div>
                    <div class="h3" id="kpi-total-stock">0.00</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-start-lg border-start-success h-100">
                <div class="card-body">
                    <div class="small text-muted">Valorización Total de Inventario</div>
                    <div class="h3" id="kpi-total-valoracion">{{ $signo }} 0.00</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-start-lg border-start-info h-100">
                <div class="card-body">
                    <div class="small text-muted">Productos Físicos Listados</div>
                    <div class="h3" id="kpi-total-productos">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="stockWarehouseTable" width="100%">
                    <thead class="table-light">
                        <tr>
                            <th>Almacén</th>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Unidad</th>
                            <th class="text-end">Stock Actual</th>
                            <th class="text-end">Stock Mín.</th>
                            <th class="text-end">Precio Compra</th>
                            <th class="text-end">Precio Venta</th>
                            <th class="text-end">Valor Inventario</th>
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
    const table = $('#stockWarehouseTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'print']
    });

    function loadData() {
        const warehouse = document.getElementById("id_almacen").value;
        const category = document.getElementById("id_categoria").value;
        const search = encodeURIComponent(document.getElementById("search").value);

        fetch(`{{ route('report.operations.stock_warehouse.data') }}?id_almacen=${warehouse}&idcategoria=${category}&search=${search}`)
            .then(res => res.json())
            .then(data => {
                const signo = data.signo || "S/";
                table.clear();

                data.items.forEach(row => {
                    const isMin = parseFloat(row.stock_actual) <= parseFloat(row.stock_minimo);
                    const stockBadge = isMin 
                        ? `<span class="badge bg-danger">${parseFloat(row.stock_actual).toFixed(2)}</span>`
                        : `<span class="badge bg-success">${parseFloat(row.stock_actual).toFixed(2)}</span>`;

                    table.row.add([
                        row.almacen,
                        `<code>${row.codigo}</code>`,
                        row.producto,
                        row.categoria,
                        row.unidad,
                        stockBadge,
                        parseFloat(row.stock_minimo).toFixed(2),
                        `${signo} ${parseFloat(row.precio_compra).toFixed(2)}`,
                        `${signo} ${parseFloat(row.precio_venta).toFixed(2)}`,
                        `<strong>${signo} ${parseFloat(row.valor_inventario).toFixed(2)}</strong>`
                    ]);
                });
                table.draw();

                document.getElementById('kpi-total-stock').innerText = parseFloat(data.total_stock || 0).toFixed(2);
                document.getElementById('kpi-total-valoracion').innerText = `${signo} ${parseFloat(data.total_valoracion || 0).toFixed(2)}`;
                document.getElementById('kpi-total-productos').innerText = data.total_productos || 0;
            });
    }

    document.getElementById("formReport").addEventListener("submit", function(e) {
        e.preventDefault();
        loadData();
    });

    document.getElementById("btnExportPdf").addEventListener("click", function() {
        const warehouse = document.getElementById("id_almacen").value;
        const category = document.getElementById("id_categoria").value;
        const search = encodeURIComponent(document.getElementById("search").value);
        window.location.href = `{{ route('report.operations.stock_warehouse.pdf') }}?id_almacen=${warehouse}&idcategoria=${category}&search=${search}`;
    });

    document.getElementById("btnExportExcel").addEventListener("click", function() {
        const warehouse = document.getElementById("id_almacen").value;
        const category = document.getElementById("id_categoria").value;
        const search = encodeURIComponent(document.getElementById("search").value);
        window.location.href = `{{ route('report.operations.stock_warehouse.excel') }}?id_almacen=${warehouse}&idcategoria=${category}&search=${search}`;
    });

    loadData();
});
</script>
@endsection
