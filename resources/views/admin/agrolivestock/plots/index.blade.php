@extends('admin.layout')
@section('title', 'Gestión de Parcelas y Terrenos Agrícolas')

@section('styles')
<style>
    .metric-plot-card {
        border-radius: 8px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        transition: transform 0.15s ease;
    }
    .metric-plot-card:hover {
        transform: translateY(-2px);
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Agropecuario y Forestal</li>
                    <li class="breadcrumb-item active" aria-current="page">Parcelas y Lotes</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-mountain me-2 text-success"></i>Parcelas y Terrenos Agrícolas
            </h1>
            <p class="text-muted small mb-0">Catastro interno de parcelas por actividad productiva (Fundo Gavilán, etc.), superficie y cultivos instalados</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPlot">
                <i class="fas fa-plus me-1"></i> Nueva Parcela
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-plot-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Superficie Total</div>
                        <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($totalHectares, 2) }} ha</div>
                        <small class="text-muted">{{ $totalPlots }} parcelas catastradas</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-vector-square fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-plot-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Superficie en Producción</div>
                        <div class="h3 mb-0 fw-bold text-success mt-1">{{ number_format($activeHectares, 2) }} ha</div>
                        <small class="text-muted">Con plantaciones activas</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(0, 97, 242, 0.1); color: #0061f2;">
                        <i class="fas fa-seedling fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-plot-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Parcelas en Descanso</div>
                        <div class="h3 mb-0 fw-bold text-warning mt-1">{{ $fallowPlots }}</div>
                        <small class="text-muted">Barbecho o rotación</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(244, 161, 0, 0.1); color: #f4a100;">
                        <i class="fas fa-bed fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-plot-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Actividades Vinculadas</div>
                        <div class="h3 mb-0 fw-bold text-primary mt-1">{{ $activities->count() }}</div>
                        <small class="text-muted">Fundos y unidades APE</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(105, 0, 199, 0.1); color: #6900c7;">
                        <i class="fas fa-map-marked-alt fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Filtrar por Actividad Productiva</label>
                    <select id="filter_activity" class="form-select form-select-sm">
                        <option value="">-- Todas las Actividades --</option>
                        @foreach($activities as $act)
                            <option value="{{ $act->id }}" {{ $selectedActivityId == $act->id ? 'selected' : '' }}>
                                {{ $act->name }} ({{ $act->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Estado de la Parcela</label>
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">-- Todos los Estados --</option>
                        <option value="IN_PRODUCTION">En Producción</option>
                        <option value="FALLOW_REST">En Descanso / Barbecho</option>
                        <option value="PREPARATION">En Preparación</option>
                        <option value="ABANDONED">Abandonada</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Buscar por Nombre o Código</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                        <input type="text" id="search_term" class="form-control" placeholder="Ej. Lote 01, Fundo Gavilán...">
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" id="btn_reset_filters" class="btn btn-sm btn-outline-secondary w-100" title="Restablecer">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-list me-1 text-success"></i> Catálogo de Parcelas y Superficies
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle w-100" id="plots-table">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Parcela / Sector</th>
                            <th>Actividad (Fundo)</th>
                            <th>Área</th>
                            <th>Cultivos Instalados</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Parcela (Crear / Editar) -->
<div class="modal fade" id="modalPlot" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formPlot" method="POST" action="{{ route('agrolivestock.plots.store') }}">
            @csrf
            <div id="method_field"></div>
            <input type="hidden" id="plot_id" name="id">

            <div class="modal-content">
                <div class="modal-header bg-success text-white py-2">
                    <h5 class="modal-title fs-6 fw-bold" id="modalPlotTitle">
                        <i class="fas fa-mountain me-2"></i>Registrar Parcela / Lote
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Actividad Productiva (Fundo / Proyecto) <span class="text-danger">*</span></label>
                            <select name="productive_activity_id" id="plot_activity_id" class="form-select form-select-sm" required>
                                <option value="">-- Seleccionar --</option>
                                @foreach($activities as $act)
                                    <option value="{{ $act->id }}">{{ $act->name }} ({{ $act->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Código Parcela <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="plot_code" class="form-control form-control-sm font-monospace" placeholder="Ej. FG-LOTE01" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Área (Hectáreas) <span class="text-danger">*</span></label>
                            <input type="number" step="0.0001" name="area_hectares" id="plot_area" class="form-control form-control-sm" placeholder="0.0000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nombre / Identificador de Parcela <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="plot_name" class="form-control form-control-sm" placeholder="Ej. Parcela Palma 1 - Sector Bajo" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Ubicación / Sector / Referencia <span class="text-danger">*</span></label>
                            <input type="text" name="sector_location" id="plot_location" class="form-control form-control-sm" placeholder="Ej. Fundo Gavilán Km 12 margen izquierda" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Topografía</label>
                            <select name="topography" id="plot_topography" class="form-select form-select-sm">
                                <option value="PLANA">Plana</option>
                                <option value="ONDULADA">Ondulada</option>
                                <option value="COLINOSA">Colinosa</option>
                                <option value="PENDIENTE_PRONUNCIADA">Pendiente Pronunciada</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tipo de Suelo</label>
                            <input type="text" name="soil_type" id="plot_soil" class="form-control form-control-sm" placeholder="Ej. Franco-arcilloso, Aluvial">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Estado <span class="text-danger">*</span></label>
                            <select name="status" id="plot_status" class="form-select form-select-sm" required>
                                <option value="IN_PRODUCTION">En Producción</option>
                                <option value="FALLOW_REST">En Descanso / Barbecho</option>
                                <option value="PREPARATION">En Preparación</option>
                                <option value="ABANDONED">Abandonada</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Observaciones / Notas Técnicas</label>
                            <textarea name="notes" id="plot_notes" rows="2" class="form-control form-control-sm" placeholder="Acceso vial, drenaje, coordenadas o notas generales..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success fw-semibold">
                        <i class="fas fa-save me-1"></i> Guardar Parcela
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const table = $('#plots-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('agrolivestock.plots.get') }}",
            data: function(d) {
                d.activity_id = $('#filter_activity').val();
                d.status = $('#filter_status').val();
                d.search_term = $('#search_term').val();
            }
        },
        columns: [
            { data: 'code_col', name: 'code' },
            { data: 'name_col', name: 'name' },
            { data: 'activity_col', name: 'activity.name' },
            { data: 'area_col', name: 'area_hectares' },
            { data: 'plantations_col', name: 'plantations_col', orderable: false, searchable: false },
            { data: 'status_col', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });

    $('#filter_activity, #filter_status').on('change', function() {
        table.ajax.reload();
    });

    $('#search_term').on('keyup', function() {
        table.ajax.reload();
    });

    $('#btn_reset_filters').on('click', function() {
        $('#filter_activity').val('');
        $('#filter_status').val('');
        $('#search_term').val('');
        table.ajax.reload();
    });

    // Resetear modal al abrir para nuevo
    $('[data-bs-target="#modalPlot"]').on('click', function() {
        $('#formPlot').attr('action', "{{ route('agrolivestock.plots.store') }}");
        $('#method_field').empty();
        $('#modalPlotTitle').html('<i class="fas fa-mountain me-2"></i>Registrar Parcela / Lote');
        $('#formPlot')[0].reset();
        $('#plot_id').val('');
    });

    // Editar Parcela
    $(document).on('click', '.btn-edit-plot', function() {
        const d = $(this).data();
        $('#formPlot').attr('action', "/agrolivestock/plots/" + d.id);
        $('#method_field').html('@method("PUT")');
        $('#modalPlotTitle').html('<i class="fas fa-edit me-2"></i>Editar Parcela: ' + d.name);

        $('#plot_id').val(d.id);
        $('#plot_activity_id').val(d.activity);
        $('#plot_code').val(d.code);
        $('#plot_area').val(d.area);
        $('#plot_name').val(d.name);
        $('#plot_location').val(d.location);
        $('#plot_topography').val(d.topography);
        $('#plot_soil').val(d.soil);
        $('#plot_status').val(d.status);
        $('#plot_notes').val(d.notes);

        $('#modalPlot').modal('show');
    });

    // Eliminar Parcela
    $(document).on('click', '.btn-delete-plot', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar Parcela?',
            text: 'Esta acción no eliminará las cosechas históricas, pero la parcela ya no figurará activa.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('agrolivestock.plots.delete') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id
                    },
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire('Eliminada', resp.message, 'success');
                            table.ajax.reload(null, false);
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo eliminar la parcela.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endsection
