@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-xl px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title">
                        <div class="page-header-icon"><i data-feather="inbox"></i></div>
                        Reporte de Caja Diaria y Arqueos
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
                    <label class="small mb-1">Caja Registradora</label>
                    <select id="idcaja" name="idcaja" class="form-select">
                        <option value="">Todas las cajas</option>
                        @foreach ($cashes as $cash)
                            <option value="{{ $cash->id }}">{{ $cash->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small mb-1">Cajero / Usuario</label>
                    <select id="idusuario" name="idusuario" class="form-select">
                        <option value="">Todos los usuarios</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->nombres }}</option>
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
        <div class="col-lg-3 col-md-6">
            <div class="card border-start-lg border-start-primary h-100">
                <div class="card-body">
                    <div class="small text-muted">Fondo Inicial Total</div>
                    <div class="h3" id="kpi-total-inicial">{{ $signo }} 0.00</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-start-lg border-start-success h-100">
                <div class="card-body">
                    <div class="small text-muted">Ventas / Recaudación</div>
                    <div class="h3" id="kpi-total-ventas">{{ $signo }} 0.00</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-start-lg border-start-info h-100">
                <div class="card-body">
                    <div class="small text-muted">Monto Contado en Cierres</div>
                    <div class="h3" id="kpi-total-contado">{{ $signo }} 0.00</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-start-lg border-start-warning h-100">
                <div class="card-body">
                    <div class="small text-muted">Diferencia Acumulada</div>
                    <div class="h3" id="kpi-total-diferencia">{{ $signo }} 0.00</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="dailyCashTable" width="100%">
                    <thead class="table-light">
                        <tr>
                            <th>Caja</th>
                            <th>Cajero</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                            <th class="text-end">Monto Inicial</th>
                            <th class="text-end">Ventas</th>
                            <th class="text-end">Ingresos</th>
                            <th class="text-end">Egresos</th>
                            <th class="text-end">Monto Esperado</th>
                            <th class="text-end">Monto Real</th>
                            <th class="text-end">Diferencia</th>
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
    const table = $('#dailyCashTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'print']
    });

    function loadData() {
        const start = document.getElementById("start_date").value;
        const end = document.getElementById("end_date").value;
        const cash = document.getElementById("idcaja").value;
        const user = document.getElementById("idusuario").value;

        fetch(`{{ route('report.operations.daily_cash.data') }}?start_date=${start}&end_date=${end}&idcaja=${cash}&idusuario=${user}`)
            .then(res => res.json())
            .then(data => {
                const signo = data.signo || "S/";
                table.clear();

                data.sessions.forEach(row => {
                    const diff = parseFloat(row.diferencia || 0);
                    let diffBadge = `<span class="badge bg-success">${signo} 0.00</span>`;
                    if (diff > 0) {
                        diffBadge = `<span class="badge bg-info">+${signo} ${diff.toFixed(2)} (${row.resultado})</span>`;
                    } else if (diff < 0) {
                        diffBadge = `<span class="badge bg-danger">${signo} ${diff.toFixed(2)} (${row.resultado})</span>`;
                    }

                    const statusBadge = parseInt(row.estado) === 1
                        ? '<span class="badge bg-primary">Abierta</span>'
                        : '<span class="badge bg-secondary">Cerrada</span>';

                    table.row.add([
                        row.caja,
                        row.usuario,
                        row.fecha_inicio,
                        row.fecha_fin || '-',
                        `${signo} ${parseFloat(row.monto_inicial).toFixed(2)}`,
                        `${signo} ${parseFloat(row.total_ventas).toFixed(2)}`,
                        `${signo} ${parseFloat(row.total_ingresos).toFixed(2)}`,
                        `${signo} ${parseFloat(row.total_egresos).toFixed(2)}`,
                        `${signo} ${parseFloat(row.monto_estimado).toFixed(2)}`,
                        `${signo} ${parseFloat(row.monto_final).toFixed(2)}`,
                        diffBadge,
                        statusBadge
                    ]);
                });
                table.draw();

                document.getElementById('kpi-total-inicial').innerText = `${signo} ${parseFloat(data.total_inicial || 0).toFixed(2)}`;
                document.getElementById('kpi-total-ventas').innerText = `${signo} ${parseFloat(data.total_ventas || 0).toFixed(2)}`;
                document.getElementById('kpi-total-contado').innerText = `${signo} ${parseFloat(data.total_contado || 0).toFixed(2)}`;
                document.getElementById('kpi-total-diferencia').innerText = `${signo} ${parseFloat(data.total_diferencia || 0).toFixed(2)}`;
            });
    }

    document.getElementById("formReport").addEventListener("submit", function(e) {
        e.preventDefault();
        loadData();
    });

    document.getElementById("btnExportPdf").addEventListener("click", function() {
        const start = document.getElementById("start_date").value;
        const end = document.getElementById("end_date").value;
        const cash = document.getElementById("idcaja").value;
        const user = document.getElementById("idusuario").value;
        window.location.href = `{{ route('report.operations.daily_cash.pdf') }}?start_date=${start}&end_date=${end}&idcaja=${cash}&idusuario=${user}`;
    });

    document.getElementById("btnExportExcel").addEventListener("click", function() {
        const start = document.getElementById("start_date").value;
        const end = document.getElementById("end_date").value;
        const cash = document.getElementById("idcaja").value;
        const user = document.getElementById("idusuario").value;
        window.location.href = `{{ route('report.operations.daily_cash.excel') }}?start_date=${start}&end_date=${end}&idcaja=${cash}&idusuario=${user}`;
    });

    loadData();
});
</script>
@endsection
