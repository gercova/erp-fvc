@extends('admin.layout')

@section('title', 'Vales de Control de Combustible y Lubricantes')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center my-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="fas fa-gas-pump text-danger me-2"></i>Vales de Control Interno
            </h1>
            <p class="text-muted small mb-0">Control de abastecimiento de combustible y lubricantes para vehículos y maquinaria</p>
        </div>
        <div>
            <a href="{{ route('fuel-control-slips.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus me-1"></i> Nuevo Vale de Control
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">Buscar por N°, Grifo, Vehículo o Placa</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="filter_search" class="form-control border-start-0" placeholder="Ej: 0000041, Grifo San Juan, Hilux, EGL-123...">
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
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Fecha de Abastecimiento</label>
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
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle w-100" id="fuelTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 110px;">N° Vale</th>
                            <th style="width: 140px;">Fecha / Hora</th>
                            <th>Vehículo / Maquinaria</th>
                            <th>Grifo / Actividad</th>
                            <th style="width: 110px;">Total (S/)</th>
                            <th style="width: 120px;">Estado</th>
                            <th style="width: 70px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const table = $('#fuelTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('fuel-control-slips.get') }}",
            data: function(d) {
                d.filter_status = $('#filter_status').val();
                d.filter_search = $('#filter_search').val();
                d.filter_date = $('#filter_date').val();
            }
        },
        columns: [
            { data: 'correlativo', name: 'correlativo' },
            { data: 'fecha', name: 'fecha' },
            { data: 'vehiculo_placa', name: 'vehiculo_placa' },
            { data: 'grifo_actividad', name: 'grifo_actividad' },
            { data: 'total_general', name: 'total_general', className: 'text-end' },
            { data: 'estado_badge', name: 'status', className: 'text-center' },
            { data: 'acciones', name: 'acciones', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        order: [[0, 'desc']]
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
            title: '¿Anular Vale de Control?',
            text: 'Esta acción cancelará el vale de abastecimiento y su flujo de firmas.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('fuel-control-slips.delete') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id
                    },
                    success: function(resp) {
                        Swal.fire('Anulado', resp.message || 'El vale fue anulado.', 'success');
                        table.draw(false);
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON?.message || 'No se pudo anular el vale.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
