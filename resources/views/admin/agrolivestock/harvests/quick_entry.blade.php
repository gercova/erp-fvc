@extends('admin.layout')
@section('title', 'Carga Rápida Unificada de Cosechas')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Agropecuario y Forestal</li>
                    <li class="breadcrumb-item active" aria-current="page">Carga Rápida de Cosechas</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-truck-loading me-2 text-success"></i>Carga Rápida Unificada de Cosechas
            </h1>
            <p class="text-muted small mb-0">Alimenta directamente el registro de productos terminados en Bloque C, evitando duplicidad de digitación e incrementando stock.</p>
        </div>
        <div>
            <a href="{{ route('production.harvests.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-boxes me-1"></i> Ver Catálogo Bloque C
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Quick Entry Form Card -->
    <div class="card shadow-sm border-0 bg-white mb-4">
        <div class="card-header bg-success text-white py-2">
            <h6 class="m-0 fw-bold">
                <i class="fas fa-pen-nib me-2"></i>Registrar Cosecha de Campo o Despacho de Vivero
            </h6>
        </div>
        <div class="card-body p-3">
            <form action="{{ route('agrolivestock.harvests.store') }}" method="POST">
                @csrf
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Actividad Productiva <span class="text-danger">*</span></label>
                        <select name="productive_activity_id" id="quick_activity_id" class="form-select form-select-sm" required>
                            @foreach($activities as $act)
                                <option value="{{ $act->id }}" {{ $selectedActivityId == $act->id ? 'selected' : '' }}>
                                    {{ $act->name }} ({{ $act->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Procedencia: Parcela Agrícola</label>
                        <select name="agricultural_plot_id" id="quick_plot_id" class="form-select form-select-sm">
                            <option value="">-- Ninguna / No Aplica --</option>
                            @foreach($plots as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }}) - {{ $p->area_hectares }} ha</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Procedencia: Vivero Forestal</label>
                        <select name="agricultural_nursery_id" id="quick_nursery_id" class="form-select form-select-sm">
                            <option value="">-- Ninguno / No Aplica --</option>
                            @foreach($nurseries as $nur)
                                <option value="{{ $nur->id }}">{{ $nur->species_name }} ({{ $nur->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Campaña / Zafra (Bloque C) <span class="text-danger">*</span></label>
                        <select name="production_campaign_id" class="form-select form-select-sm" required>
                            @foreach($campaigns as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->activity->name ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Producto Obtenido (Catálogo Bloque C) <span class="text-danger">*</span></label>
                        <select name="produced_item_id" class="form-select form-select-sm" required>
                            <option value="">-- Seleccionar Producto --</option>
                            @foreach($producedItems as $item)
                                <option value="{{ $item->id }}">
                                    {{ $item->name }} ({{ $item->unit_of_measurement }}) - {{ $item->activity->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Fecha de Cosecha <span class="text-danger">*</span></label>
                        <input type="date" name="harvest_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">N° Boleto / Ticket de Balanza</label>
                        <input type="text" name="field_ticket_code" class="form-control form-control-sm font-monospace" placeholder="Ej. TKT-2026-089">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Cantidad Obtenida <span class="text-danger">*</span></label>
                        <input type="number" step="0.001" name="quantity" class="form-control form-control-sm fw-bold" placeholder="0.000" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Peso en Balanza Campo (kg)</label>
                        <input type="number" step="0.1" name="field_weight_kg" class="form-control form-control-sm" placeholder="0.0">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Grado de Calidad</label>
                        <select name="quality_grade" class="form-select form-select-sm">
                            <option value="PRIMERA">Primera Calidad / Exportación</option>
                            <option value="SEGUNDA">Segunda Calidad</option>
                            <option value="ESTÁNDAR">Estándar Industrial</option>
                            <option value="DESCARTE">Descarte</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Almacén de Ingreso (Inventario Central)</label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">-- Sin Almacén / Venta Directa en Campo --</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Observaciones / Chofer / Transporte</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Camión placa, chofer, condiciones climáticas...">
                    </div>

                    <div class="col-12 text-end mt-3">
                        <button type="submit" class="btn btn-success fw-semibold px-4 shadow-sm">
                            <i class="fas fa-save me-1"></i> Grabar Cosecha y Actualizar Inventario
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table of Recent Harvests -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-list me-1 text-primary"></i> Últimas Cosechas Registradas en Campo
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle w-100" id="harvests-table">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha y Boleto</th>
                            <th>Procedencia (Parcela / Vivero)</th>
                            <th>Producto Cosechado</th>
                            <th>Cantidad</th>
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
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const table = $('#harvests-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('agrolivestock.harvests.get') }}",
            data: function(d) {
                d.activity_id = $('#quick_activity_id').val();
            }
        },
        columns: [
            { data: 'date_col', name: 'harvest_date' },
            { data: 'source_col', name: 'plot.name' },
            { data: 'item_col', name: 'producedItem.name' },
            { data: 'quantity_col', name: 'quantity' },
            { data: 'warehouse_col', name: 'warehouse.nombre' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });

    $('#quick_activity_id').on('change', function() {
        table.ajax.reload();
    });

    $(document).on('click', '.btn-delete-harvest', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar Cosecha?',
            text: 'Se eliminará el registro de cosecha en el sistema.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('agrolivestock.harvests.delete') }}",
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
