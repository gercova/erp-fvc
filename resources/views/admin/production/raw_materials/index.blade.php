@extends('admin.layout')
@section('title', 'Catálogo Abierto de Insumos y Materias Primas')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Producción</li>
                    <li class="breadcrumb-item active" aria-current="page">Insumos y Materias Primas</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-boxes me-2 text-primary"></i>Catálogo Abierto de Insumos y Materias Primas
            </h1>
            <p class="text-muted small mb-0">Gestión de insumos agrícolas, pecuarios y forestales sin restricciones cerradas de SBN</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.input_movements.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-exchange-alt me-1"></i> Ver Movimientos / Consumos
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMaterial">
                <i class="fas fa-plus-circle me-1"></i> Nuevo Insumo
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Insumos Registrados</div>
                        <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($totalItems) }}</div>
                        <small class="text-muted">En el catálogo institucional</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(0, 97, 242, 0.1); color: var(--bs-primary);">
                        <i class="fas fa-tags fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Líneas / Categorías</div>
                        <div class="h3 mb-0 fw-bold text-success mt-1">{{ number_format($categoriesCount) }}</div>
                        <small class="text-muted">Fertilizantes, alimentos, fármacos, etc.</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="fas fa-filter fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Insumos Activos</div>
                        <div class="h3 mb-0 fw-bold text-primary mt-1">{{ number_format($activeItems) }}</div>
                        <small class="text-primary">Disponibles para imputar en campo</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                        <i class="fas fa-check-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label small fw-bold text-muted mb-0 me-1">Categoría:</label>
                <select id="filter_category" class="form-select form-select-sm" style="min-width: 220px;">
                    <option value="">-- Todas las Categorías --</option>
                    <option value="fertilizer">Fertilizantes / Abonos</option>
                    <option value="seed">Semillas / Plantones</option>
                    <option value="medication">Medicamentos / Fármacos</option>
                    <option value="feed">Alimento / Balanceado</option>
                    <option value="insecticide_fungicide">Insecticidas / Fungicidas</option>
                    <option value="other">Otros / Suministros</option>
                </select>
            </div>
            <div class="input-group input-group-sm" style="width: 250px;">
                <input type="text" id="filter_search" class="form-control" placeholder="Buscar insumo...">
                <button class="btn btn-outline-secondary" type="button" id="btn_search"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="materials_table" style="width: 100%;">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th>Categoría</th>
                            <th>Insumo / Descripción</th>
                            <th>Unidad</th>
                            <th>Costo Ref.</th>
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

<!-- Modal: Create / Edit Raw Material -->
<div class="modal fade" id="modalMaterial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="form_material" action="{{ route('production.raw_materials.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_method" id="material_method" value="POST">
            <input type="hidden" id="material_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modal_material_title"><i class="fas fa-box-open me-2 text-primary"></i>Nuevo Insumo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre del Insumo <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="mat_name" class="form-control" required placeholder="Ej: Fertilizante Urea 46%, Concentrado Inicio Truchas">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Categoría <span class="text-danger">*</span></label>
                            <select name="category" id="mat_category" class="form-select" required>
                                <option value="fertilizer">Fertilizante / Abono</option>
                                <option value="seed">Semilla / Plantón</option>
                                <option value="medication">Medicamento / Fármaco</option>
                                <option value="feed">Alimento / Balanceado</option>
                                <option value="insecticide_fungicide">Insecticida / Fungicida</option>
                                <option value="other">Otro / Suministro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Unidad de Medida <span class="text-danger">*</span></label>
                            <input type="text" name="unit_of_measurement" id="mat_unit" class="form-control" required placeholder="Ej: KG, SACO, LITRO">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Costo Referencial Estimado (S/)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">S/</span>
                            <input type="number" step="0.01" min="0" name="default_unit_cost" id="mat_cost" class="form-control" value="0.00">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Descripción / Especificación Técnica</label>
                        <textarea name="description" id="mat_desc" class="form-control" rows="2" placeholder="Ficha técnica, dosificación o proveedor habitual..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Guardar Insumo</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    var table = $('#materials_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('production.raw_materials.get') }}",
            data: function(d) {
                d.category = $('#filter_category').val();
                d.search_term = $('#filter_search').val();
            }
        },
        columns: [
            { data: 'category_col', name: 'category' },
            { data: 'name_col', name: 'name' },
            { data: 'unit_col', name: 'unit_of_measurement' },
            { data: 'cost_col', name: 'default_unit_cost' },
            { data: 'status_col', name: 'is_active' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        },
        order: [[1, 'asc']],
        pageLength: 15
    });

    $('#filter_category').on('change', function() { table.ajax.reload(); });
    $('#filter_search').on('keyup', function(e) { if (e.keyCode === 13) table.ajax.reload(); });
    $('#btn_search').on('click', function() { table.ajax.reload(); });

    // Open edit modal
    $(document).on('click', '.btn-edit-material', function() {
        var id = $(this).data('id');
        $('#modal_material_title').html('<i class="fas fa-edit me-2 text-primary"></i>Editar Insumo');
        $('#form_material').attr('action', '/production/raw-materials/' + id);
        $('#material_method').val('PUT');
        $('#mat_name').val($(this).data('name'));
        $('#mat_category').val($(this).data('category'));
        $('#mat_unit').val($(this).data('unit'));
        $('#mat_cost').val($(this).data('cost'));
        $('#mat_desc').val($(this).data('desc'));
        $('#modalMaterial').modal('show');
    });

    // Reset modal on close
    $('#modalMaterial').on('hidden.bs.modal', function () {
        $('#modal_material_title').html('<i class="fas fa-box-open me-2 text-primary"></i>Nuevo Insumo');
        $('#form_material').attr('action', "{{ route('production.raw_materials.store') }}");
        $('#material_method').val('POST');
        $('#form_material')[0].reset();
    });

    // Delete material
    $(document).on('click', '.btn-delete-material', function() {
        var id = $(this).data('id');
        if (!confirm('¿Desea eliminar este insumo del catálogo?')) return;

        $.ajax({
            url: "{{ route('production.raw_materials.delete') }}",
            type: "POST",
            data: { _token: "{{ csrf_token() }}", id: id },
            success: function(res) {
                table.ajax.reload();
            }
        });
    });
});
</script>
@endsection
