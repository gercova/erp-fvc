@extends('admin.layout')
@section('title', 'Catálogo de Productos Producidos por Actividad')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Producción</li>
                    <li class="breadcrumb-item active" aria-current="page">Productos Producidos</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-boxes-packing me-2 text-primary"></i>Productos Terminados de Actividades Productivas
            </h1>
            <p class="text-muted small mb-0">Catálogo de bienes y cosechas por actividad con publicación directa al POS central</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.harvests.index') }}" class="btn btn-outline-success">
                <i class="fas fa-seedling me-1"></i> Ver Registro de Cosechas
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProducedItem">
                <i class="fas fa-plus-circle me-1"></i> Nuevo Producto Producido
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
                        <div class="text-muted small text-uppercase fw-semibold">Total Productos Producidos</div>
                        <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($totalProduced) }}</div>
                        <small class="text-muted">Líneas de producción registradas</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(0, 97, 242, 0.1); color: var(--bs-primary);">
                        <i class="fas fa-cubes fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Publicados en Catálogo POS</div>
                        <div class="h3 mb-0 fw-bold text-success mt-1">{{ number_format($publishedCount) }}</div>
                        <small class="text-success">Listos para emitir boletas y notas</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="fas fa-check-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Sin Publicar a Ventas</div>
                        <div class="h3 mb-0 fw-bold text-secondary mt-1">{{ number_format($unpublishedCount) }}</div>
                        <small class="text-muted">Pendientes de fijar precio de venta</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(108, 117, 125, 0.1); color: #6c757d;">
                        <i class="fas fa-clock fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label small fw-bold text-muted mb-0 me-1">Actividad:</label>
                <select id="filter_activity" class="form-select form-select-sm" style="min-width: 220px;">
                    <option value="">-- Todas las Actividades --</option>
                    @foreach($activities as $act)
                        <option value="{{ $act->id }}" {{ $selectedActivityId == $act->id ? 'selected' : '' }}>
                            {{ $act->name }} ({{ $act->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <select id="filter_published" class="form-select form-select-sm">
                    <option value="">-- Estado de Publicación --</option>
                    <option value="1">Publicados en POS</option>
                    <option value="0">No Publicados</option>
                </select>
                <div class="input-group input-group-sm" style="width: 220px;">
                    <input type="text" id="filter_search" class="form-control" placeholder="Buscar producto...">
                    <button class="btn btn-outline-secondary" type="button" id="btn_search"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle w-100" id="produced_items_table">
                    <thead class="table-light">
                        <tr>
                            <th>Actividad</th>
                            <th>Producto / Servicio</th>
                            <th>Unidad</th>
                            <th>Costo Estándar</th>
                            <th>Estado en POS</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Register Produced Item -->
<div class="modal fade" id="modalProducedItem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('production.produced_items.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-cube me-2 text-primary"></i>Registrar Producto Producido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Actividad Productiva <span class="text-danger">*</span></label>
                        <select name="productive_activity_id" class="form-select" required>
                            @foreach($activities as $act)
                                <option value="{{ $act->id }}" {{ $selectedActivityId == $act->id ? 'selected' : '' }}>
                                    {{ $act->name }} ({{ $act->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre del Producto / Cosecha <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Ej: Racimo de Palma Aceitera, Queso Fresco, Leche Fresca, Cuy Beneficiado">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Unidad de Medida <span class="text-danger">*</span></label>
                            <input type="text" name="unit_of_measurement" class="form-control" required placeholder="KG, LITRO, UNIDAD, RACIMO">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Costo Estándar Proyectado (S/)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">S/</span>
                                <input type="number" step="0.01" min="0" name="standard_cost" class="form-control" value="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Descripción / Detalles Técnicos</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Especificaciones de calidad, presentación o destino..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Guardar Producto</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Publish to Sales Catalog -->
<div class="modal fade" id="modalPublish" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="form_publish">
            @csrf
            <input type="hidden" name="id" id="publish_item_id">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-upload me-2"></i>Publicar al Catálogo Central de Ventas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Esta acción registrará el producto en el catálogo general de <strong>ERP-FVC (products)</strong>, permitiendo venderlo de forma inmediata en el POS, Notas de Venta y Facturación Electrónica SUNAT (Boletas/Facturas).
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Producto a Publicar:</label>
                        <div id="publish_product_name" class="fw-bold fs-6 text-dark"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Precio de Venta al Público (S/) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">S/</span>
                            <input type="number" step="0.01" min="0.01" name="sale_price" id="publish_sale_price" class="form-control fs-5 fw-bold text-success" required placeholder="0.00">
                        </div>
                        <small class="text-muted">Precio con el que figurará en mostrador o ventas corporativas</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-semibold"><i class="fas fa-check me-1"></i> Confirmar Publicación</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    var table = $('#produced_items_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('production.produced_items.get') }}",
            data: function(d) {
                d.activity_id = $('#filter_activity').val();
                d.is_published = $('#filter_published').val();
                d.search_term = $('#filter_search').val();
            }
        },
        columns: [
            { data: 'activity_col', name: 'activity.name' },
            { data: 'name_col', name: 'name' },
            { data: 'unit_col', name: 'unit_of_measurement' },
            { data: 'cost_col', name: 'standard_cost' },
            { data: 'status_col', name: 'is_published_to_sales' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        },
        order: [[1, 'asc']],
        pageLength: 15
    });

    $('#filter_activity, #filter_published').on('change', function() { table.ajax.reload(); });
    $('#filter_search').on('keyup', function(e) { if (e.keyCode === 13) table.ajax.reload(); });
    $('#btn_search').on('click', function() { table.ajax.reload(); });

    // Open publish modal
    $(document).on('click', '.btn-publish-product', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var cost = $(this).data('cost') || '0.00';
        $('#publish_item_id').val(id);
        $('#publish_product_name').text(name);
        $('#publish_sale_price').val(cost);
        $('#modalPublish').modal('show');
    });

    // Submit publish
    $('#form_publish').on('submit', function(e) {
        e.preventDefault();
        var data = {
            _token: "{{ csrf_token() }}",
            id: $('#publish_item_id').val(),
            sale_price: $('#publish_sale_price').val()
        };

        $.ajax({
            url: "{{ route('production.produced_items.publish') }}",
            type: "POST",
            data: data,
            success: function(res) {
                $('#modalPublish').modal('hide');
                table.ajax.reload();
                alert(res.message);
            },
            error: function(err) {
                alert('Ocurrió un error al publicar el producto.');
            }
        });
    });

    // Delete item
    $(document).on('click', '.btn-delete-item', function() {
        var id = $(this).data('id');
        if (!confirm('¿Desea eliminar este producto de la actividad?')) return;

        $.ajax({
            url: "{{ route('production.produced_items.delete') }}",
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
