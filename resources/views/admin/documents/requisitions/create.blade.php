@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-fluid px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="file-plus"></i></div>
                        Nuevo Requerimiento de Bienes y Servicios
                    </h1>
                    <div class="small text-muted mt-1">Llene los datos según el formato oficial N° {{ $correlativo }}.</div>
                </div>
                <div class="col-auto mb-3">
                    <a href="{{ route('requisitions.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver a la Lista
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid px-4 pb-5">
    <form id="form-requisition">
        @csrf
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-info-circle me-1"></i> Datos Generales del Requerimiento</h6>
                <span class="badge bg-danger fs-6">N° {{ $correlativo }}</span>
            </div>
            <div class="card-body p-4">
                <input type="hidden" name="correlativo" value="{{ $correlativo }}">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">AL (Destinatario):</label>
                        <input type="text" name="dirigido_a" class="form-control" value="Director Gerente del IESTP &quot;Francisco Vigo Caballero&quot;" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fecha de Emisión:</label>
                        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Área / Unidad / P.E.:</label>
                        <select name="area_id" class="form-select select2">
                            <option value="">-- Seleccionar Área --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ $userAreaDetail && $userAreaDetail->area_id == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }} ({{ $area->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">DE (Solicitante):</label>
                        <input type="text" name="de" class="form-control" value="{{ $user->nombres }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cargo del Solicitante:</label>
                        <input type="text" name="cargo" class="form-control" value="{{ $userAreaDetail?->cargo ?: 'Docente / Servidor' }}" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">I.- FINALIDAD DEL REQUERIMIENTO:</label>
                        <p class="small text-muted mb-1">Solicito a usted la atención del presente requerimiento, el cual será destinado a:</p>
                        <textarea name="finalidad" class="form-control" rows="2" placeholder="Describa el objetivo y destino de los bienes o servicios..." required></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">II.- FUENTE DE FINANCIAMIENTO / ACTIVIDAD:</label>
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="fuente_financiamiento" id="fuente_pe" value="programa_estudios" checked>
                                    <label class="form-check-label fw-semibold" for="fuente_pe">
                                        Programa de Estudios (P.E.)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="fuente_financiamiento" id="fuente_prod" value="actividad_productiva">
                                    <label class="form-check-label fw-semibold" for="fuente_prod">
                                        Actividad Productiva
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="fuente_financiamiento" id="fuente_plan" value="plan">
                                    <label class="form-check-label fw-semibold" for="fuente_plan">
                                        Plan Operativo / Institucional
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <input type="text" name="fuente_especificar" class="form-control" placeholder="Especifique el nombre del P.E., Actividad Productiva o Plan correspondiente...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-list-ol me-1"></i> III.- DETALLE DEL REQUERIMIENTO</h6>
                <button type="button" id="btn-add-item" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus me-1"></i> Agregar Bien/Servicio
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="items-table">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th style="width: 50px;">N°</th>
                                <th style="width: 120px;">Cantidad</th>
                                <th>Descripción del Bien o Servicio</th>
                                <th style="width: 150px;">Precio Unit. (S/)</th>
                                <th style="width: 150px;">Precio Total (S/)</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <!-- Rows added dynamically via JS -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold fs-6">TOTAL GENERAL (S/):</td>
                                <td class="text-end fw-bold fs-6 text-primary" id="lbl-total-general">S/ 0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-align-left me-1"></i> IV.- JUSTIFICACIÓN Y CONDICIONES</h6>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">IV.- JUSTIFICACIÓN:</label>
                    <p class="small text-muted mb-1">(Indicar la necesidad, urgencia y beneficio institucional del requerimiento)</p>
                    <textarea name="justificacion" class="form-control" rows="3" placeholder="Detalle la justificación institucional..." required></textarea>
                </div>

                <div class="alert alert-secondary mb-0 border-0">
                    <h6 class="fw-bold mb-1"><i class="fas fa-info-circle me-1"></i> V.- CONDICIONES ADMINISTRATIVAS:</h6>
                    <p class="small mb-0">El presente requerimiento deberá ser ingresado por Trámite Documentario (Mesa de Partes) para su evaluación y aprobación por la Dirección General.</p>
                </div>
            </div>
            <div class="card-footer bg-white py-3 text-end border-top">
                <a href="{{ route('requisitions.index') }}" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" id="btn-submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-1"></i> Registrar y Enviar a Firmas
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let rowIndex = 0;

    function addRow(cantidad = 1, descripcion = '', precio = '') {
        rowIndex++;
        let rowHtml = `
            <tr id="row-${rowIndex}">
                <td class="text-center fw-bold item-number">${rowIndex}</td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="items[${rowIndex}][cantidad]" class="form-control form-control-sm text-center input-cantidad" value="${cantidad}" required>
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][descripcion]" class="form-control form-control-sm" value="${descripcion}" placeholder="Nombre o detalle del bien o servicio..." required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][precio_unitario]" class="form-control form-control-sm text-end input-precio" value="${precio}" placeholder="0.00" required>
                </td>
                <td class="text-end fw-bold item-subtotal">S/ 0.00</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" data-id="${rowIndex}"><i class="fas fa-trash-alt"></i></button>
                </td>
            </tr>
        `;
        $('#items-body').append(rowHtml);
        recalc();
    }

    // Add 3 default empty rows
    addRow(1, '', '');
    addRow(1, '', '');
    addRow(1, '', '');

    $('#btn-add-item').on('click', function() {
        addRow();
    });

    $(document).on('click', '.btn-remove-row', function() {
        if ($('#items-body tr').length > 1) {
            $(this).closest('tr').remove();
            reorderRows();
            recalc();
        } else {
            toastr.warning('Debe registrar al menos un ítem.');
        }
    });

    $(document).on('input', '.input-cantidad, .input-precio', function() {
        recalc();
    });

    function reorderRows() {
        $('#items-body tr').each(function(index) {
            $(this).find('.item-number').text(index + 1);
        });
    }

    function recalc() {
        let total = 0;
        $('#items-body tr').each(function() {
            let cant = parseFloat($(this).find('.input-cantidad').val()) || 0;
            let precio = parseFloat($(this).find('.input-precio').val()) || 0;
            let subtotal = cant * precio;
            $(this).find('.item-subtotal').text('S/ ' + subtotal.toFixed(2));
            total += subtotal;
        });
        $('#lbl-total-general').text('S/ ' + total.toFixed(2));
    }

    $('#form-requisition').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btn-submit');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: "{{ route('requisitions.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.type === 'success') {
                    Swal.fire({
                        title: '¡Registrado!',
                        text: response.message,
                        icon: 'success'
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                } else {
                    toastr.error(response.message);
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Registrar y Enviar a Firmas');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Registrar y Enviar a Firmas');
                let msg = 'Error al registrar el requerimiento.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                toastr.error(msg);
            }
        });
    });
});
</script>
@endsection
