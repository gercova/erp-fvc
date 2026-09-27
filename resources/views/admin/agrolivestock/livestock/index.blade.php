@extends('admin.layout')
@section('title', 'Manejo Pecuario: Ganado Vacuno, Porcino, Cuyes y Aves')

@section('styles')
<style>
    .metric-livestock-card {
        border-radius: 8px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        transition: transform 0.15s ease;
    }
    .metric-livestock-card:hover {
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
                    <li class="breadcrumb-item active" aria-current="page">Manejo Pecuario</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-paw me-2 text-danger"></i>Manejo Pecuario e Inventario Animal
            </h1>
            <p class="text-muted small mb-0">Control individual y por lotes: bovinos, porcinos, cuyes y aves. Eventos sanitarios y fases de alimentación.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalLivestock">
                <i class="fas fa-plus me-1"></i> Nueva Unidad / Lote Pecuario
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
            <div class="card metric-livestock-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Población Activa Total</div>
                        <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($totalHeadsActive) }}</div>
                        <small class="text-muted">{{ $totalUnits }} unidades/lotes registrados</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(0, 97, 242, 0.1); color: #0061f2;">
                        <i class="fas fa-layer-group fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-livestock-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Bovinos / Vacunos</div>
                        <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($cattleCount) }} cabezas</div>
                        <small class="text-muted">Control individual (arete/tatuaje)</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(108, 117, 125, 0.1); color: #6c757d;">
                        <i class="fas fa-horse-head fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-livestock-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Porcinos (Cerdos)</div>
                        <div class="h3 mb-0 fw-bold text-pink mt-1" style="color: #e83e8c;">{{ number_format($pigsCount) }} cabezas</div>
                        <small class="text-muted">Inicio, Crecimiento y Engorde</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(232, 62, 140, 0.1); color: #e83e8c;">
                        <i class="fas fa-piggy-bank fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card metric-livestock-card bg-white shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Cuyes y Aves</div>
                        <div class="h3 mb-0 fw-bold text-warning mt-1">{{ number_format($guineaPigsCount + $poultryCount) }}</div>
                        <small class="text-muted">{{ $guineaPigsCount }} cuyes | {{ $poultryCount }} aves</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(244, 161, 0, 0.1); color: #f4a100;">
                        <i class="fas fa-egg fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Actividad Productiva</label>
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
                    <label class="form-label small fw-bold text-muted mb-1">Especie Animal</label>
                    <select id="filter_species" class="form-select form-select-sm">
                        <option value="">-- Todas las Especies --</option>
                        <option value="CATTLE" {{ $selectedSpecies === 'CATTLE' ? 'selected' : '' }}>Bovino / Vacuno</option>
                        <option value="PIG" {{ $selectedSpecies === 'PIG' ? 'selected' : '' }}>Porcino / Cerdo</option>
                        <option value="GUINEA_PIG" {{ $selectedSpecies === 'GUINEA_PIG' ? 'selected' : '' }}>Cuyícola / Cuy</option>
                        <option value="POULTRY" {{ $selectedSpecies === 'POULTRY' ? 'selected' : '' }}>Avícola / Aves</option>
                        <option value="FISH_POND" {{ $selectedSpecies === 'FISH_POND' ? 'selected' : '' }}>Piscícola / Peces</option>
                        <option value="SHEEP_GOAT" {{ $selectedSpecies === 'SHEEP_GOAT' ? 'selected' : '' }}>Ovino / Caprino</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Estado</label>
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <option value="ACTIVE">Activo</option>
                        <option value="GESTATION">Gestación</option>
                        <option value="LACTATION">Lactancia</option>
                        <option value="FATTENING">Engorde</option>
                        <option value="QUARANTINE">Cuarentena</option>
                        <option value="SOLD">Vendido</option>
                        <option value="DECEASED">Baja / Mortalidad</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Buscar Arete / Identificador</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                        <input type="text" id="search_term" class="form-control" placeholder="Ej. VAC-042, LOTE-CERD-01...">
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
                <i class="fas fa-paw me-1 text-danger"></i> Inventario de Unidades y Lotes Pecuarios
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="livestock-table" style="width: 100%;">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th>Identificador / Raza</th>
                            <th>Especie</th>
                            <th>Modalidad / Cabezas</th>
                            <th>Peso (Ingreso / Actual)</th>
                            <th>Actividad (Fundo)</th>
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

