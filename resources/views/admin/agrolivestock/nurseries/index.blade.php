@extends('admin.layout')
@section('title', 'Viveros y Producción Forestal / Agrícola')

@section('styles')
<style>
    .metric-nursery-card {
        border-radius: 8px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        transition: transform 0.15s ease;
    }
    .metric-nursery-card:hover {
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
                    <li class="breadcrumb-item active" aria-current="page">Viveros y Forestal</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-seedling me-2 text-success"></i>Viveros y Producción Forestal
            </h1>
            <p class="text-muted small mb-0">Control de almácigos, tubos y plántulas (eucalipto, capirona, cacao, cítricos, café, barbasco, etc.)</p>
        </div>
        <div>
            <button type="button" class="btn btn-success fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNursery">
                <i class="fas fa-plus me-1"></i> Nuevo Vivero / Lote
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metrics Summary -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-nursery-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Plántulas Actuales</div>
                        <div class="h3 mb-0 fw-bold text-success mt-1">{{ number_format($totalSeedlingsCurrent) }}</div>
                        <small class="text-muted">{{ $totalNurseries }} camas / lotes en vivero</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-spa fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-nursery-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Listas para Campo / Venta</div>
                        <div class="h3 mb-0 fw-bold text-primary mt-1">{{ number_format($readyForFieldCount) }}</div>
                        <small class="text-muted">Etapa de despacho inmediato</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(0, 97, 242, 0.1); color: #0061f2;">
                        <i class="fas fa-truck-loading fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-nursery-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Tasa Media de Supervivencia</div>
                        <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($avgSurvivalRate, 1) }}%</div>
                        <small class="text-muted">Eficiencia de enraizamiento</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(244, 161, 0, 0.1); color: #f4a100;">
                        <i class="fas fa-percentage fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-nursery-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Actividades Asociadas</div>
                        <div class="h3 mb-0 fw-bold text-purple mt-1" style="color: #6900c7;">{{ $activities->count() }}</div>
                        <small class="text-muted">Proyectos forestales/agrícolas</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(105, 0, 199, 0.1); color: #6900c7;">
                        <i class="fas fa-project-diagram fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
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
                    <label class="form-label small fw-bold text-muted mb-1">Etapa de Desarrollo</label>
                    <select id="filter_stage" class="form-select form-select-sm">
                        <option value="">-- Todas las Etapas --</option>
                        <option value="GERMINATION">Germinación / Almácigo</option>
                        <option value="SEEDLING_CONTAINER">Plántula en Bolsa/Tubo</option>
                        <option value="HARDENING_ACCLIMATIZATION">Rustificación / Aclimatación</option>
                        <option value="READY_FOR_FIELD">Listo para Campo / Venta</option>
                        <option value="DISPATCHED">Despachado</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Buscar por Especie o Vivero</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                        <input type="text" id="search_term" class="form-control" placeholder="Ej. Eucalipto, Cacao, Capirona...">
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
                <i class="fas fa-table me-1 text-success"></i> Catálogo de Especies y Lotes en Vivero
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle w-100" id="nurseries-table">
                    <thead class="table-light">
                        <tr>
                            <th>Especie / Identificador</th>
                            <th>Actividad (Fundo)</th>
                            <th>Población y Supervivencia</th>
                            <th>Etapa de Desarrollo</th>
                            <th>Fechas Clave</th>
                            <th>Responsable</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Vivero (Crear / Editar) -->
