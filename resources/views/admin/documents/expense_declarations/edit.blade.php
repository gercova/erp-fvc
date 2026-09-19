@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-fluid px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="edit"></i></div>
                        Editar Declaración Jurada N° {{ $declaration->correlativo }}
                    </h1>
                    <div class="small text-muted mt-1">Actualice los datos observados antes de re-enviar para su aprobación.</div>
                </div>
                <div class="col-auto mb-3">
                    <a href="{{ route('expense-declarations.show', $declaration->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Cancelar y Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid px-4 pb-5">
    <form id="form-declaration-edit">
        @csrf
        @method('PUT')
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="m-0 fw-bold text-primary">DATOS DE LA DECLARACIÓN JURADA</h6>
                    <small class="text-muted">Ley N° 28411 / Directiva de Tesorería N° 001-2007-EF/77.15</small>
                </div>
                <span class="badge bg-primary fs-6">{{ $declaration->correlativo }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Señor (a) - Nombres y Apellidos:</label>
                        <input type="text" name="servidor_nombres" class="form-control" value="{{ $declaration->servidor_nombres }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Identificado con DNI:</label>
                        <input type="text" name="dni" class="form-control" value="{{ $declaration->dni }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Cargo:</label>
                        <input type="text" name="cargo" class="form-control" value="{{ $declaration->cargo }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Área / Unidad / Programa:</label>
                        <select name="area_id" class="form-select select2">
                            <option value="">-- Seleccionar Área --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ $declaration->area_id == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Fecha del Documento:</label>
                        <input type="date" name="fecha" class="form-control" value="{{ $declaration->fecha ? $declaration->fecha->format('Y-m-d') : date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Lugar de Emisión:</label>
                        <input type="text" name="lugar" class="form-control" value="{{ $declaration->lugar }}" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Conceptos de los Gastos:</label>
                        <textarea name="conceptos" class="form-control" rows="2" required>{{ $declaration->conceptos }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-receipt me-1"></i> Detalle de la Gestión y Gastos</h6>
                <button type="button" id="btn-add-item" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus me-1"></i> Agregar Gasto
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="items-table">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th style="width: 170px;">Fecha</th>
                                <th>Detalle de la Gestión</th>
                                <th style="width: 180px;">Importe (S/)</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            @foreach ($declaration->items as $index => $item)
                                <tr id="row-{{ $index + 1 }}">
                                    <td>
                                        <input type="date" name="items[{{ $index + 1 }}][fecha]" class="form-control form-control-sm text-center" value="{{ $item->fecha ? $item->fecha->format('Y-m-d') : '' }}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index + 1 }}][detalle_gestion]" class="form-control form-control-sm" value="{{ $item->detalle_gestion }}" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[{{ $index + 1 }}][importe]" class="form-control form-control-sm text-end input-importe" value="{{ $item->importe }}" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="text-end fw-bold fs-6">TOTAL GENERAL (S/):</td>
                                <td class="text-end fw-bold fs-6 text-primary" id="lbl-total-general">S/ {{ number_format($declaration->total, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3 text-end border-top">
                <a href="{{ route('expense-declarations.show', $declaration->id) }}" class="btn btn-secondary me-2">Cancelar</a>
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
    let rowIndex = {{ $declaration->items->count() }};

    function addRow(fecha = "{{ date('Y-m-d') }}", detalle = '', importe = '') {
        rowIndex++;
        let rowHtml = `
            <tr id="row-${rowIndex}">
                <td>
                    <input type="date" name="items[${rowIndex}][fecha]" class="form-control form-control-sm text-center" value="${fecha}" required>
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][detalle_gestion]" class="form-control form-control-sm" value="${detalle}" placeholder="Detalle..." required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="items[${rowIndex}][importe]" class="form-control form-control-sm text-end input-importe" value="${importe}" placeholder="0.00" required>
                </td>
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
            recalc();
        } else {
            toastr.warning('Debe registrar al menos un concepto.');
        }
    });

    $(document).on('input', '.input-importe', function() {
        recalc();
    });

    function recalc() {
        let total = 0;
        $('#items-body tr').each(function() {
            let imp = parseFloat($(this).find('.input-importe').val()) || 0;
            total += imp;
        });
        $('#lbl-total-general').text('S/ ' + total.toFixed(2));
    }

    $('#form-declaration-edit').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btn-submit');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: "{{ route('expense-declarations.update', $declaration->id) }}",
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
