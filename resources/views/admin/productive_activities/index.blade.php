@extends('admin.layout')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center my-3">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Actividades Productivas y Empresariales (APE)</h1>
            <p class="text-muted small mb-0">Gestión de unidades operativas, centros de costos y aprobación institucional.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('productive_activities.cost_center.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-chart-line me-1"></i> Panel Centro de Costos
            </a>
            <a href="{{ route('productive_activities.transactions.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-exchange-alt me-1"></i> Movimientos Ingreso/Gasto
            </a>
            <a href="{{ route('productive_activities.rdr.index') }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-university me-1"></i> Módulo RDR / CUT
            </a>
            <a href="{{ route('productive_activities.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="fas fa-plus me-1"></i> Nueva Actividad
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-primary shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Actividades</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $totalActivities }}</div>
                        </div>
                        <div class="text-primary opacity-50"><i class="fas fa-cubes fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-success shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Actividades Activas</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $activeCount }}</div>
                        </div>
                        <div class="text-success opacity-50"><i class="fas fa-play-circle fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-warning shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">En Mantenimiento</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $maintenanceCount }}</div>
                        </div>
                        <div class="text-warning opacity-50"><i class="fas fa-tools fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-info shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Progreso Físico Promedio</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ number_format($avgProgress, 1) }}%</div>
                        </div>
                        <div class="text-info opacity-50"><i class="fas fa-tasks fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card with Filters and Yajra Table -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <div class="row g-2 align-items-center">
                <!-- Segmented Control de Tipo -->
                <div class="col-lg-7 col-md-12">
                    <div class="btn-group btn-group-sm flex-wrap w-100" role="group" id="filterTypeGroup">
                        @foreach ($types as $key => $label)
                            <button type="button" class="btn btn-outline-secondary {{ $selectedType === $key ? 'active btn-primary text-white' : '' }}" data-type="{{ $key }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Filtro por Área -->
                <div class="col-lg-3 col-md-6">
                    <select class="form-select form-select-sm" id="filterArea">
                        <option value="">-- Todas las Áreas --</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" {{ $selectedAreaId == $area->id ? 'selected' : '' }}>
                                {{ $area->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro por Estado -->
                <div class="col-lg-2 col-md-6">
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">-- Todos los Estados --</option>
                        <option value="ACTIVA">Activas</option>
                        <option value="EN_MANTENIMIENTO">En Mantenimiento</option>
                        <option value="CERRADA">Cerradas</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle w-100" id="activitiesTable" style="font-size: 0.9rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Código</th>
                            <th>Nombre de la Actividad</th>
                            <th style="width: 110px;">Tipo</th>
                            <th style="width: 90px;">C. Costo</th>
                            <th>Área Responsable</th>
                            <th>Responsable</th>
                            <th style="width: 140px;">Seguimiento</th>
                            <th style="width: 110px;">Estado</th>
                            <th style="width: 120px;">Aprobación</th>
                            <th style="width: 110px;" class="text-center">Acciones</th>
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
    let currentType = '{{ $selectedType }}';

    let table = $('#activitiesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("productive_activities.get") }}',
            data: function(d) {
                d.type = currentType;
                d.area_id = $('#filterArea').val();
                d.status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'code', name: 'code', className: 'fw-bold text-primary font-monospace' },
            { data: 'name', name: 'name', className: 'fw-bold' },
            { data: 'type_badge', name: 'type', orderable: false, searchable: false },
            { data: 'cost_center_code', name: 'cost_center_code', defaultContent: '<span class="text-muted">S/C</span>' },
            { data: 'area.name', name: 'area.name', defaultContent: '<span class="text-muted">No asignada</span>' },
            { data: 'head.nombres', name: 'head.nombres', defaultContent: '<span class="text-muted">No asignado</span>' },
            { data: 'progress_bar', name: 'execution_progress_percent', searchable: false },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'approval_status_badge', name: 'approval_status_badge', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[0, 'asc']]
    });

    // Filtro por tipo en Segmented Control
    $('#filterTypeGroup button').on('click', function() {
        $('#filterTypeGroup button').removeClass('active btn-primary text-white').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('active btn-primary text-white');
        currentType = $(this).data('type');
        table.draw();
    });

    $('#filterArea, #filterStatus').on('change', function() {
        table.draw();
    });

    // Enviar a aprobación institucional
    $(document).on('click', '.btn-submit-approval', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: '¿Enviar a Aprobación Institucional?',
            text: 'Se iniciará la cadena de firmas (Jefatura de Área, Administración y Dirección General).',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0061f2',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, enviar documento',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("productive-activities") }}/' + id + '/submit-approval',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(resp) {
                        Swal.fire('¡Enviado!', resp.message, 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        let msg = err.responseJSON ? err.responseJSON.message : 'Error al enviar a aprobación.';
                        Swal.fire('Atención', msg, 'error');
                    }
                });
            }
        });
    });

    // Eliminar actividad
    $(document).on('click', '.btn-delete', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        Swal.fire({
            title: '¿Eliminar Actividad Productiva?',
            text: 'Se dará de baja la actividad: ' + name,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e81500',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("productive_activities.delete") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id
                    },
                    success: function(resp) {
                        Swal.fire('Eliminado', resp.message, 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        Swal.fire('Error', 'No se pudo eliminar la actividad.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
