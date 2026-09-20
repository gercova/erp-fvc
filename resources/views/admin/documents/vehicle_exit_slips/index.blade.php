@extends('admin.layout')
@section('title', 'Papeletas de Salida de Vehículo')

@section('styles')
    <style>
        .vehicle-card,
        .vehicle-card .card-body,
        .vehicle-card .table-responsive,
        .vehicle-card .dropdown,
        .vehicle-card td,
        .vehicle-card th {
            overflow: visible !important;
        }

        .vehicle-card .dropdown-menu {
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
                    <i class="fas fa-truck text-danger me-2"></i>Papeletas de Salida de Vehículo
                </h1>
                <p class="text-muted small mb-0">Autorización y control de salidas de las unidades móviles institucionales</p>
            </div>
            <div>
                @can('vehicle_exit_slips.create')
                    <a href="{{ route('vehicle-exit-slips.create') }}" class="btn btn-primary shadow-sm">
                        <i class="fas fa-plus me-1"></i> Nueva Papeleta de Vehículo
                    </a>
                @endcan
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">Buscar por N°, Vehículo, Solicitante, Chofer o Destino</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i
                                    class="fas fa-search text-muted"></i></span>
                            <input type="text" id="filter_search" class="form-control border-start-0"
                                placeholder="Ej: PSV-000001, Camioneta Hilux, Juan Perez, Lima...">
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
                        <label class="form-label small fw-semibold text-muted">Fecha de Salida</label>
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
        <div class="card shadow-sm border-0 vehicle-card">
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table class="table table-hover align-middle w-100" id="vehicleTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 120px;">N° Papeleta</th>
                                <th>Vehículo / Destino</th>
                                <th>Solicitante / Chofer</th>
                                <th style="width: 180px;">Horario (Salida / Retorno)</th>
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
            const table = $('#vehicleTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('vehicle-exit-slips.get') }}",
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
                        data: 'vehiculo_info',
                        name: 'vehiculo'
                    },
                    {
                        data: 'personas',
                        name: 'solicitante_nombre'
                    },
                    {
                        data: 'horario',
                        name: 'fecha_salida'
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
                    title: '¿Anular Papeleta de Vehículo?',
                    text: 'Esta acción cancelará la papeleta de salida de vehículo y su flujo de firmas.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, anular',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('vehicle-exit-slips.delete') }}",
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