<div class="modal fade" id="modalNursery" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formNursery" method="POST" action="{{ route('agrolivestock.nurseries.store') }}">
            @csrf
            <div id="method_field"></div>
            <input type="hidden" id="nursery_id" name="id">

            <div class="modal-content">
                <div class="modal-header bg-success text-white py-2">
                    <h5 class="modal-title fs-6 fw-bold" id="modalNurseryTitle">
                        <i class="fas fa-seedling me-2"></i>Registrar Vivero / Lote de Plántulas
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Actividad Productiva <span class="text-danger">*</span></label>
                            <select name="productive_activity_id" id="nursery_activity_id" class="form-select form-select-sm" required>
                                <option value="">-- Seleccionar --</option>
                                @foreach($activities as $act)
                                    <option value="{{ $act->id }}">{{ $act->name }} ({{ $act->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fase de Producción (Opcional - Bloque C)</label>
                            <select name="production_batch_id" id="nursery_batch_id" class="form-select form-select-sm">
                                <option value="">-- Ninguno / Independiente --</option>
                                @foreach($batches as $b)
                                    <option value="{{ $b->id }}">{{ $b->batch_code }} - {{ $b->phase_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Especie Forestal o Agrícola <span class="text-danger">*</span></label>
                            <input type="text" name="species_name" id="nursery_species" class="form-control form-control-sm" placeholder="Ej. Eucalipto urophylla, Capirona, Cacao CCN-51, Cítricos, Café, Barbasco" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nombre / Identificador del Lote o Cama <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="nursery_name" class="form-control form-control-sm" placeholder="Ej. Cama 04 - Eucalipto Clonado" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Ubicación del Vivero <span class="text-danger">*</span></label>
                            <input type="text" name="location" id="nursery_location" class="form-control form-control-sm" placeholder="Ej. Vivero Central Fundo Gavilán - Módulo 2" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Etapa de Desarrollo <span class="text-danger">*</span></label>
                            <select name="stage" id="nursery_stage" class="form-select form-select-sm" required>
                                <option value="GERMINATION">Germinación / Almácigo</option>
                                <option value="SEEDLING_CONTAINER">Plántula en Bolsa/Tubo</option>
                                <option value="HARDENING_ACCLIMATIZATION">Rustificación / Aclimatación</option>
                                <option value="READY_FOR_FIELD">Listo para Campo Definitivo / Venta</option>
                                <option value="DISPATCHED">Despachado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Cantidad Inicial (Semillas/Almácigos) <span class="text-danger">*</span></label>
                            <input type="number" name="initial_quantity" id="nursery_initial_qty" class="form-control form-control-sm" placeholder="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Cantidad Actual (Plántulas Vivas) <span class="text-danger">*</span></label>
                            <input type="number" name="current_quantity" id="nursery_current_qty" class="form-control form-control-sm" placeholder="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Fecha de Siembra / Almácigo <span class="text-danger">*</span></label>
                            <input type="date" name="sowing_date" id="nursery_sowing_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Fecha Estimada de Venta / Despacho</label>
                            <input type="date" name="estimated_dispatch_date" id="nursery_dispatch_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Responsable Técnico</label>
                            <select name="in_charge_user_id" id="nursery_user_id" class="form-select form-select-sm">
                                <option value="">-- No Asignado --</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->nombres }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Notas Técnicas y Manejo</label>
                            <textarea name="notes" id="nursery_notes" rows="2" class="form-control form-control-sm" placeholder="Tratamiento pregerminativo, sustrato empleado, observaciones fitosanitarias..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success fw-semibold">
                        <i class="fas fa-save me-1"></i> Guardar Vivero
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
    const table = $('#nurseries-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('agrolivestock.nurseries.get') }}",
            data: function(d) {
                d.activity_id = $('#filter_activity').val();
                d.stage = $('#filter_stage').val();
                d.search_term = $('#search_term').val();
            }
        },
        columns: [
            { data: 'species_col', name: 'species_name' },
            { data: 'activity_col', name: 'activity.name' },
            { data: 'quantities_col', name: 'current_quantity' },
            { data: 'stage_col', name: 'stage' },
            { data: 'dates_col', name: 'sowing_date' },
            { data: 'in_charge_col', name: 'inChargeUser.name', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });

    $('#filter_activity, #filter_stage').on('change', function() {
        table.ajax.reload();
    });

    $('#search_term').on('keyup', function() {
        table.ajax.reload();
    });

    $('#btn_reset_filters').on('click', function() {
        $('#filter_activity').val('');
        $('#filter_stage').val('');
        $('#search_term').val('');
        table.ajax.reload();
    });

    // Resetear modal para nuevo
    $('[data-bs-target="#modalNursery"]').on('click', function() {
        $('#formNursery').attr('action', "{{ route('agrolivestock.nurseries.store') }}");
        $('#method_field').empty();
        $('#modalNurseryTitle').html('<i class="fas fa-seedling me-2"></i>Registrar Vivero / Lote de Plántulas');
        $('#formNursery')[0].reset();
        $('#nursery_id').val('');
    });

    // Editar
    $(document).on('click', '.btn-edit-nursery', function() {
        const d = $(this).data();
        $('#formNursery').attr('action', "/agrolivestock/nurseries/" + d.id);
        $('#method_field').html('@method("PUT")');
        $('#modalNurseryTitle').html('<i class="fas fa-edit me-2"></i>Editar Vivero: ' + d.species);

        $('#nursery_id').val(d.id);
        $('#nursery_name').val(d.name);
        $('#nursery_activity_id').val(d.activity);
        $('#nursery_batch_id').val(d.batch);
        $('#nursery_location').val(d.location);
        $('#nursery_species').val(d.species);
        $('#nursery_stage').val(d.stage);
        $('#nursery_initial_qty').val(d.initial);
        $('#nursery_current_qty').val(d.current);
        $('#nursery_sowing_date').val(d.sowing);
        $('#nursery_dispatch_date').val(d.dispatch);
        $('#nursery_user_id').val(d.user);
        $('#nursery_notes').val(d.notes);

        $('#modalNursery').modal('show');
    });

    // Eliminar
    $(document).on('click', '.btn-delete-nursery', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar Vivero?',
            text: 'Esta acción eliminará el registro del vivero.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('agrolivestock.nurseries.delete') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id
                    },
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire('Eliminado', resp.message, 'success');
                            table.ajax.reload(null, false);
                        }
                    }
                });
            }
        });
    });
});
</script>
@endsection
