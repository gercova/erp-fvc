@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-fluid px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="edit"></i></div>
                        Editar Papeleta de Salida N° {{ $slip->correlativo }}
                    </h1>
                    <div class="small text-muted mt-1">Modifique los datos observados antes de remitir para su autorización.</div>
                </div>
                <div class="col-auto mb-3">
                    <a href="{{ route('exit-slips.show', $slip->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Cancelar y Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid px-4 pb-5">
    <form id="form-exit-slip-edit">
        @csrf
        @method('PUT')
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="m-0 fw-bold text-primary">DATOS DE LA PAPELETA</h6>
                    <small class="text-muted">IESTP "FRANCISCO VIGO CABALLERO" - UCHIZA</small>
                </div>
                <span class="badge bg-primary fs-6">{{ $slip->correlativo }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nombres y Apellidos:</label>
                        <input type="text" name="nombres_apellidos" class="form-control" value="{{ $slip->nombres_apellidos }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Condición Personal:</label>
                        <div class="d-flex gap-3 pt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_personal" id="personal_docente" value="DOCENTE" {{ $slip->tipo_personal === 'DOCENTE' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="personal_docente">Docente</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_personal" id="personal_admin" value="ADMINISTRATIVO" {{ $slip->tipo_personal === 'ADMINISTRATIVO' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="personal_admin">Administrativo</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Área / Unidad / P.E.:</label>
                        <select name="area_id" class="form-select select2">
                            <option value="">-- Seleccionar Área --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ $slip->area_id == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
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
                        <label class="form-label fw-bold">Destino General:</label>
                        <input type="text" name="destino" class="form-control" value="{{ $slip->destino }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-tasks me-1"></i> Motivo de Salida</h6>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 250px;">MOTIVO DE SALIDA</th>
                                <th style="width: 140px;" class="text-center">MARQUE</th>
                                <th>LUGAR / ESPECIFICACIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $motivos = [
                                    'Comisión de Servicios' => 'Comisión oficial de servicios institucionales',
                                    'Salud' => 'Atención médica / consulta de salud',
                                    'Asunto Judicial' => 'Citación judicial, notarial o policial',
                                    'Asunto Personal' => 'Trámites de índole estrictamente personal',
                                    'Otros' => 'Otras circunstancias no contempladas',
                                ];
                            @endphp
                            @foreach ($motivos as $motivoKey => $desc)
                                <tr>
                                    <td class="fw-bold">{{ $motivoKey }}</td>
                                    <td class="text-center">
                                        <input class="form-check-input radio-motivo" type="radio" name="motivo" value="{{ $motivoKey }}" {{ $slip->motivo === $motivoKey ? 'checked' : '' }} required>
                                    </td>
                                    <td>
                                        <input type="text" name="lugar_{{ Str::slug($motivoKey) }}" class="form-control form-control-sm input-motivo-lugar" value="{{ $slip->motivo === $motivoKey ? $slip->lugar : '' }}" placeholder="Indicar lugar para {{ $motivoKey }}...">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <input type="hidden" name="lugar" id="hidden-lugar" value="{{ $slip->lugar }}">

                <div class="mb-3">
                    <label class="form-label fw-bold">Observaciones:</label>
                    <textarea name="observaciones" class="form-control" rows="2">{{ $slip->observaciones }}</textarea>
                </div>
            </div>
            <div class="card-footer bg-white py-3 text-end border-top">
                <a href="{{ route('exit-slips.show', $slip->id) }}" class="btn btn-secondary me-2">Cancelar</a>
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
    function updateSelectedLugar() {
        let selectedMotivo = $('input[name="motivo"]:checked').val();
        let slug = selectedMotivo.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-');
        let lugarVal = $('input[name="lugar_' + slug + '"]').val();
        $('#hidden-lugar').val(lugarVal);
    }

    $('input[name="motivo"], .input-motivo-lugar').on('change input', function() {
        updateSelectedLugar();
    });

    $('#form-exit-slip-edit').on('submit', function(e) {
        e.preventDefault();
        updateSelectedLugar();

        let btn = $('#btn-submit');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: "{{ route('exit-slips.update', $slip->id) }}",
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