<!-- Modal Unidad Pecuaria (Crear / Editar) -->
<div class="modal fade" id="modalLivestock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formLivestock" method="POST" action="{{ route('agrolivestock.livestock.store') }}">
            @csrf
            <div id="method_field"></div>
            <input type="hidden" id="unit_id" name="id">

            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title fs-6 fw-bold" id="modalLivestockTitle">
                        <i class="fas fa-paw me-2"></i>Registrar Unidad / Lote Pecuario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Actividad Productiva <span class="text-danger">*</span></label>
                            <select name="productive_activity_id" id="unit_activity_id" class="form-select form-select-sm" required>
                                <option value="">-- Seleccionar --</option>
                                @foreach($activities as $act)
                                    <option value="{{ $act->id }}">{{ $act->name }} ({{ $act->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Unidad Productiva / Sub-área (Opcional)</label>
                            <select name="productive_unit_id" id="unit_productive_unit_id" class="form-select form-select-sm">
                                <option value="">-- Ninguna --</option>
                                @foreach($productiveUnits as $pu)
                                    <option value="{{ $pu->id }}">{{ $pu->name }} ({{ $pu->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Especie <span class="text-danger">*</span></label>
                            <select name="species" id="unit_species" class="form-select form-select-sm" required>
                                <option value="CATTLE">Bovino / Vacuno</option>
                                <option value="PIG">Porcino / Cerdo</option>
                                <option value="GUINEA_PIG">Cuyícola / Cuy</option>
                                <option value="POULTRY">Avícola / Aves</option>
                                <option value="FISH_POND">Piscícola / Poza de Peces</option>
                                <option value="SHEEP_GOAT">Ovino / Caprino</option>
                                <option value="OTHER">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tipo de Registro <span class="text-danger">*</span></label>
                            <select name="tracking_type" id="unit_tracking_type" class="form-select form-select-sm" required>
                                <option value="INDIVIDUAL">Individual (Arete / Tatuaje)</option>
                                <option value="BATCH">Por Lote / Galpón / Poza</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Identificador / Arete / Código <span class="text-danger">*</span></label>
                            <input type="text" name="identifier_code" id="unit_identifier" class="form-control form-control-sm font-monospace" placeholder="Ej. ARETE-089 o LOTE-CERD-01" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Raza / Cruza</label>
                            <input type="text" name="breed" id="unit_breed" class="form-control form-control-sm" placeholder="Ej. Brown Swiss, Landrace, Tipo 1, Cobb 500">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Sexo / Composición</label>
                            <select name="sex" id="unit_sex" class="form-select form-select-sm" required>
                                <option value="FEMALE">Hembra</option>
                                <option value="MALE">Macho</option>
                                <option value="MIXED_BATCH">Lote Mixto</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Número de Cabezas <span class="text-danger">*</span></label>
                            <input type="number" name="batch_head_count" id="unit_head_count" class="form-control form-control-sm" value="1" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Fecha de Ingreso / Nacimiento <span class="text-danger">*</span></label>
                            <input type="date" name="birth_or_entry_date" id="unit_entry_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Peso al Ingreso (kg)</label>
                            <input type="number" step="0.1" name="entry_weight_kg" id="unit_entry_weight" class="form-control form-control-sm" placeholder="0.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Peso Actual (kg)</label>
                            <input type="number" step="0.1" name="current_weight_kg" id="unit_curr_weight" class="form-control form-control-sm" placeholder="0.0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Estado <span class="text-danger">*</span></label>
                            <select name="status" id="unit_status" class="form-select form-select-sm" required>
                                <option value="ACTIVE">Activo / En Producción</option>
                                <option value="GESTATION">Gestación</option>
                                <option value="LACTATION">Lactancia</option>
                                <option value="FATTENING">Engorde / Finalización</option>
                                <option value="QUARANTINE">Cuarentena</option>
                                <option value="SOLD">Vendido</option>
                                <option value="SLAUGHTERED">Beneficiado</option>
                                <option value="DECEASED">Baja / Mortalidad</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Observaciones / Genealogía</label>
                            <textarea name="notes" id="unit_notes" rows="2" class="form-control form-control-sm" placeholder="Padre, madre, procedencia, corrales o notas veterinarias..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold">
                        <i class="fas fa-save me-1"></i> Guardar Unidad
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
    const table = $('#livestock-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('agrolivestock.livestock.get') }}",
            data: function(d) {
                d.activity_id = $('#filter_activity').val();
                d.species = $('#filter_species').val();
                d.status = $('#filter_status').val();
                d.search_term = $('#search_term').val();
            }
        },
        columns: [
            { data: 'identifier_col', name: 'identifier_code' },
            { data: 'species_col', name: 'species' },
            { data: 'type_heads_col', name: 'batch_head_count' },
            { data: 'weight_col', name: 'current_weight_kg' },
            { data: 'activity_col', name: 'activity.name' },
            { data: 'status_col', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });

    $('#filter_activity, #filter_species, #filter_status').on('change', function() {
        table.ajax.reload();
    });

    $('#search_term').on('keyup', function() {
        table.ajax.reload();
    });

    $('#btn_reset_filters').on('click', function() {
        $('#filter_activity').val('');
        $('#filter_species').val('');
        $('#filter_status').val('');
        $('#search_term').val('');
        table.ajax.reload();
    });

    $('[data-bs-target="#modalLivestock"]').on('click', function() {
        $('#formLivestock').attr('action', "{{ route('agrolivestock.livestock.store') }}");
        $('#method_field').empty();
        $('#modalLivestockTitle').html('<i class="fas fa-paw me-2"></i>Registrar Unidad / Lote Pecuario');
        $('#formLivestock')[0].reset();
        $('#unit_id').val('');
    });

    $(document).on('click', '.btn-edit-unit', function() {
        const d = $(this).data();
        $('#formLivestock').attr('action', "/agrolivestock/livestock/" + d.id);
        $('#method_field').html('@method("PUT")');
        $('#modalLivestockTitle').html('<i class="fas fa-edit me-2"></i>Editar Unidad: ' + d.code);

        $('#unit_id').val(d.id);
        $('#unit_activity_id').val(d.activity);
        $('#unit_productive_unit_id').val(d.punit);
        $('#unit_species').val(d.species);
        $('#unit_breed').val(d.breed);
        $('#unit_tracking_type').val(d.type);
        $('#unit_identifier').val(d.code);
        $('#unit_head_count').val(d.heads);
        $('#unit_sex').val(d.sex);
        $('#unit_entry_date').val(d.date);
        $('#unit_entry_weight').val(d.entryWeight);
        $('#unit_curr_weight').val(d.currWeight);
        $('#unit_status').val(d.status);
        $('#unit_notes').val(d.notes);

        $('#modalLivestock').modal('show');
    });

    $(document).on('click', '.btn-delete-unit', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar Unidad Pecuaria?',
            text: 'Se eliminará el animal o lote del inventario.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('agrolivestock.livestock.delete') }}",
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
                    }
                });
            }
        });
    });
});
</script>
@endsection
