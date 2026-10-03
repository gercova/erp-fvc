@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Convenios Institucionales</h1>
            <p class="text-muted small mb-0">Gestión de alianzas estratégicas, convenios marco y específicos, adendas y compromisos.</p>
        </div>
        <div class="d-flex gap-2">
            @can('agreements.create')
            <a href="{{ route('agreements.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="fas fa-plus me-1"></i> Nuevo Convenio
            </a>
            @endcan
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: Activos -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-success shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Convenios Activos</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $kpiActive }}</div>
                            <small class="text-muted">Vigentes y operativos</small>
                        </div>
                        <div class="text-success opacity-50"><i class="fas fa-handshake fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 2: Próximos a Vencer -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-warning shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Por Vencer (&le; 30 días)</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $kpiExpiringSoon }}</div>
                            <small class="text-muted">Requieren adenda o cierre</small>
                        </div>
                        <div class="text-warning opacity-50"><i class="fas fa-hourglass-half fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 3: Monto Total -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-primary shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Monto Total Vigente</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">S/ {{ number_format($kpiTotalAmount, 2) }}</div>
                            <small class="text-muted">Contraprestación / inversión</small>
                        </div>
                        <div class="text-primary opacity-50"><i class="fas fa-coins fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 4: Progreso de Compromisos -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start-lg border-start-info shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Cumplimiento de Cláusulas</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $kpiObligationProgress }}%</div>
                            <small class="text-muted">Compromisos completados</small>
                        </div>
                        <div class="text-info opacity-50"><i class="fas fa-tasks fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card with Filters & DataTables -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <div class="row g-2 align-items-center">
                <!-- Filtro Tipo -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Tipo de Convenio:</label>
                    <select class="form-select form-select-sm" id="filterType">
                        <option value="">-- Todos los Tipos --</option>
                        @foreach($types as $t)
                            <option value="{{ $t->value }}">{{ $t->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro Estado -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Estado Operativo:</label>
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">-- Todos los Estados --</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}">{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro Área -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Departamento Responsable:</label>
                    <select class="form-select form-select-sm" id="filterArea">
                        <option value="">-- Todas las Áreas --</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Búsqueda -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Búsqueda Rápida:</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="filterSearch" placeholder="Código, nombre, RUC...">
                        <button class="btn btn-outline-secondary" type="button" id="btnResetFilters" title="Limpiar Filtros">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="agreementsTable">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3">Código</th>
                            <th>Tipo / Alcance</th>
                            <th>Convenio / Propósito</th>
                            <th>Contraparte (RUC)</th>
                            <th>Área / Coordinador</th>
                            <th>Vigencia</th>
                            <th>Monto</th>
                            <th>Compromisos</th>
                            <th>Estado</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <!-- Loaded dynamically via DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#agreementsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('agreements.data') }}",
            data: function (d) {
                d.filter_type = $('#filterType').val();
                d.filter_status = $('#filterStatus').val();
                d.filter_area = $('#filterArea').val();
                d.filter_search = $('#filterSearch').val();
            }
        },
        columns: [
            { data: 'codigo', name: 'code', className: 'ps-3 text-nowrap' },
            { data: 'tipo_alcance', name: 'type' },
            { data: 'nombre_convenio', name: 'name' },
            { data: 'contraparte', name: 'client.nombres' },
            { data: 'departamento_responsable', name: 'area.name' },
            { data: 'vigencia', name: 'end_date' },
            { data: 'monto', name: 'total_amount' },
            { data: 'compromisos_progreso', name: 'compromisos', orderable: false, searchable: false },
            { data: 'estado', name: 'status' },
            { data: 'acciones', name: 'acciones', orderable: false, searchable: false, className: 'text-end pe-3 text-nowrap' }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });

    $('#filterType, #filterStatus, #filterArea').on('change', function() {
        table.draw();
    });

    $('#filterSearch').on('keyup', function() {
        table.draw();
    });

    $('#btnResetFilters').on('click', function() {
        $('#filterType').val('');
        $('#filterStatus').val('');
        $('#filterArea').val('');
        $('#filterSearch').val('');
        table.draw();
    });
});
</script>
@endpush
