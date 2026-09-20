@extends('admin.layout')

@section('title', 'Editar Vale de Control N° ' . $slip->correlativo)

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center my-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('fuel-control-slips.index') }}">Vales de Control</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('fuel-control-slips.show', $slip->id) }}">N° {{ $slip->correlativo }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Editar</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="fas fa-edit text-warning me-2"></i>Editar Vale de Control N° <span class="text-danger">{{ $slip->correlativo }}</span>
            </h1>
            <p class="text-muted small mb-0">Modifique los datos del vale antes de la aprobación final</p>
        </div>
        <div>
            <a href="{{ route('fuel-control-slips.show', $slip->id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <form id="formEditFuelSlip" action="{{ route('fuel-control-slips.update', $slip->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <!-- Columna Principal -->
            <div class="col-lg-8">
                <!-- Datos del Abastecimiento -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="m-0 fw-bold text-dark">
                            <i class="fas fa-info-circle text-primary me-2"></i>1. Datos de Identificación y Despacho
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Fecha de Abastecimiento <span class="text-danger">*</span></label>
                                <input type="date" name="fecha" class="form-control" value="{{ $slip->fecha ? $slip->fecha->format('Y-m-d') : '' }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Hora <span class="text-danger">*</span></label>
                                <input type="time" name="hora" class="form-control" value="{{ substr((string)$slip->hora, 0, 5) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Área Solicitante <span class="text-danger">*</span></label>
                                <select name="area_id" class="form-select" required>
                                    @foreach($areas as $area)
                                        <option value="{{ $area->id }}" {{ $slip->area_id == $area->id ? 'selected' : '' }}>
                                            {{ $area->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Nombre del Grifo / Estación de Servicios <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-gas-pump text-muted"></i></span>
                                    <input type="text" name="nombre_grifo" class="form-control" value="{{ $slip->nombre_grifo }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Requerimiento N°</label>
                                <input type="text" name="requerimiento_nro" class="form-control" value="{{ $slip->requerimiento_nro }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Orden de Compra N°</label>
                                <input type="text" name="orden_compra_nro" class="form-control" value="{{ $slip->orden_compra_nro }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Vehículo / Maquinaria <span class="text-danger">*</span></label>
                                <input type="text" name="vehiculo_maquina" class="form-control" value="{{ $slip->vehiculo_maquina }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Placa / Código</label>
                                <input type="text" name="placa" class="form-control text-uppercase" value="{{ $slip->placa }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Kilometraje / Horómetro</label>
                                <input type="text" name="kilometraje_horometro" class="form-control" value="{{ $slip->kilometraje_horometro }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small text-muted">Actividad / Comisión de Servicio <span class="text-danger">*</span></label>
                                <textarea name="actividad_comision" class="form-control" rows="2" required>{{ $slip->actividad_comision }}</textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small text-muted">Facturar a Nombre de: <span class="text-danger">*</span></label>
                                <input type="text" name="facturar_a" class="form-control" value="{{ $slip->facturar_a }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Combustible y Lubricantes -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-dark">
                            <i class="fas fa-oil-can text-danger me-2"></i>2. Detalle de Combustibles y Lubricantes
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btn_add_item">
                            <i class="fas fa-plus me-1"></i> Agregar Fila
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" id="items_table">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th style="width: 110px;" class="text-center">Cantidad <span class="text-danger">*</span></th>
                                        <th style="width: 130px;" class="text-center">Unidad Med. <span class="text-danger">*</span></th>
                                        <th>Descripción del Combustible / Lubricante <span class="text-danger">*</span></th>
                                        <th style="width: 130px;" class="text-center">P. Unitario (S/)</th>
                                        <th style="width: 130px;" class="text-center">Importe (S/)</th>
                                        <th style="width: 50px;" class="text-center">#</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($slip->items as $idx => $it)
                                    <tr class="item-row">
                                        <td>
                                            <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][cantidad]" class="form-control form-control-sm text-center item-qty" value="{{ $it->cantidad }}" required>
                                        </td>
                                        <td>
                                            <select name="items[{{ $idx }}][unidad_medida]" class="form-select form-select-sm" required>
                                                <option value="Galones" {{ $it->unidad_medida == 'Galones' ? 'selected' : '' }}>Galones</option>
                                                <option value="Litros" {{ $it->unidad_medida == 'Litros' ? 'selected' : '' }}>Litros</option>
                                                <option value="Cuartos" {{ $it->unidad_medida == 'Cuartos' ? 'selected' : '' }}>Cuartos (1/4)</option>
                                                <option value="Balde" {{ $it->unidad_medida == 'Balde' ? 'selected' : '' }}>Balde</option>
                                                <option value="Unidad" {{ $it->unidad_medida == 'Unidad' ? 'selected' : '' }}>Unidad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $idx }}][descripcion]" class="form-control form-control-sm item-desc" value="{{ $it->descripcion }}" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[{{ $idx }}][precio_unitario]" class="form-control form-control-sm text-end item-price" value="{{ $it->precio_unitario }}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm text-end item-total bg-light" readonly value="{{ number_format($it->total, 2, '.', '') }}">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row" {{ count($slip->items) <= 1 ? 'disabled' : '' }}>
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end fw-bold">TOTAL ESTIMADO (S/):</td>
                                        <td class="text-end fw-bold text-danger fs-6" id="total_general_display">S/ {{ number_format($slip->total_general, 2) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="m-0 fw-bold text-dark">
                            <i class="fas fa-comment-dots text-primary me-2"></i>3. Observaciones Adicionales
                        </h6>
                    </div>
                    <div class="card-body">
                        <textarea name="observaciones" class="form-control" rows="2">{{ $slip->observaciones }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Columna Lateral: Acciones -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4 sticky-top" style="top: 20px;">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="m-0 fw-bold text-dark">
                            <i class="fas fa-save text-primary me-2"></i>Guardar Cambios
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Al guardar las modificaciones, los cambios se reflejarán inmediatamente en la vista y en el PDF del vale de control.</p>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning btn-lg text-dark shadow-sm" id="btn_submit">
                                <i class="fas fa-save me-2"></i> Actualizar Vale de Control
                            </button>
                            <a href="{{ route('fuel-control-slips.show', $slip->id) }}" class="btn btn-outline-secondary">
                                Descartar Cambios
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let rowIndex = {{ count($slip->items) }};

    function calculateRowTotal(row) {
        const qty = parseFloat(row.find('.item-qty').val()) || 0;
        const price = parseFloat(row.find('.item-price').val()) || 0;
        const total = qty * price;
        row.find('.item-total').val(total.toFixed(2));
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let grand = 0;
        $('.item-total').each(function() {
            grand += parseFloat($(this).val()) || 0;
        });
        $('#total_general_display').text('S/ ' + grand.toFixed(2));
    }

    $(document).on('input', '.item-qty, .item-price', function() {
        calculateRowTotal($(this).closest('tr'));
    });

    $('#btn_add_item').click(function() {
        const newRow = `
            <tr class="item-row">
                <td>
                    <input type="number" step="0.01" min="0.01" name="items[${rowIndex}][cantidad]" class="form-control form-control-sm text-center item-qty" placeholder="0.00" required>
                </td>
                <td>
                    <select name="items[${rowIndex}][unidad_medida]" class="form-select form-select-sm" required>
                        <option value="Galones">Galones</option>
                        <option value="Litros">Litros</option>
                        <option value="Cuartos">Cuartos (1/4)</option>
                        <option value="Balde">Balde</option>
                        <option value="Unidad">Unidad</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][descripcion]" class="form-control form-control-sm item-desc" placeholder="Ej: Diésel B5 S-50 / Gasolina 90 / Aceite" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][precio_unitario]" class="form-control form-control-sm text-end item-price" placeholder="0.00">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm text-end item-total bg-light" readonly value="0.00">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#items_table tbody').append(newRow);
        rowIndex++;
        updateRemoveButtons();
    });

    $(document).on('click', '.btn-remove-row', function() {
        $(this).closest('tr').remove();
        updateRemoveButtons();
        calculateGrandTotal();
    });

    function updateRemoveButtons() {
        const rows = $('#items_table tbody tr');
        rows.find('.btn-remove-row').prop('disabled', rows.length <= 1);
    }

    $('#formEditFuelSlip').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btn_submit');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Guardando...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Actualizado!',
                    text: resp.message || 'El vale de control fue actualizado exitosamente.',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    window.location.href = resp.redirect;
                });
            },
            error: function(err) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Actualizar Vale de Control');
                const msg = err.responseJSON?.message || 'Ocurrió un error al procesar la solicitud.';
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Validación',
                    text: msg
                });
            }
        });
    });
});
</script>
@endpush
