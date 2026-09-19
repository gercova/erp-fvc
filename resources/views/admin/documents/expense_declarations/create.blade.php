@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-fluid px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="file-plus"></i></div>
                        Nueva Declaración Jurada de Gastos
                    </h1>
                    <div class="small text-muted mt-1">Sustento formal de gastos sin comprobante según normativa legal vigente.</div>
                </div>
                <div class="col-auto mb-3">
                    <a href="{{ route('expense-declarations.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver a la Lista
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid px-4 pb-5">
    <form id="form-declaration">
        @csrf
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="m-0 fw-bold text-primary">DECLARACIÓN JURADA DE GASTOS</h6>
                    <small class="text-muted">Ley N° 28411 / Directiva de Tesorería N° 001-2007-EF/77.15</small>
                </div>
                <span class="badge bg-primary fs-6">{{ $correlativo }}</span>
            </div>
            <div class="card-body p-4">
                <input type="hidden" name="correlativo" value="{{ $correlativo }}">

                <div class="alert alert-light border mb-4">
                    <p class="mb-0 text-muted small">
                        <strong>Marco Normativo:</strong> Ley N° 28411, Ley General del Sistema Nacional de Presupuesto y facultado por el numeral del artículo 71° con Directiva de Tesorería N° 001-2007-EF/77.15.
                    </p>
                </div>

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Señor (a) - Nombres y Apellidos:</label>
                        <input type="text" name="servidor_nombres" class="form-control" value="{{ $user->nombres }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Identificado con DNI:</label>
                        <input type="text" name="dni" class="form-control" placeholder="Número de DNI" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Cargo:</label>
                        <input type="text" name="cargo" class="form-control" value="{{ $userAreaDetail?->cargo ?: 'Docente / Servidor' }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Área / Unidad / Programa:</label>
                        <select name="area_id" class="form-select select2">
                            <option value="">-- Seleccionar Área --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ $userAreaDetail && $userAreaDetail->area_id == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Fecha del Documento:</label>
                        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Lugar de Emisión:</label>
                        <input type="text" name="lugar" class="form-control" value="Uchiza" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Conceptos de los Gastos:</label>
                        <p class="small text-muted mb-1">
                            Quien presta Servicio en el Instituto de Educación Superior Público "Francisco Vigo Caballero" de Uchiza. DECLARO haber efectuado los Siguientes Gastos con Cargo del Importe Recibido de la Oficina de ADMINISTRACIÓN, <strong>Por los conceptos de:</strong>
                        </p>
                        <textarea name="conceptos" class="form-control" rows="2" placeholder="Describa el motivo o comisión del gasto del cual fue imposible obtener comprobantes formales..." required></textarea>
                        <small class="text-muted">"...de los cuales me ha sido imposible tener documentos valorados para sustentar el anticipo recibido."</small>
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
                            <!-- Dynamically added -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="text-end fw-bold fs-6">TOTAL GENERAL (S/):</td>
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
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-certificate me-1"></i> Certificación</h6>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-secondary border-0 mb-0">
                    <h6 class="fw-bold mb-1">CERTIFICO:</h6>
                    <p class="mb-0">Que lo DECLARADO en el Documento Justifica los Gastos Efectuados.</p>
                </div>
            </div>
            <div class="card-footer bg-white py-3 text-end border-top">
                <a href="{{ route('expense-declarations.index') }}" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" id="btn-submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-1"></i> Registrar Declaración Jurada
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

    function addRow(fecha = "{{ date('Y-m-d') }}", detalle = '', importe = '') {
        rowIndex++;
        let rowHtml = `
            <tr id="row-${rowIndex}">
                <td>
                    <input type="date" name="items[${rowIndex}][fecha]" class="form-control form-control-sm text-center" value="${fecha}" required>
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][detalle_gestion]" class="form-control form-control-sm" value="${detalle}" placeholder="Detalle del gasto efectuado..." required>
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

    addRow("{{ date('Y-m-d') }}", '', '');
    addRow("{{ date('Y-m-d') }}", '', '');

    $('#btn-add-item').on('click', function() {
        addRow();
    });

    $(document).on('click', '.btn-remove-row', function() {
        if ($('#items-body tr').length > 1) {
            $(this).closest('tr').remove();
            recalc();
        } else {
            toastr.warning('Debe registrar al menos un concepto de gasto.');
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

    $('#form-declaration').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btn-submit');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: "{{ route('expense-declarations.store') }}",
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
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Registrar Declaración Jurada');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Registrar Declaración Jurada');
                toastr.error('Error al guardar la declaración jurada.');
            }
        });
    });
});
</script>
@endsection
