@extends('admin.layout')

@section('title', 'Nuevo Vale de Control de Combustible y Lubricantes')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center my-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('fuel-control-slips.index') }}">Vales de Control</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nuevo</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="fas fa-gas-pump text-danger me-2"></i>Vale de Control Interno de Combustible y Lubricantes
            </h1>
            <p class="text-muted small mb-0">Formato oficial para control y despacho de combustible y lubricantes</p>
        </div>
        <div>
            <a href="{{ route('fuel-control-slips.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver al listado
            </a>
        </div>
    </div>

    <form id="formFuelSlip" action="{{ route('fuel-control-slips.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            <!-- Columna Principal -->
            <div class="col-lg-8">
                <!-- Datos del Abastecimiento -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-dark">
                                <i class="fas fa-info-circle text-primary me-2"></i>1. Datos de Identificación y Despacho
                            </h6>
                            <div class="badge bg-danger fs-6 px-3 py-2">
                                N° {{ $correlativo }}
                                <input type="hidden" name="correlativo" value="{{ $correlativo }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Fecha de Abastecimiento <span class="text-danger">*</span></label>
                                <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Hora <span class="text-danger">*</span></label>
                                <input type="time" name="hora" class="form-control" value="{{ date('H:i') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Área Solicitante <span class="text-danger">*</span></label>
                                <select name="area_id" class="form-select" required>
                                    @foreach($areas as $area)
                                        <option value="{{ $area->id }}" {{ ($userAreaDetail && $userAreaDetail->area_id == $area->id) ? 'selected' : '' }}>
                                            {{ $area->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Nombre del Grifo / Estación de Servicios <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-gas-pump text-muted"></i></span>
                                    <input type="text" name="nombre_grifo" class="form-control" placeholder="Ej: Estación de Servicios San Juan SAC / Grifo Primax" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Requerimiento N° (Opcional)</label>
                                <input type="text" name="requerimiento_nro" class="form-control" placeholder="Ej: REQ-000012">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Orden de Compra N° (Opcional)</label>
                                <input type="text" name="orden_compra_nro" class="form-control" placeholder="Ej: O/C-000085">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Vehículo / Maquinaria <span class="text-danger">*</span></label>
                                <input type="text" name="vehiculo_maquina" class="form-control" placeholder="Ej: Camioneta Toyota Hilux / Tractor Agrícola" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Placa / Código de Máquina</label>
                                <input type="text" name="placa" class="form-control text-uppercase" placeholder="Ej: EGL-892 o S/P">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Kilometraje / Horómetro Actual</label>
                                <input type="text" name="kilometraje_horometro" class="form-control" placeholder="Ej: 145,280 km ó 1,250 hrs">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small text-muted">Actividad / Comisión de Servicio <span class="text-danger">*</span></label>
                                <textarea name="actividad_comision" class="form-control" rows="2" placeholder="Describa la comisión o actividad académica/administrativa en la que se utilizará el combustible..." required></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small text-muted">Facturar a Nombre de: <span class="text-danger">*</span></label>
                                <input type="text" name="facturar_a" class="form-control" value="IESTP &quot;FRANCISCO VIGO CABALLERO&quot; - RUC: 20489378121" required>
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
                                    <tr class="item-row">
                                        <td>
                                            <input type="number" step="0.01" min="0.01" name="items[0][cantidad]" class="form-control form-control-sm text-center item-qty" placeholder="0.00" required>
                                        </td>
                                        <td>
                                            <select name="items[0][unidad_medida]" class="form-select form-select-sm" required>
                                                <option value="Galones">Galones</option>
                                                <option value="Litros">Litros</option>
                                                <option value="Cuartos">Cuartos (1/4)</option>
                                                <option value="Balde">Balde</option>
                                                <option value="Unidad">Unidad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="items[0][descripcion]" class="form-control form-control-sm item-desc" placeholder="Ej: Diésel B5 S-50 / Gasolina 90 / Aceite 15W40" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[0][precio_unitario]" class="form-control form-control-sm text-end item-price" placeholder="0.00">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm text-end item-total bg-light" readonly value="0.00">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row" disabled>
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end fw-bold">TOTAL ESTIMADO (S/):</td>
                                        <td class="text-end fw-bold text-danger fs-6" id="total_general_display">S/ 0.00</td>
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
                        <textarea name="observaciones" class="form-control" rows="2" placeholder="Detalles de abastecimiento, precintos, comprobante emitido por grifo, etc."></textarea>
                    </div>
                </div>
            </div>

            <!-- Columna Lateral: Resumen y Workflow -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4 sticky-top" style="top: 20px;">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="m-0 fw-bold text-dark">
                            <i class="fas fa-tasks text-primary me-2"></i>Circuito de Firmas y Control
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Al emitir el presente vale, se generará la cadena de aprobaciones requerida para su conformidad institucional:</p>

                        <div class="timeline-workflow small">
                            <div class="d-flex mb-3">
                                <div class="me-3 text-center">
                                    <span class="badge bg-primary rounded-circle p-2"><i class="fas fa-user"></i></span>
                                </div>
                                <div>
                                    <div class="fw-semibold">1. Conductor / Operador</div>
                                    <div class="text-muted">{{ $user->name }}</div>
                                    <span class="badge bg-success mt-1">Emisor</span>
                                </div>
                            </div>
                            <div class="d-flex mb-3">
                                <div class="me-3 text-center">
                                    <span class="badge bg-secondary rounded-circle p-2"><i class="fas fa-user-check"></i></span>
                                </div>
                                <div>
                                    <div class="fw-semibold">2. Jefe Inmediato / Coordinador</div>
                                    <div class="text-muted">Conformidad del servicio</div>
                                    <span class="badge bg-light text-dark border mt-1">Pendiente</span>
                                </div>
                            </div>
                            <div class="d-flex mb-3">
                                <div class="me-3 text-center">
                                    <span class="badge bg-secondary rounded-circle p-2"><i class="fas fa-clipboard-check"></i></span>
                                </div>
                                <div>
                                    <div class="fw-semibold">3. Administración / Abastecimiento</div>
                                    <div class="text-muted">Control y facturación</div>
                                    <span class="badge bg-light text-dark border mt-1">Pendiente</span>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm" id="btn_submit">
                                <i class="fas fa-paper-plane me-2"></i> Generar Vale de Control
                            </button>
                            <a href="{{ route('fuel-control-slips.index') }}" class="btn btn-outline-secondary">
                                Cancelar
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
    let rowIndex = 1;

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

    $('#formFuelSlip').on('submit', function(e) {
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
                    title: '¡Registrado!',
                    text: resp.message || 'El vale de control fue creado exitosamente.',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    window.location.href = resp.redirect;
                });
            },
            error: function(err) {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i> Generar Vale de Control');
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
