@extends('admin.layout')
@section('title', 'Papeletas de Salida por Vacaciones')

@section('styles')
    <style>
        .vacation-card,
        .vacation-card .card-body,
        .vacation-card .table-responsive,
        .vacation-card .dropdown,
        .vacation-card td,
        .vacation-card th {
            overflow: visible !important;
        }

        .vacation-card .dropdown-menu {
            z-index: 1055 !important;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">
                    <i class="fas fa-calendar-alt text-danger me-2"></i>Papeletas de Salida por Vacaciones
                </h1>
                <p class="text-muted small mb-0">Autorización y registro del descanso vacacional para personal docente y administrativo</p>
            </div>
            <div>
                @can('vacation_exit_slips.create')
                    <a href="{{ route('vacation-exit-slips.create') }}" class="btn btn-primary shadow-sm">
                        <i class="fas fa-plus me-1"></i> Nueva Papeleta de Vacaciones
                    </a>
                @endcan
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">Buscar por N°, Servidor, DNI o Área</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i
                                    class="fas fa-search text-muted"></i></span>
                            <input type="text" id="filter_search" class="form-control border-start-0"
                                placeholder="Ej: PSVAC-000001, Juan Perez, 12345678...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Estado</label>
                        <select id="filter_status" class="form-select">
                            <option value="">-- Todos los Estados --</option>
                            <option value="PENDIENTE">PENDIENTE</option>
                            <option value="EN_REVISION">EN REVISIÓN</option>
                            <option value="APROBADO">APROBADO</option>
                            <option value="OBSERVADO">OBSERVADO</option>
                            <option value="RECHAZADO">RECHAZADO</option>
                            <option value="ANULADO">ANULADO</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Fecha de Inicio</label>
                        <input type="date" id="filter_date" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="button" id="btn_filter" class="btn btn-secondary w-100">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                        <button type="button" id="btn_reset" class="btn btn-outline-secondary">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card shadow-sm border-0 vacation-card">
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table class="table table-hover align-middle w-100" id="vacationTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 130px;">N° Papeleta</th>
                                <th>Servidor / Condición</th>
                                <th>Área / Programa de Estudios</th>
                                <th style="width: 180px;">Período Vacacional</th>
                                <th style="width: 110px;" class="text-center">Estado</th>
                                <th style="width: 70px;" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        @if (session('toast_success'))
            <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
                <div id="toastSuccess" class="toast align-items-center text-bg-success border-0 shadow-lg" role="alert"
                    aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                    <div class="d-flex">
                        <div class="toast-body fw-semibold">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('toast_success') }}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                            aria-label="Cerrar"></button>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            const table = $('#vacationTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('vacation-exit-slips.get') }}",
                    data: function(d) {
                        d.filter_status = $('#filter_status').val();
                        d.filter_search = $('#filter_search').val();
                        d.filter_date = $('#filter_date').val();
                    }
                },
                columns: [{
                        data: 'correlativo',
                        name: 'correlativo'
                    },
                    {
                        data: 'servidor',
                        name: 'apellidos_nombres'
                    },
                    {
                        data: 'area_cargo',
                        name: 'area_programa_estudios'
                    },
                    {
                        data: 'periodo',
                        name: 'fecha_desde'
                    },
                    {
                        data: 'estado_badge',
                        name: 'status',
                        className: 'text-center'
                    },
                    {
                        data: 'acciones',
                        name: 'acciones',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                },
                order: [
                    [0, 'desc']
                ]
            });

            $('#btn_filter').click(function() {
                table.draw();
            });

            $('#filter_search').on('keyup', function(e) {
                if (e.key === 'Enter') table.draw();
            });

            $('#filter_status, #filter_date').on('change', function() {
                table.draw();
            });

            $('#btn_reset').click(function() {
                $('#filter_status').val('');
                $('#filter_search').val('');
                $('#filter_date').val('');
                table.draw();
            });

            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: '¿Anular Papeleta de Vacaciones?',
                    text: 'Esta acción cancelará la papeleta de salida por vacaciones y su flujo de firmas.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, anular',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('vacation-exit-slips.delete') }}",
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                id: id
                            },
                            success: function(resp) {
                                Swal.fire('Anulado', resp.message ||
                                    'La papeleta fue anulada.', 'success');
                                table.draw(false);
                            },
                            error: function(err) {
                                Swal.fire('Error', err.responseJSON?.message ||
                                    'No se pudo anular la papeleta.', 'error');
                            }
                        });
                    }
                });
            });

            @if (session('toast_success'))
                const toastEl = document.getElementById('toastSuccess');
                if (toastEl) {
                    new bootstrap.Toast(toastEl).show();
                }
            @endif
        });
    </script>
@endsection
