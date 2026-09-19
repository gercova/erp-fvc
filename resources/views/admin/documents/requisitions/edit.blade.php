@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-fluid px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="edit"></i></div>
                        Editar Requerimiento N° {{ $requisition->correlativo }}
                    </h1>
                    <div class="small text-muted mt-1">Modifique los datos observados o pendientes antes de su evaluación.</div>
                </div>
                <div class="col-auto mb-3">
                    <a href="{{ route('requisitions.show', $requisition->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Cancelar y Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid px-4 pb-5">
    <form id="form-requisition-edit">
        @csrf
        @method('PUT')
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-info-circle me-1"></i> Datos Generales</h6>
                <span class="badge bg-danger fs-6">N° {{ $requisition->correlativo }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">AL (Destinatario):</label>
                        <input type="text" name="dirigido_a" class="form-control" value="{{ $requisition->dirigido_a }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fecha de Emisión:</label>
                        <input type="date" name="fecha" class="form-control" value="{{ $requisition->fecha ? $requisition->fecha->format('Y-m-d') : date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Área / Unidad / P.E.:</label>
                        <select name="area_id" class="form-select select2">
                            <option value="">-- Seleccionar Área --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ $requisition->area_id == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }} ({{ $area->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">DE (Solicitante):</label>
                        <input type="text" name="de" class="form-control" value="{{ $requisition->de }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cargo del Solicitante:</label>
                        <input type="text" name="cargo" class="form-control" value="{{ $requisition->cargo }}" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">I.- FINALIDAD DEL REQUERIMIENTO:</label>
                        <textarea name="finalidad" class="form-control" rows="2" required>{{ $requisition->finalidad }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">II.- FUENTE DE FINANCIAMIENTO / ACTIVIDAD:</label>
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="fuente_financiamiento" id="fuente_pe" value="programa_estudios" {{ $requisition->fuente_financiamiento === 'programa_estudios' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="fuente_pe">
                                        Programa de Estudios (P.E.)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="fuente_financiamiento" id="fuente_prod" value="actividad_productiva" {{ $requisition->fuente_financiamiento === 'actividad_productiva' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="fuente_prod">
                                        Actividad Productiva
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="fuente_financiamiento" id="fuente_plan" value="plan" {{ $requisition->fuente_financiamiento === 'plan' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="fuente_plan">
                                        Plan Operativo / Institucional
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <input type="text" name="fuente_especificar" class="form-control" value="{{ $requisition->fuente_especificar }}" placeholder="Especifique nombre...">
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
                            @foreach ($requisition->items as $index => $item)
                                <tr id="row-{{ $index + 1 }}">
                                    <td class="text-center fw-bold item-number">{{ $index + 1 }}</td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[{{ $index + 1 }}][cantidad]" class="form-control form-control-sm text-center input-cantidad" value="{{ $item->cantidad }}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index + 1 }}][descripcion]" class="form-control form-control-sm" value="{{ $item->descripcion }}" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[{{ $index + 1 }}][precio_unitario]" class="form-control form-control-sm text-end input-precio" value="{{ $item->precio_unitario }}" required>
                                    </td>
                                    <td class="text-end fw-bold item-subtotal">S/ {{ number_format($item->precio_total, 2) }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold fs-6">TOTAL GENERAL (S/):</td>
                                <td class="text-end fw-bold fs-6 text-primary" id="lbl-total-general">S/ {{ number_format($requisition->total, 2) }}</td>
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
                    <textarea name="justificacion" class="form-control" rows="3" required>{{ $requisition->justificacion }}</textarea>
                </div>
            </div>
            <div class="card-footer bg-white py-3 text-end border-top">
                <a href="{{ route('requisitions.show', $requisition->id) }}" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" id="btn-submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let rowIndex = {{ $requisition->items->count() }};

    function addRow(cantidad = 1, descripcion = '', precio = '') {
        rowIndex++;
        let rowHtml = `
            <tr id="row-${rowIndex}">
                <td class="text-center fw-bold item-number">${rowIndex}</td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="items[${rowIndex}][cantidad]" class="form-control form-control-sm text-center input-cantidad" value="${cantidad}" required>
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][descripcion]" class="form-control form-control-sm" value="${descripcion}" placeholder="Nombre o detalle..." required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][precio_unitario]" class="form-control form-control-sm text-end input-precio" value="${precio}" placeholder="0.00" required>
                </td>
                <td class="text-end fw-bold item-subtotal">S/ 0.00</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fas fa-trash-alt"></i></button>
                </td>
            </tr>
        `;
        $('#items-body').append(rowHtml);
        recalc();
    }

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

    $('#form-requisition-edit').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btn-submit');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: "{{ route('requisitions.update', $requisition->id) }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.type === 'success') {
                    toastr.success(response.message);
                    window.location.href = response.redirect;
                } else {
                    toastr.error(response.message);
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Cambios');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Cambios');
                toastr.error('Error al actualizar.');
            }
        });
    });
});
</script>
@endsection
