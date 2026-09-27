@extends('admin.layout')
@section('title', 'Registro de Cosechas y Rendimientos de Producción')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Producción</li>
                    <li class="breadcrumb-item active" aria-current="page">Cosechas y Rendimientos</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-seedling me-2 text-primary"></i>Registro de Cosechas y Rendimientos de Producción
            </h1>
            <p class="text-muted small mb-0">Control de salidas de campo, pesajes y entrada física a almacén institucional</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.profitability.index') }}" class="btn btn-outline-success">
                <i class="fas fa-chart-line me-1"></i> Reporte de Rentabilidad
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalHarvest">
                <i class="fas fa-plus-circle me-1"></i> Registrar Cosecha
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
        <div class="col-md-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Eventos de Cosecha / Salida</div>
                        <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($totalHarvestsCount) }}</div>
                        <small class="text-muted">Partes de cosecha registrados</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(0, 97, 242, 0.1); color: var(--bs-primary);">
                        <i class="fas fa-calendar-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Volumen Total Cosechado</div>
                        <div class="h3 mb-0 fw-bold text-success mt-1">{{ number_format($totalQuantity, 2) }}</div>
                        <small class="text-success">Rendimiento acumulado en unidades / kg</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="fas fa-weight-hanging fs-4"></i>
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
                <select id="filter_activity" class="form-select form-select-sm" style="min-width: 240px;">
                    <option value="">-- Todas las Actividades --</option>
                    @foreach($activities as $act)
                        <option value="{{ $act->id }}" {{ $selectedActivityId == $act->id ? 'selected' : '' }}>
                            {{ $act->name }} ({{ $act->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="input-group input-group-sm" style="width: 240px;">
                <input type="text" id="filter_search" class="form-control" placeholder="Buscar cosecha...">
                <button class="btn btn-outline-secondary" type="button" id="btn_search"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="harvests_table" style="width: 100%;">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th>Fecha</th>
                            <th>Producto Obtenido</th>
                            <th>Campaña / Lote</th>
                            <th>Cantidad</th>
                            <th>Calidad</th>
                            <th>Almacén Destino</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Register Harvest -->
<div class="modal fade" id="modalHarvest" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('production.harvests.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-seedling me-2 text-primary"></i>Registrar Cosecha / Rendimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Producto Obtenido <span class="text-danger">*</span></label>
                            <select name="produced_item_id" class="form-select" required>
                                <option value="">-- Seleccionar Producto --</option>
                                @foreach($producedItems as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->name }} ({{ $item->unit_of_measurement }}) - {{ $item->activity->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Campaña / Plan de Producción <span class="text-danger">*</span></label>
                            <select name="production_campaign_id" class="form-select" required>
                                <option value="">-- Seleccionar Campaña --</option>
                                @foreach($campaigns as $camp)
                                    <option value="{{ $camp->id }}">
                                        {{ $camp->campaign_code }} - {{ $camp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Fecha de Cosecha <span class="text-danger">*</span></label>
                            <input type="date" name="harvest_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Cantidad Obtenida <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" min="0.001" name="quantity" class="form-control" required placeholder="0.000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Calidad / Grado</label>
                            <select name="quality_grade" class="form-select">
                                <option value="PRIMERA">Primera / Exportación</option>
                                <option value="ESTANDAR" selected>Estándar / Comercial</option>
                                <option value="SEGUNDA">Segunda</option>
                                <option value="DESCARTE">Descarte / Merma</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Almacén Destino (Actualizar Stock)</label>
                            <select name="warehouse_id" class="form-select">
                                <option value="">-- Sin ingreso a almacén físico --</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}">{{ $wh->descripcion }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Si el producto está publicado en el POS, incrementará el stock del almacén</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Costo Unitario Calculado (S/)</label>
                            <input type="number" step="0.01" min="0" name="unit_cost_calculated" class="form-control" value="0.00">
                            <small class="text-muted">Costo estimado de producción por unidad</small>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Observaciones / Detalles de Pesaje</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Ticket de balanza, condiciones de recolección..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Guardar Registro de Cosecha</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    var table = $('#harvests_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('production.harvests.get') }}",
            data: function(d) {
                d.activity_id = $('#filter_activity').val();
                d.search_term = $('#filter_search').val();
            }
        },
        columns: [
            { data: 'date_col', name: 'harvest_date' },
            { data: 'item_col', name: 'producedItem.name' },
            { data: 'campaign_col', name: 'campaign.name' },
            { data: 'quantity_col', name: 'quantity' },
            { data: 'grade_col', name: 'quality_grade' },
            { data: 'warehouse_col', name: 'warehouse.descripcion' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        },
        order: [[0, 'desc']],
        pageLength: 15
    });

    $('#filter_activity').on('change', function() { table.ajax.reload(); });
    $('#filter_search').on('keyup', function(e) { if (e.keyCode === 13) table.ajax.reload(); });
    $('#btn_search').on('click', function() { table.ajax.reload(); });

    $(document).on('click', '.btn-delete-harvest', function() {
        var id = $(this).data('id');
        if (!confirm('¿Desea eliminar este registro de cosecha?')) return;

        $.ajax({
            url: "{{ route('production.harvests.delete') }}",
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
