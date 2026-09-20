@extends('admin.layout')

@section('title', 'Bandeja de Aprobaciones')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center my-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="fas fa-signature text-primary me-2"></i>Bandeja de Aprobaciones y Visto Bueno
            </h1>
            <p class="text-muted small mb-0">Gestión centralizada de autorizaciones, visaciones y firmas de requerimientos y formatos institucionales</p>
        </div>
    </div>

    <!-- Tarjetas KPI -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Pendientes de Firma</div>
                            <div class="h3 mb-0 fw-bold text-gray-800" id="kpi_pending">{{ $pendingCount }}</div>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Firmados por Mí</div>
                            <div class="h3 mb-0 fw-bold text-gray-800" id="kpi_signed">{{ $signedCount }}</div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-double fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navegación de Pestañas (Tabs) y Filtros -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3 border-0" id="approvalTabs">
                <li class="nav-item">
                    <button class="nav-link active py-3 px-4 fw-semibold text-dark border-0 border-bottom border-3 border-primary" data-scope="pending" type="button">
                        <i class="fas fa-inbox me-2 text-warning"></i> Pendientes por Firmar
                        @if($pendingCount > 0)
                            <span class="badge bg-danger ms-2">{{ $pendingCount }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 px-4 fw-semibold text-muted border-0 border-bottom border-3 border-transparent" data-scope="signed" type="button">
                        <i class="fas fa-clipboard-check me-2 text-success"></i> Documentos Firmados
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted">Buscar por Solicitante, Paso o Etapa</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="filter_search" class="form-control border-start-0" placeholder="Ej: Juan Pérez, Director, Unidad de Administración...">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">Tipo de Formato</label>
                    <select id="filter_type" class="form-select">
                        <option value="">-- Todos los Formatos --</option>
                        <option value="requisition">Requerimientos de Bienes/Servicios</option>
                        <option value="expense_declaration">Declaración Jurada de Gastos</option>
                        <option value="exit_slip">Papeleta de Salida de Personal</option>
                        <option value="vehicle_exit_slip">Papeleta de Salida de Vehículos</option>
                        <option value="vacation_exit_slip">Papeleta de Salida por Vacaciones</option>
                        <option value="fuel_control_slip">Vale de Control de Combustible</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
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

    <!-- Tabla de Aprobaciones -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle w-100" id="approvalsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 140px;">Tipo Formato</th>
                            <th style="width: 120px;">N° Documento</th>
                            <th>Solicitante</th>
                            <th>Etapa / Instancia de Firma</th>
                            <th style="width: 110px;" class="text-center">Estado</th>
                            <th style="width: 130px;">Fecha</th>
                            <th style="width: 160px;" class="text-center">Acción</th>
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
    let currentScope = 'pending';

    const table = $('#approvalsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('approvals.get') }}",
            data: function(d) {
                d.filter_scope = currentScope;
                d.filter_type = $('#filter_type').val();
                d.filter_search = $('#filter_search').val();
            }
        },
        columns: [
            { data: 'tipo_documento', name: 'document_type' },
            { data: 'documento_correlativo', name: 'documento_correlativo' },
            { data: 'solicitante', name: 'solicitante' },
            { data: 'label', name: 'label' },
            { data: 'status', name: 'status', className: 'text-center' },
            { data: 'fecha', name: 'fecha' },
            { data: 'acciones', name: 'acciones', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        order: [[5, 'desc']]
    });

    $('#approvalTabs button').click(function() {
        $('#approvalTabs button').removeClass('active border-primary text-dark').addClass('text-muted border-transparent');
        $(this).addClass('active border-primary text-dark').removeClass('text-muted border-transparent');
        currentScope = $(this).data('scope');
        table.draw();
    });

    $('#btn_filter').click(function() {
        table.draw();
    });

    $('#filter_search').on('keyup', function(e) {
        if (e.key === 'Enter') table.draw();
    });

    $('#filter_type').on('change', function() {
        table.draw();
    });

    $('#btn_reset').click(function() {
        $('#filter_type').val('');
        $('#filter_search').val('');
        table.draw();
    });
});
</script>
@endpush
