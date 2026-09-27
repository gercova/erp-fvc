@extends('admin.layout')
@section('title', 'Preventas y Pedidos de Actividades Productivas')

@section('styles')
    <style>
        .role-segmented-control {
            background-color: #f1f3f5;
            padding: 4px;
            border-radius: 8px;
            display: inline-flex;
            gap: 4px;
        }

        .role-segmented-control .btn-check + .btn {
            border: 0;
            border-radius: 6px;
            color: #495057;
            font-weight: 500;
            padding: 6px 14px;
            background: transparent;
            transition: all 0.15s ease;
        }

        .role-segmented-control .btn-check:checked + .btn {
            background-color: #ffffff;
            color: #0d6efd;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
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
                    <li class="breadcrumb-item">Comercialización</li>
                    <li class="breadcrumb-item active" aria-current="page">Preventas y Pedidos</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Preventas y Pedidos de Cosecha
            </h1>
            <p class="text-muted small mb-0">Gestión de órdenes previas de clientes y reservas antes de la emisión del comprobante formal</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalOrder">
                <i class="fas fa-plus-circle me-1"></i> Nueva Preventa / Pedido
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
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Preventas</div>
                        <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($totalOrders) }}</div>
                        <small class="text-muted">Órdenes registradas</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(0, 97, 242, 0.1); color: var(--bs-primary);">
                        <i class="fas fa-shopping-basket fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Pendientes / En Espera</div>
                        <div class="h3 mb-0 fw-bold text-warning mt-1">{{ number_format($pendingOrders) }}</div>
                        <small class="text-warning">Por cosechar o entregar</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-hourglass-half fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Confirmadas / Listas</div>
                        <div class="h3 mb-0 fw-bold text-info mt-1">{{ number_format($confirmedOrders) }}</div>
                        <small class="text-info">Con adelanto o pactadas</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                        <i class="fas fa-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Monto Total Comprometido</div>
                        <div class="h3 mb-0 fw-bold text-success mt-1">S/ {{ number_format($totalAmount, 2) }}</div>
                        <small class="text-muted">Valor de preventas activas</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="fas fa-coins fs-4"></i>
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

            <!-- Segmented status controls -->
            <div class="role-segmented-control">
                <input type="radio" class="btn-check filter-status" name="filter_status" id="st_all" value="" checked>
                <label class="btn" for="st_all">Todos</label>

                <input type="radio" class="btn-check filter-status" name="filter_status" id="st_pending" value="pending">
                <label class="btn" for="st_pending">Pendientes</label>

                <input type="radio" class="btn-check filter-status" name="filter_status" id="st_confirmed" value="confirmed">
                <label class="btn" for="st_confirmed">Confirmados</label>

                <input type="radio" class="btn-check filter-status" name="filter_status" id="st_delivered" value="delivered">
                <label class="btn" for="st_delivered">Entregados</label>

                <input type="radio" class="btn-check filter-status" name="filter_status" id="st_invoiced" value="invoiced">
                <label class="btn" for="st_invoiced">Facturados</label>
            </div>

            <div class="input-group input-group-sm" style="width: 220px;">
                <input type="text" id="filter_search" class="form-control" placeholder="Buscar pedido / cliente...">
                <button class="btn btn-outline-secondary" type="button" id="btn_search"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="orders_table" style="width: 100%;">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Producto / Actividad</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Monto Total</th>
                            <th>Fechas</th>
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

