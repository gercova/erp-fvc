@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-fluid px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="edit"></i></div>
                        Editar Papeleta de Vehículo N° {{ $slip->correlativo }}
                    </h1>
                    <div class="small text-muted mt-1">Actualice los datos antes de reenviar para autorización.</div>
                </div>
                <div class="col-auto mb-3">
                    <a href="{{ route('vehicle-exit-slips.show', $slip->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Cancelar y Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid px-4 pb-5">
    <form id="form-vehicle-slip-edit">
        @csrf
        @method('PUT')
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="m-0 fw-bold text-primary">PAPELETA DE SALIDA DE VEHÍCULO</h6>
                    <small class="text-muted">IESTP "FRANCISCO VIGO CABALLERO" - UCHIZA</small>
                </div>
                <span class="badge bg-primary fs-6">{{ $slip->correlativo }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Vehículo:</label>
                        <input type="text" name="vehiculo" class="form-control" value="{{ $slip->vehiculo }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Persona Solicitante:</label>
                        <input type="text" name="solicitante_nombre" class="form-control" value="{{ $slip->solicitante_nombre }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Chofer Designado:</label>
                        <input type="text" name="chofer_nombre" class="form-control" value="{{ $slip->chofer_nombre }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">N° Brevete / Licencia:</label>
                        <input type="text" name="brevete_numero" class="form-control" value="{{ $slip->brevete_numero }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Área / Unidad:</label>
                        <select name="area_id" class="form-select select2">
                            <option value="">-- Seleccionar Área --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ $slip->area_id == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Lugar (Destino):</label>
                        <input type="text" name="lugar" class="form-control" value="{{ $slip->lugar }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Motivo de Salida del Vehículo:</label>
                        <textarea name="motivo" class="form-control" rows="3" required>{{ $slip->motivo }}</textarea>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fecha de Salida:</label>
                        <input type="date" name="fecha_salida" class="form-control" value="{{ $slip->fecha_salida ? $slip->fecha_salida->format('Y-m-d') : '' }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Hora de Salida:</label>
                        <input type="time" name="hora_salida" class="form-control" value="{{ substr((string)$slip->hora_salida, 0, 5) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fecha de Retorno:</label>
                        <input type="date" name="fecha_retorno" class="form-control" value="{{ $slip->fecha_retorno ? $slip->fecha_retorno->format('Y-m-d') : '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Hora de Retorno:</label>
                        <input type="time" name="hora_retorno" class="form-control" value="{{ $slip->hora_retorno ? substr((string)$slip->hora_retorno, 0, 5) : '' }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Observaciones:</label>
                        <textarea name="observaciones" class="form-control" rows="2">{{ $slip->observaciones }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white py-3 text-end border-top">
                <a href="{{ route('vehicle-exit-slips.show', $slip->id) }}" class="btn btn-secondary me-2">Cancelar</a>
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
    $('#form-vehicle-slip-edit').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btn-submit');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: "{{ route('vehicle-exit-slips.update', $slip->id) }}",
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
                toastr.error('Error al actualizar la papeleta.');
            }
        });
    });
});
</script>
@endsection
