@extends('admin.layout')
@section('title', 'Historial Sanitario y Eventos: ' . $unit->identifier_code)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('agrolivestock.livestock.index') }}">Manejo Pecuario</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $unit->identifier_code }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-paw me-2 text-danger"></i>{{ $unit->identifier_code }}
            </h1>
            <p class="text-muted small mb-0">Especie: <span class="fw-bold">{{ $unit->species_label }}</span> | Raza: {{ $unit->breed ?? 'Común' }} | Actividad: {{ $unit->activity->name ?? 'General' }}</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalLivestockEvent">
                <i class="fas fa-notes-medical me-1"></i> Registrar Evento Pecuario
            </button>
            <a href="{{ route('agrolivestock.livestock.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver a Lista
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Cards Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Población / Tipo</div>
                <div class="h3 mb-0 fw-bold text-dark mt-1">
                    {{ $unit->tracking_type === 'BATCH' ? number_format($unit->batch_head_count) . ' cabezas' : 'Individual' }}
                </div>
                <small class="text-muted">Sexo: {{ $unit->sex }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Evolución de Peso</div>
                <div class="h3 mb-0 fw-bold text-primary mt-1">{{ number_format($unit->current_weight_kg ?? 0, 1) }} kg</div>
                <small class="text-muted">Ingreso: {{ number_format($unit->entry_weight_kg ?? 0, 1) }} kg</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Costo Total en Eventos</div>
                <div class="h3 mb-0 fw-bold text-danger mt-1">S/ {{ number_format($totalEventCost, 2) }}</div>
                <small class="text-muted">Insumos y sanidad aplicados</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Estado Actual</div>
                <div class="mt-2">{!! $unit->status_badge !!}</div>
                <small class="text-muted d-block mt-1">Fecha Ingreso: {{ $unit->birth_or_entry_date ? $unit->birth_or_entry_date->format('d/m/Y') : '-' }}</small>
            </div>
        </div>
    </div>

    <!-- Eventos Pecuarios Registrados -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-history me-1 text-primary"></i> Historial de Eventos Sanitarios, Alimentación y Manejo
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalLivestockEvent">
                <i class="fas fa-plus me-1"></i> Agregar Evento
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase text-muted">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo de Evento</th>
                            <th>Fase / Descripción</th>
                            <th>Insumo Utilizado (Bloque C)</th>
                            <th>Cabezas</th>
                            <th>Costo Insumo</th>
                            <th>Mano de Obra</th>
                            <th>Costo Total</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unit->events as $ev)
                            <tr>
                                <td class="font-monospace fw-bold">{{ $ev->event_date->format('d/m/Y') }}</td>
                                <td>{!! $ev->event_type_badge !!}</td>
                                <td>
                                    @if($ev->feeding_phase)
                                        <div class="badge bg-light text-primary border mb-1">{{ $ev->feeding_phase_label }}</div>
                                    @endif
                                    <div class="fw-semibold text-dark">{{ $ev->description }}</div>
                                    @if($ev->dosage_or_ration)
                                        <small class="text-muted">Dosis: {{ $ev->dosage_or_ration }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($ev->rawMaterial)
                                        <div class="fw-bold text-dark">{{ $ev->rawMaterial->name }}</div>
                                        <small class="text-muted">{{ number_format($ev->input_quantity_used, 2) }} {{ $ev->rawMaterial->unit_of_measurement }}</small>
                                    @else
                                        <span class="text-muted small">Sin insumo registrado</span>
                                    @endif
                                </td>
                                <td>{{ $ev->head_affected_count }}</td>
                                <td class="text-muted">S/ {{ number_format($ev->input_cost, 2) }}</td>
                                <td class="text-muted">S/ {{ number_format($ev->labor_cost, 2) }}</td>
                                <td><strong class="text-danger">S/ {{ number_format($ev->total_cost, 2) }}</strong></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-event" data-id="{{ $ev->id }}" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    No hay eventos registrados para esta unidad o lote pecuario.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Evento Pecuario -->
<div class="modal fade" id="modalLivestockEvent" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('agrolivestock.livestock.event.store') }}">
            @csrf
            <input type="hidden" name="livestock_unit_id" value="{{ $unit->id }}">

            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title fs-6 fw-bold">
                        <i class="fas fa-notes-medical me-2"></i>Registrar Evento Pecuario para {{ $unit->identifier_code }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tipo de Evento <span class="text-danger">*</span></label>
                            <select name="event_type" id="select_event_type" class="form-select form-select-sm" required>
                                <option value="HEALTH_TREATMENT">Tratamiento Sanitario (Desparasitante / Antibiótico)</option>
                                <option value="VACCINATION">Vacunación / Vitaminización</option>
                                <option value="FEEDING_LOG">Alimentación / Ración (Fases Porcinos/Bovinos/Aves)</option>
                                <option value="WEIGHT_CONTROL">Control de Pesaje Biomédico</option>
                                <option value="BREEDING_SERVICE">Servicio Reproductivo / Inseminación</option>
                                <option value="BIRTH">Parto / Nacimiento</option>
                                <option value="WEANING">Destete</option>
                                <option value="MORTALITY_DISPOSAL">Baja / Mortalidad</option>
                                <option value="SALE_TRANSFER">Venta / Traslado</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fecha del Evento <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <!-- Bloque de Alimentación por Fases (Porcinos, Cuyes, etc.) -->
                        <div class="col-md-6" id="box_feeding_phase" style="display: none;">
                            <label class="form-label small fw-bold text-primary">Fase de Alimentación</label>
                            <select name="feeding_phase" class="form-select form-select-sm">
                                <option value="STARTER">Inicio / Pre-inicio (Starter)</option>
                                <option value="GROWER">Crecimiento (Grower)</option>
                                <option value="FINISHER">Engorde / Acabado (Finisher)</option>
                                <option value="MAINTENANCE">Mantenimiento</option>
                                <option value="LACTATION">Lactancia / Maternidad</option>
                            </select>
                        </div>

                        <!-- Bloque de Pesaje -->
                        <div class="col-md-6" id="box_weight_control" style="display: none;">
                            <label class="form-label small fw-bold text-info">Nuevo Peso Registrado (kg)</label>
                            <input type="number" step="0.1" name="new_weight_kg" class="form-control form-control-sm" placeholder="Ej. 85.5">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Cabezas Afectadas / Atendidas <span class="text-danger">*</span></label>
                            <input type="number" name="head_affected_count" class="form-control form-control-sm" value="{{ $unit->batch_head_count }}" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Dosis / Ración Administrada</label>
                            <input type="text" name="dosage_or_ration" class="form-control form-control-sm" placeholder="Ej. 5 ml por animal, 2 kg/día de concentrado">
                        </div>

                        <!-- Insumo de Bloque C -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Insumo Utilizado (Catálogo Bloque C)</label>
                            <select name="production_raw_material_id" id="event_material_id" class="form-select form-select-sm">
                                <option value="">-- Ninguno / Externo --</option>
                                @foreach($rawMaterials as $rm)
                                    <option value="{{ $rm->id }}" data-cost="{{ $rm->unit_cost }}">
                                        {{ $rm->name }} ({{ $rm->category_label }}) - S/ {{ number_format($rm->unit_cost, 2) }}/{{ $rm->unit_of_measurement }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Cantidad de Insumo</label>
                            <input type="number" step="0.001" name="input_quantity_used" id="event_qty" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Costo del Insumo (S/)</label>
                            <input type="number" step="0.01" name="input_cost" id="event_input_cost" class="form-control form-control-sm" value="0.00">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Costo de Mano de Obra / Veterinario (S/)</label>
                            <input type="number" step="0.01" name="labor_cost" class="form-control form-control-sm" value="0.00">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Descripción del Evento <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control form-control-sm" placeholder="Ej. Aplicación de Ivermectina 1% contra parásitos internos" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Observaciones Adicionales</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Respuesta al tratamiento, periodo de retiro en carne/leche..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold">
                        <i class="fas fa-save me-1"></i> Guardar Evento Pecuario
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
    $('#select_event_type').on('change', function() {
        const val = $(this).val();
        if (val === 'FEEDING_LOG') {
            $('#box_feeding_phase').show();
            $('#box_weight_control').hide();
        } else if (val === 'WEIGHT_CONTROL') {
            $('#box_feeding_phase').hide();
            $('#box_weight_control').show();
        } else {
            $('#box_feeding_phase').hide();
            $('#box_weight_control').hide();
        }
    }).trigger('change');

    // Cálculo automático de costo de insumo al seleccionar
    $('#event_material_id, #event_qty').on('change keyup', function() {
        const selected = $('#event_material_id option:selected');
        const unitCost = parseFloat(selected.data('cost')) || 0;
        const qty = parseFloat($('#event_qty').val()) || 0;
        if (unitCost > 0 && qty > 0) {
            $('#event_input_cost').val((unitCost * qty).toFixed(2));
        }
    });

    $(document).on('click', '.btn-delete-event', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar Evento Pecuario?',
            text: 'Se eliminará el registro del evento.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('agrolivestock.livestock.event.delete') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id
                    },
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire('Eliminado', resp.message, 'success').then(() => location.reload());
                        }
                    }
                });
            }
        });
    });
});
</script>
@endsection