<!-- Modal: Create Activity Order -->
<div class="modal fade" id="modalOrder" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('commercialization.orders.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-shopping-cart me-2 text-primary"></i>Registrar Preventa / Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Cliente (Padrón Único) <span class="text-danger">*</span></label>
                            <select name="client_id" class="form-select select2" required>
                                <option value="">-- Seleccionar Cliente --</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">
                                        {{ $c->nombres }} ({{ $c->nro_documento ?? 'Sin DNI' }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Reutiliza el catálogo de clientes central del ERP</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Actividad Productiva <span class="text-danger">*</span></label>
                            <select name="productive_activity_id" class="form-select" required>
                                @foreach($activities as $act)
                                    <option value="{{ $act->id }}" {{ $selectedActivityId == $act->id ? 'selected' : '' }}>
                                        {{ $act->name }} ({{ $act->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Producto Producido <span class="text-danger">*</span></label>
                        <select name="produced_item_id" id="order_item_sel" class="form-select" required>
                            <option value="">-- Seleccionar Producto --</option>
                            @foreach($producedItems as $p)
                                <option value="{{ $p->id }}" data-price="{{ $p->standard_cost }}" data-unit="{{ $p->unit_of_measurement }}">
                                    {{ $p->name }} ({{ $p->unit_of_measurement }}) - Costo Ref: S/ {{ number_format($p->standard_cost, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Cantidad Pedida <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" min="0.001" name="quantity" id="order_qty" class="form-control" required placeholder="0.000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Precio Unitario Acordado (S/) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="unit_price" id="order_price" class="form-control" required value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Total Acordado (S/)</label>
                            <input type="number" step="0.01" min="0" name="total_amount" id="order_total" class="form-control fw-bold text-primary" readonly value="0.00">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Adelanto / Seña Recibida (S/)</label>
                            <input type="number" step="0.01" min="0" name="advance_payment" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Fecha del Pedido <span class="text-danger">*</span></label>
                            <input type="date" name="order_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Fecha Estimada de Entrega</label>
                            <input type="date" name="expected_delivery_date" class="form-control">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Notas / Compromisos de Entrega</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Lugar de entrega en parcela o campus, contacto, etc..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Guardar Preventa</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Update Order Status -->
<div class="modal fade" id="modalStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form id="form_update_status">
            @csrf
            <input type="hidden" id="status_order_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Cambiar Estado de Pedido</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2 text-muted small" id="status_order_code_disp"></div>
                    <label class="form-label small fw-bold">Nuevo Estado:</label>
                    <select id="new_status_select" class="form-select">
                        <option value="pending">Pendiente</option>
                        <option value="confirmed">Confirmado</option>
                        <option value="delivered">Entregado</option>
                        <option value="invoiced">Facturado / Liquidado</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Actualizar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    var table = $('#orders_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('commercialization.orders.get') }}",
            data: function(d) {
                d.activity_id = $('#filter_activity').val();
                d.status = $('input[name="filter_status"]:checked').val();
                d.search_term = $('#filter_search').val();
            }
        },
        columns: [
            { data: 'order_code_col', name: 'order_code' },
            { data: 'client_col', name: 'client.nombres' },
            { data: 'item_col', name: 'producedItem.name' },
            { data: 'quantity_col', name: 'quantity' },
            { data: 'price_col', name: 'unit_price' },
            { data: 'total_col', name: 'total_amount' },
            { data: 'date_col', name: 'order_date' },
            { data: 'status_col', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        },
        order: [[0, 'desc']],
        pageLength: 15
    });

    $('#filter_activity').on('change', function() { table.ajax.reload(); });
    $('.filter-status').on('change', function() { table.ajax.reload(); });
    $('#filter_search').on('keyup', function(e) { if (e.keyCode === 13) table.ajax.reload(); });
    $('#btn_search').on('click', function() { table.ajax.reload(); });

    // Calculate total order amount
    function calcOrderTotal() {
        var q = parseFloat($('#order_qty').val()) || 0;
        var p = parseFloat($('#order_price').val()) || 0;
        $('#order_total').val((q * p).toFixed(2));
    }
    $('#order_qty, #order_price').on('input', calcOrderTotal);

    $('#order_item_sel').on('change', function() {
        var price = $(this).find(':selected').data('price') || 0;
        $('#order_price').val(parseFloat(price).toFixed(2));
        calcOrderTotal();
    });

    // Open change status modal
    $(document).on('click', '.btn-update-status', function() {
        var id = $(this).data('id');
        var code = $(this).data('code');
        var st = $(this).data('status');
        $('#status_order_id').val(id);
        $('#status_order_code_disp').text('Pedido: ' + code);
        $('#new_status_select').val(st);
        $('#modalStatus').modal('show');
    });

    $('#form_update_status').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('commercialization.orders.update_status') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: $('#status_order_id').val(),
                status: $('#new_status_select').val()
            },
            success: function(res) {
                $('#modalStatus').modal('hide');
                table.ajax.reload();
            }
        });
    });

    // Delete order
    $(document).on('click', '.btn-delete-order', function() {
        var id = $(this).data('id');
        if (!confirm('¿Desea eliminar esta preventa?')) return;

        $.ajax({
            url: "{{ route('commercialization.orders.delete') }}",
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
