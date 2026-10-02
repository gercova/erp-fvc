@extends('layouts.app')

@section('title', 'Fallos de Contabilización Automática')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Fallos de Contabilización</h3>
            <p class="text-muted small mb-0">Cola de reintentos y reprocesamiento de eventos que no pudieron asentarse en el Libro Diario.</p>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('accounting.chart-of-accounts.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-sitemap me-1"></i> Plan de Cuentas
            </a>
            <button id="btn-reprocess-all" class="btn btn-danger btn-sm">
                <i class="fas fa-sync-alt me-1"></i> Reprocesar Todos los Pendientes
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-danger shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Fallos Pendientes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-pending">{{ $pendingCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-success shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Reprocesados con Éxito</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $reprocessedCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-primary shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Política de Reintentos</div>
                            <div class="small mb-0 font-weight-bold text-gray-800">3 intentos exponenciales (5s, 15s, 60s)</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-redo fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-1"></i> Registro de Errores y Excepciones</h6>
                </div>
                <div class="col-md-6 text-end">
                    <select id="filter-status" class="form-select form-select-sm d-inline-block w-auto">
                        <option value="">Todos los Estados</option>
                        <option value="FAILED" selected>Solo Fallidos</option>
                        <option value="REPROCESSED">Reprocesados</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle w-100" id="failures-table">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Evento / Clase</th>
                            <th width="15%">Origen</th>
                            <th width="35%">Causa del Error</th>
                            <th width="5%">Intentos</th>
                            <th width="10%">Estado</th>
                            <th width="10%">Fecha Fallo</th>
                            <th width="5%">Acción</th>
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
    const table = $('#failures-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("accounting.failures.data") }}',
            data: function(d) {
                d.status = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'event_name', name: 'event_name' },
            { data: 'source_info', name: 'source_id' },
            { 
                data: 'error_message', 
                name: 'error_message',
                render: function(data) {
                    return '<span class="text-danger small font-monospace">' + $('<div>').text(data).html() + '</span>';
                }
            },
            { data: 'attempts', name: 'attempts', className: 'text-center' },
            { data: 'status', name: 'status', className: 'text-center' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        }
    });

    $('#filter-status').on('change', function() {
        table.ajax.reload();
    });

    // Single reprocess
    $(document).on('click', '.btn-reprocess', function() {
        const id = $(this).data('id');
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ url("/accounting/failures") }}/' + id + '/reprocess',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Reprocesado!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Reprocesar');
                Swal.fire({
                    icon: 'error',
                    title: 'Error al reprocesar',
                    text: xhr.responseJSON ? xhr.responseJSON.message : 'Error inesperado.'
                });
            }
        });
    });

    // Reprocess all
    $('#btn-reprocess-all').on('click', function() {
        Swal.fire({
            title: '¿Reprocesar todos los fallos?',
            text: 'Se intentará contabilizar nuevamente todos los registros fallidos en orden.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, reprocesar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("accounting.failures.reprocess-all") }}',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        Swal.fire('Proceso completado', res.message, 'success');
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON ? xhr.responseJSON.message : 'Error en la solicitud', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
