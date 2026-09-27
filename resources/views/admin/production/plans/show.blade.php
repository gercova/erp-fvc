@extends('admin.layout')
@section('title', 'Detalle de Campaña: ' . $campaign->name)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('production.plans.index') }}">Planes de Producción</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $campaign->campaign_code }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 mb-0 text-gray-900 fw-bold">{{ $campaign->name }}</h1>
                <span class="badge bg-light text-dark font-monospace border fs-6">{{ $campaign->campaign_code }}</span>
                {!! $campaign->status_badge !!}
            </div>
            <p class="text-muted small mb-0 mt-1">
                Actividad: <strong class="text-dark">{{ $campaign->activity->name ?? 'General' }}</strong> |
                Período: {{ $campaign->start_date ? $campaign->start_date->format('d/m/Y') : '-' }} al {{ $campaign->end_date ? $campaign->end_date->format('d/m/Y') : 'Abierto' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateBatch">
                <i class="fas fa-plus me-1"></i> Nueva Fase / Lote
            </button>
            <a href="{{ route('production.plans.edit', $campaign->id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-edit me-1"></i> Editar Plan
            </a>
            <a href="{{ route('production.plans.index') }}" class="btn btn-outline-dark">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Meta de Cosecha</div>
                <div class="h3 mb-0 fw-bold text-dark mt-1">
                    {{ number_format($campaign->target_quantity, 2) }} <small class="fs-6 fw-normal text-muted">{{ $campaign->target_unit }}</small>
                </div>
                <small class="text-muted">Proyectada en el plan</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Cosecha Obtenida</div>
                <div class="h3 mb-0 fw-bold text-success mt-1">
                    {{ number_format($totalHarvestQuantity, 2) }} <small class="fs-6 fw-normal text-muted">{{ $campaign->target_unit }}</small>
                </div>
                <small class="text-success">Rendimiento real en campo</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Costo Total Capturado</div>
                <div class="h3 mb-0 fw-bold text-primary mt-1">S/ {{ number_format($totalCost, 2) }}</div>
                <small class="text-muted">Insumos (S/ {{ number_format($totalInputCost, 2) }}) + Mano de Obra (S/ {{ number_format($totalLaborCost, 2) }})</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Presupuesto Asignado</div>
                <div class="h3 mb-0 fw-bold text-dark mt-1">S/ {{ number_format($campaign->budget_allocated, 2) }}</div>
                @php
                    $executedPercent = $campaign->budget_allocated > 0 ? ($totalCost / $campaign->budget_allocated) * 100 : 0;
                @endphp
                <small class="{{ $executedPercent > 100 ? 'text-danger fw-bold' : 'text-muted' }}">
                    Ejecución: {{ number_format($executedPercent, 1) }}%
                </small>
            </div>
        </div>
    </div>

    <!-- Tabs for Batches, Inputs, Harvests -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3 border-0" id="planTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active py-3 fw-semibold" id="batches-tab" data-bs-toggle="tab" data-bs-target="#batches-pane" type="button" role="tab">
                        <i class="fas fa-cubes me-1"></i> Fases y Lotes ({{ $campaign->batches->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold" id="inputs-tab" data-bs-toggle="tab" data-bs-target="#inputs-pane" type="button" role="tab">
                        <i class="fas fa-boxes me-1"></i> Insumos Consumidos ({{ $campaign->inputMovements->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold" id="harvests-tab" data-bs-toggle="tab" data-bs-target="#harvests-pane" type="button" role="tab">
                        <i class="fas fa-seedling me-1"></i> Cosechas y Salidas ({{ $campaign->harvests->count() }})
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="planTabsContent">
                <!-- Tab 1: Batches & Phases -->
                <div class="tab-pane fade show active" id="batches-pane" role="tabpanel">
                    @if($campaign->batches->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-cubes fs-1 text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">Aún no se han configurado fases ni lotes para esta campaña</h5>
                            <p class="text-muted small">Registre las etapas productivas (ej. Siembra, Mantenimiento, Alevinaje, Engorde, etc.)</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateBatch">
                                <i class="fas fa-plus me-1"></i> Crear Primera Fase
                            </button>
                        </div>
                    @else
                        <div class="row g-4">
                            @foreach($campaign->batches as $batch)
                                <div class="col-lg-6">
                                    <div class="card border shadow-sm h-100">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                            <div>
                                                <span class="badge bg-primary font-monospace me-1">{{ $batch->batch_code }}</span>
                                                <strong class="text-dark">{{ $batch->phase_name }}</strong>
                                            </div>
                                            <div>
                                                {!! $batch->status_badge !!}
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-2 small mb-3">
                                                <div class="col-6">
                                                    <span class="text-muted">Tipo de Fase:</span>
                                                    <div class="fw-semibold">{{ $batch->phase_type_label }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted">Período:</span>
                                                    <div class="fw-semibold">
                                                        {{ $batch->start_date ? $batch->start_date->format('d/m/Y') : '-' }} al
                                                        {{ $batch->end_date ? $batch->end_date->format('d/m/Y') : 'Actual' }}
                                                    </div>
                                                </div>
                                                @if($batch->initial_quantity > 0)
                                                    <div class="col-6">
                                                        <span class="text-muted">Cantidad Inicial:</span>
                                                        <div class="fw-semibold">{{ number_format($batch->initial_quantity, 2) }} {{ $batch->unit_measure }}</div>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted">Cantidad Actual:</span>
                                                        <div class="fw-semibold">{{ number_format($batch->current_quantity, 2) }} {{ $batch->unit_measure }}</div>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Labor Tasks in this phase -->
                                            <div class="d-flex justify-content-between align-items-center mb-2 pt-2 border-top">
                                                <h6 class="small fw-bold text-dark mb-0">
                                                    <i class="fas fa-user-clock me-1 text-primary"></i> Labores y Mano de Obra
                                                </h6>
                                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-add-labor"
                                                    data-batch-id="{{ $batch->id }}"
                                                    data-batch-code="{{ $batch->batch_code }}">
                                                    <i class="fas fa-plus fa-xs me-1"></i> Agregar Labor
                                                </button>
                                            </div>

                                            @if($batch->laborCosts->isEmpty())
                                                <p class="text-muted small fst-italic mb-0">Sin costos de mano de obra registrados en esta fase.</p>
                                            @else
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered align-middle mb-0 small">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Fecha</th>
                                                                <th>Labor / Tarea</th>
                                                                <th>Personal</th>
                                                                <th class="text-end">Costo</th>
                                                                <th style="width: 30px;"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($batch->laborCosts as $labor)
                                                                <tr>
                                                                    <td class="font-monospace">{{ $labor->task_date ? $labor->task_date->format('d/m') : '-' }}</td>
                                                                    <td>{{ $labor->task_description }}</td>
                                                                    <td>{{ $labor->worker_name }}</td>
                                                                    <td class="text-end fw-bold">S/ {{ number_format($labor->labor_cost, 2) }}</td>
                                                                    <td class="text-center">
                                                                        <button type="button" class="btn btn-link text-danger p-0 btn-delete-labor" data-id="{{ $labor->id }}">
                                                                            <i class="fas fa-times fa-xs"></i>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                        <tfoot class="table-light">
                                                            <tr>
                                                                <th colspan="3" class="text-end">Subtotal Labor:</th>
                                                                <th class="text-end fw-bold text-primary">S/ {{ number_format($batch->laborCosts->sum('labor_cost'), 2) }}</th>
                                                                <th></th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Tab 2: Consumed Inputs -->
                <div class="tab-pane fade" id="inputs-pane" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark">Insumos y Materiales Imputados al Proyecto</h6>
                        <a href="{{ route('production.input_movements.index', ['activity_id' => $campaign->productive_activity_id]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i> Registrar Nuevo Consumo
                        </a>
                    </div>
                    @if($campaign->inputMovements->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-boxes fs-3 mb-2 d-block"></i>
                            <p class="small mb-0">No se registran salidas de insumos imputadas a esta campaña.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Insumo</th>
                                        <th>Categoría</th>
                                        <th>Cantidad</th>
                                        <th>Costo Unit.</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($campaign->inputMovements as $mov)
                                        <tr>
                                            <td class="font-monospace">{{ $mov->movement_date ? $mov->movement_date->format('d/m/Y') : '-' }}</td>
                                            <td class="fw-bold">{{ $mov->rawMaterial->name ?? '-' }}</td>
                                            <td><span class="badge bg-light text-dark border">{{ $mov->rawMaterial->category_label ?? '-' }}</span></td>
                                            <td>{{ number_format($mov->quantity, 2) }} {{ $mov->rawMaterial->unit_of_measurement ?? '' }}</td>
                                            <td>S/ {{ number_format($mov->unit_cost, 2) }}</td>
                                            <td class="fw-bold text-dark">S/ {{ number_format($mov->total_cost, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5" class="text-end">Total Costo Insumos:</th>
                                        <th class="fw-bold text-primary">S/ {{ number_format($campaign->inputMovements->sum('total_cost'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Tab 3: Harvests -->
                <div class="tab-pane fade" id="harvests-pane" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark">Cosechas y Rendimientos Registrados</h6>
                        <a href="{{ route('production.harvests.index', ['activity_id' => $campaign->productive_activity_id]) }}" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-plus me-1"></i> Registrar Cosecha
                        </a>
                    </div>
                    @if($campaign->harvests->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-seedling fs-3 mb-2 d-block"></i>
                            <p class="small mb-0">No se registran salidas de cosecha o producto terminado en esta campaña.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Producto Terminado</th>
                                        <th>Cantidad Obtenida</th>
                                        <th>Calidad</th>
                                        <th>Almacén Destino</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($campaign->harvests as $harv)
                                        <tr>
                                            <td class="font-monospace">{{ $harv->harvest_date ? $harv->harvest_date->format('d/m/Y') : '-' }}</td>
                                            <td class="fw-bold text-dark">{{ $harv->producedItem->name ?? '-' }}</td>
                                            <td class="fw-bold text-success fs-6">{{ number_format($harv->quantity, 2) }} {{ $harv->producedItem->unit_of_measurement ?? '' }}</td>
                                            <td><span class="badge bg-secondary">{{ $harv->quality_grade }}</span></td>
                                            <td>{{ $harv->warehouse ? $harv->warehouse->descripcion : 'Sin almacén' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-end">Total Cosechado:</th>
                                        <th colspan="3" class="fw-bold text-success fs-6">{{ number_format($campaign->harvests->sum('quantity'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create Batch / Phase -->
<div class="modal fade" id="modalCreateBatch" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('production.batches.store') }}" method="POST">
            @csrf
            <input type="hidden" name="production_campaign_id" value="{{ $campaign->id }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-cubes me-2 text-primary"></i>Nueva Fase / Lote de Producción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Código de Lote / Fase <span class="text-danger">*</span></label>
                        <input type="text" name="batch_code" class="form-control font-monospace" required placeholder="Ej: LOTE-01, FASE-SIEMBRA">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre Descriptivo de la Fase <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Ej: Alevinaje y Siembra en Estanque 1">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tipo de Proceso <span class="text-danger">*</span></label>
                            <select name="phase_type" class="form-select" required>
                                <option value="start">Inicio / Alevinaje</option>
                                <option value="growth">Crecimiento / Desarrollo</option>
                                <option value="fattening">Engorde / Acabado</option>
                                <option value="sowing">Siembra / Plantación</option>
                                <option value="maintenance">Mantenimiento / Labores</option>
                                <option value="harvest">Cosecha / Recolección</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Estado <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active">Activo (En curso)</option>
                                <option value="completed">Completado</option>
                                <option value="cancelled">Cancelado</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fecha de Inicio <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fecha de Cierre (Opcional)</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Cantidad Inicial de Unidades</label>
                            <input type="number" step="0.01" min="0" name="initial_quantity" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Unidad de Medida</label>
                            <input type="text" name="unit_measure" class="form-control" value="UNIDADES" placeholder="UNIDADES / KG">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Notas de Campo</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Guardar Fase</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Labor Cost Task -->
<div class="modal fade" id="modalAddLabor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('production.batches.labor_cost.store') }}" method="POST">
            @csrf
            <input type="hidden" name="production_batch_id" id="labor_batch_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-clock me-2 text-primary"></i>Registrar Labor y Costo de Mano de Obra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Lote / Fase:</label>
                        <div id="labor_batch_code_display" class="fw-bold font-monospace text-primary"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Descripción de la Labor <span class="text-danger">*</span></label>
                        <input type="text" name="task_description" class="form-control" required placeholder="Ej: Plateo de palmas, Alimentación matutina, Poda sanitaria">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Personal / Cuadrilla <span class="text-danger">*</span></label>
                        <input type="text" name="worker_name" class="form-control" required placeholder="Nombre del trabajador o cuadrilla">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fecha de la Labor <span class="text-danger">*</span></label>
                            <input type="date" name="task_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Horas / Jornal Trabajado <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="hours_worked" id="calc_hours" class="form-control" required value="8.00">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tarifa por Hora / Jornal (S/) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="hourly_rate" id="calc_rate" class="form-control" required value="5.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Costo Total Calculado (S/)</label>
                            <input type="number" step="0.01" min="0" name="labor_cost" id="calc_total_cost" class="form-control fw-bold text-primary" readonly value="40.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Guardar Costo de Labor</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Open modal add labor
    $('.btn-add-labor').on('click', function() {
        var batchId = $(this).data('batch-id');
        var batchCode = $(this).data('batch-code');
        $('#labor_batch_id').val(batchId);
        $('#labor_batch_code_display').text(batchCode);
        $('#modalAddLabor').modal('show');
    });

    // Auto-calculate labor cost
    function updateLaborCost() {
        var h = parseFloat($('#calc_hours').val()) || 0;
        var r = parseFloat($('#calc_rate').val()) || 0;
        $('#calc_total_cost').val((h * r).toFixed(2));
    }
    $('#calc_hours, #calc_rate').on('input', updateLaborCost);

    // Delete labor cost
    $('.btn-delete-labor').on('click', function() {
        var id = $(this).data('id');
        if (!confirm('¿Desea eliminar este registro de labor?')) return;

        $.ajax({
            url: "{{ route('production.batches.labor_cost.delete') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: id
            },
            success: function(res) {
                location.reload();
            }
        });
    });
});
</script>
@endsection
