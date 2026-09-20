@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-fluid px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="edit"></i></div>
                        Editar Papeleta de Vacaciones N° {{ $slip->correlativo }}
                    </h1>
                    <div class="small text-muted mt-1">Actualice los datos observados antes de remitir para su autorización.</div>
                </div>
                <div class="col-auto mb-3">
                    <a href="{{ route('vacation-exit-slips.show', $slip->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Cancelar y Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid px-4 pb-5">
    <form id="form-vacation-slip-edit">
        @csrf
        @method('PUT')
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="m-0 fw-bold text-primary">I.- DATOS DEL SERVIDOR</h6>
                    <small class="text-muted">IESTP "FRANCISCO VIGO CABALLERO" - UCHIZA</small>
                </div>
                <span class="badge bg-primary fs-6">{{ $slip->correlativo }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Apellidos y Nombres:</label>
                        <input type="text" name="apellidos_nombres" class="form-control" value="{{ $slip->apellidos_nombres }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">D.N.I.:</label>
                        <input type="text" name="dni" class="form-control" value="{{ $slip->dni }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Condición Laboral:</label>
                        <select name="condicion_laboral" class="form-select" required>
                            <option value="Docente Nombrado" {{ $slip->condicion_laboral === 'Docente Nombrado' ? 'selected' : '' }}>Docente Nombrado</option>
                            <option value="Docente Contratado" {{ $slip->condicion_laboral === 'Docente Contratado' ? 'selected' : '' }}>Docente Contratado</option>
                            <option value="Administrativo Nombrado" {{ $slip->condicion_laboral === 'Administrativo Nombrado' ? 'selected' : '' }}>Administrativo Nombrado</option>
                            <option value="Administrativo Contratado" {{ $slip->condicion_laboral === 'Administrativo Contratado' ? 'selected' : '' }}>Administrativo Contratado</option>
                            <option value="CAS" {{ $slip->condicion_laboral === 'CAS' ? 'selected' : '' }}>Régimen CAS</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cargo / Especialidad:</label>
                        <input type="text" name="cargo_especialidad" class="form-control" value="{{ $slip->cargo_especialidad }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Área / Programa de Estudios:</label>
                        <input type="text" name="area_programa_estudios" class="form-control" value="{{ $slip->area_programa_estudios }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Dependencia en Organigrama:</label>
                        <select name="area_id" class="form-select select2">
                            <option value="">-- Seleccionar Área --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ $slip->area_id == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-plane-departure me-1"></i> II.- DATOS DE LA SALIDA</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Período Desde:</label>
                        <input type="date" id="fecha_desde" name="fecha_desde" class="form-control" value="{{ $slip->fecha_desde ? $slip->fecha_desde->format('Y-m-d') : '' }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Período Hasta:</label>
                        <input type="date" id="fecha_hasta" name="fecha_hasta" class="form-control" value="{{ $slip->fecha_hasta ? $slip->fecha_hasta->format('Y-m-d') : '' }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Total de Días:</label>
                        <input type="number" id="total_dias" name="total_dias" class="form-control" min="1" value="{{ $slip->total_dias }}" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">R.D. N° (Resolución Directoral):</label>
                        <input type="text" name="resolucion_directoral" class="form-control" value="{{ $slip->resolucion_directoral }}" placeholder="Ej: R.D. N° 045-2026-IESTP-FVC">
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white py-3 text-end border-top">
                <a href="{{ route('vacation-exit-slips.show', $slip->id) }}" class="btn btn-secondary me-2">Cancelar</a>
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
    function calcDays() {
        let f1 = $('#fecha_desde').val();
        let f2 = $('#fecha_hasta').val();
        if (f1 && f2) {
            let d1 = new Date(f1);
            let d2 = new Date(f2);
            let diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
            if (diff > 0) {
                $('#total_dias').val(diff);
            }
        }
    }

    $('#fecha_desde, #fecha_hasta').on('change', function() {
        calcDays();
    });

    $('#form-vacation-slip-edit').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btn-submit');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: "{{ route('vacation-exit-slips.update', $slip->id) }}",
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
