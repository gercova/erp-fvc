@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-fluid px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="calendar"></i></div>
                        Papeletas de Salida por Vacaciones
                    </h1>
                    <div class="small text-muted mt-1">Autorización y registro del descanso vacacional para personal docente y administrativo.</div>
                </div>
                <div class="col-auto mb-3">
                    @can('vacation_exit_slips.create')
                        <a href="{{ route('vacation-exit-slips.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva Papeleta de Vacaciones
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid px-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="filter-search" class="form-control border-start-0" placeholder="Buscar por N°, servidor, DNI o área...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filter-status" class="form-select">
                        <option value="">-- Todos los Estados --</option>
                        <option value="PENDIENTE">Pendiente</option>
                        <option value="EN_REVISION">En Revisión</option>
                        <option value="APROBADO">Aprobado</option>
                        <option value="OBSERVADO">Observado</option>
                        <option value="RECHAZADO">Rechazado</option>
                        <option value="ANULADO">Anulado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" id="filter-date" class="form-control" title="Filtrar por fecha de inicio">
                </div>
                <div class="col-md-2 text-end">
                    <button id="btn-refresh" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-sync-alt me-1"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="vacation-slips-table" class="table table-hover table-striped align-middle mb-0" style="width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">N° Papeleta</th>
                            <th>Servidor / Condición</th>
                            <th>Área / Programa de Estudios</th>
                            <th>Período Vacacional</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let table = $('#vacation-slips-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('vacation-exit-slips.get') }}",
            data: function(d) {
                d.filter_search = $('#filter-search').val();
                d.filter_status = $('#filter-status').val();
                d.filter_date = $('#filter-date').val();
            }
        },
        columns: [
            { data: 'correlativo', name: 'correlativo', className: 'ps-4' },
            { data: 'servidor', name: 'apellidos_nombres' },
            { data: 'area_cargo', name: 'area_programa_estudios' },
            { data: 'periodo', name: 'fecha_desde' },
            { data: 'estado_badge', name: 'status', className: 'text-center' },
            { data: 'acciones', name: 'acciones', orderable: false, searchable: false, className: 'text-end pe-4' }
        ],
        order: [[0, 'desc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        }
    });

    $('#filter-search, #filter-status, #filter-date').on('change keyup', function() {
        table.draw();
    });

    $('#btn-refresh').on('click', function() {
        $('#filter-search').val('');
        $('#filter-status').val('');
        $('#filter-date').val('');
        table.draw();
    });

    $(document).on('click', '.btn-delete', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: '¿Desea anular esta papeleta de vacaciones?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e81500',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('vacation-exit-slips.delete') }}",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}", id: id },
                    success: function(response) {
                        toastr.success(response.message);
                        table.ajax.reload();
                    }
                });
            }
        });
    });
});
</script>
@endsection
