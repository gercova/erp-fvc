@extends('admin.layout')
@section('title', 'Movimientos y Consumos de Insumos')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Producción</li>
                    <li class="breadcrumb-item active" aria-current="page">Movimientos de Insumos</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-dolly-flatbed me-2 text-primary"></i>Movimientos y Consumos de Insumos
            </h1>
            <p class="text-muted small mb-0">Imputación estricta de costos de insumos por actividad y proyecto (aislamiento de costos sin mezclas)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.raw_materials.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-boxes me-1"></i> Catálogo de Insumos
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMovement">
                <i class="fas fa-plus-circle me-1"></i> Registrar Movimiento
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
                        <div class="text-muted small text-uppercase fw-semibold">Consumo en Campo (Salidas)</div>
                        <div class="h3 mb-0 fw-bold text-danger mt-1">S/ {{ number_format($totalOutflowCost, 2) }}</div>
                        <small class="text-muted">Costo imputado al proyecto</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-arrow-circle-down fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Ingresos / Compras Asignadas</div>
                        <div class="h3 mb-0 fw-bold text-success mt-1">S/ {{ number_format($totalInflowCost, 2) }}</div>
                        <small class="text-muted">Insumos ingresados por adquisición</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="fas fa-arrow-circle-up fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Movimientos</div>
                        <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($movementsCount) }}</div>
                        <small class="text-primary">Registros de trazabilidad</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(0, 97, 242, 0.1); color: var(--bs-primary);">
                        <i class="fas fa-receipt fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label small fw-bold text-muted mb-0 me-1">Proyecto / Actividad:</label>
                <select id="filter_activity" class="form-select form-select-sm" style="min-width: 240px;">
                    <option value="">-- Todos los Proyectos --</option>
                    @foreach($activities as $act)
                        <option value="{{ $act->id }}" {{ $selectedActivityId == $act->id ? 'selected' : '' }}>
                            {{ $act->name }} ({{ $act->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <select id="filter_type" class="form-select form-select-sm">
                    <option value="">-- Todos los Tipos --</option>
                    <option value="outflow_consumption">Consumo en Campo</option>
                    <option value="inflow_purchase">Ingreso / Compra</option>
                </select>
                <div class="input-group input-group-sm" style="width: 220px;">
                    <input type="text" id="filter_search" class="form-control" placeholder="Buscar insumo...">
                    <button class="btn btn-outline-secondary" type="button" id="btn_search"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="movements_table" style="width: 100%;">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Insumo</th>
                            <th>Proyecto / Campaña</th>
                            <th>Cantidad</th>
                            <th>Costo Unit.</th>
                            <th>Total (S/)</th>
                            <th>Origen</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Register Input Movement -->
<div class="modal fade" id="modalMovement" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('production.input_movements.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-dolly-flatbed me-2 text-primary"></i>Registrar Movimiento de Insumo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 small border-0 mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Asegúrese de seleccionar el <strong>Proyecto / Actividad</strong> correcto para evitar mezclar costos entre proyectos diferentes.
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tipo de Movimiento <span class="text-danger">*</span></label>
                            <select name="movement_type" id="input_mov_type" class="form-select" required>
                                <option value="outflow_consumption">Consumo en Campo (Salida de Insumo)</option>
                                <option value="inflow_purchase">Ingreso por Compra Directa</option>
                                <option value="adjustment">Ajuste de Inventario</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Proyecto / Actividad Productiva <span class="text-danger">*</span></label>
                            <select name="productive_activity_id" class="form-select" required>
                                @foreach($activities as $act)
                                    <option value="{{ $act->id }}" {{ $selectedActivityId == $act->id ? 'selected' : '' }}>
                                        {{ $act->name }} ({{ $act->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Insumo / Materia Prima <span class="text-danger">*</span></label>
                            <select name="production_raw_material_id" id="sel_raw_material" class="form-select" required>
                                <option value="">-- Seleccione Insumo --</option>
                                @foreach($rawMaterials as $mat)
                                    <option value="{{ $mat->id }}" data-cost="{{ $mat->default_unit_cost }}" data-unit="{{ $mat->unit_of_measurement }}">
                                        {{ $mat->name }} ({{ $mat->category_label }}) - S/ {{ number_format($mat->default_unit_cost, 2) }}/{{ $mat->unit_of_measurement }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Campaña / Plan (Opcional)</label>
                            <select name="production_campaign_id" class="form-select">
                                <option value="">-- Sin vincular a campaña --</option>
                                @foreach($campaigns as $camp)
                                    <option value="{{ $camp->id }}">{{ $camp->campaign_code }} - {{ $camp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Fecha del Movimiento <span class="text-danger">*</span></label>
                            <input type="date" name="movement_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Cantidad <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.001" min="0.001" name="quantity" id="input_qty" class="form-control" required placeholder="0.000">
                                <span class="input-group-text bg-light" id="span_unit">UNID</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Costo Unitario (S/) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">S/</span>
                                <input type="number" step="0.01" min="0" name="unit_cost" id="input_cost" class="form-control" required value="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Costo Total Imputado (S/)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">S/</span>
                                <input type="number" step="0.01" min="0" name="total_cost" id="input_total" class="form-control fw-bold text-primary" readonly value="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vincular a Compra Registrada en ERP (Opcional)</label>
                            <select name="buy_id" class="form-select">
                                <option value="">-- No vincular a compra formal --</option>
                                @foreach($recentBuys as $b)
                                    <option value="{{ $b->id }}">
                                        Compra #{{ $b->id }} - {{ $b->serie }}-{{ $b->correlativo }} (S/ {{ number_format($b->total, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Observaciones / Destino de Campo</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Ej: Aplicado en parcela 2, desinfección preventiva..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Guardar Movimiento</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    var table = $('#movements_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('production.input_movements.get') }}",
            data: function(d) {
                d.activity_id = $('#filter_activity').val();
                d.movement_type = $('#filter_type').val();
                d.search_term = $('#filter_search').val();
            }
        },
        columns: [
            { data: 'date_col', name: 'movement_date' },
            { data: 'type_col', name: 'movement_type' },
            { data: 'material_col', name: 'rawMaterial.name' },
            { data: 'activity_col', name: 'activity.name' },
            { data: 'quantity_col', name: 'quantity' },
            { data: 'unit_cost_col', name: 'unit_cost' },
            { data: 'total_cost_col', name: 'total_cost' },
            { data: 'source_col', name: 'buy_id' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        },
        order: [[0, 'desc']],
        pageLength: 15
    });

    $('#filter_activity, #filter_type').on('change', function() { table.ajax.reload(); });
    $('#filter_search').on('keyup', function(e) { if (e.keyCode === 13) table.ajax.reload(); });
    $('#btn_search').on('click', function() { table.ajax.reload(); });

    // When raw material changes, fill default cost and unit
    $('#sel_raw_material').on('change', function() {
        var opt = $(this).find(':selected');
        var cost = opt.data('cost') || 0;
        var unit = opt.data('unit') || 'UNID';
        $('#input_cost').val(parseFloat(cost).toFixed(2));
        $('#span_unit').text(unit);
        calcTotal();
    });

    function calcTotal() {
        var q = parseFloat($('#input_qty').val()) || 0;
        var c = parseFloat($('#input_cost').val()) || 0;
        $('#input_total').val((q * c).toFixed(2));
    }
    $('#input_qty, #input_cost').on('input', calcTotal);

    // Delete movement
    $(document).on('click', '.btn-delete-movement', function() {
        var id = $(this).data('id');
        if (!confirm('¿Desea anular este movimiento de insumo?')) return;

        $.ajax({
            url: "{{ route('production.input_movements.delete') }}",
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
