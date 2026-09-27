@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Editar Actividad Productiva</h1>
            <p class="text-muted small mb-0">{{ $activity->name }} ({{ $activity->code }})</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('productive_activities.show', $activity->id) }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-eye me-1"></i> Ver Ficha
            </a>
            <a href="{{ route('productive_activities.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver al Listado
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i> Por favor corrija los siguientes errores:</h6>
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-edit me-1"></i> Actualizar Parámetros de la Actividad</h6>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('productive_activities.update', $activity->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Código de la Actividad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-monospace" name="code" value="{{ old('code', $activity->code) }}" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nombre de la Actividad Productiva <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $activity->name) }}" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tipo de Actividad <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" required>
                            @foreach ($types as $key => $label)
                                <option value="{{ $key }}" {{ old('type', $activity->type) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Código de Centro de Costos</label>
                        <input type="text" class="form-control font-monospace" name="cost_center_code" value="{{ old('cost_center_code', $activity->cost_center_code) }}">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Área Institucional Responsable <span class="text-danger">*</span></label>
                        <select class="form-select" name="area_id" required>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ old('area_id', $activity->area_id) == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Responsable / Encargado de la Actividad</label>
                        <select class="form-select" name="head_user_id">
                            <option value="">-- Sin asignar --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ old('head_user_id', $activity->head_user_id) == $user->id ? 'selected' : '' }}>
                                    {{ $user->nombres }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Fuente de Financiamiento Predeterminada</label>
                        <select class="form-select" name="default_fund_source_id">
                            <option value="">-- Seleccionar Cuenta / Fondo --</option>
                            @foreach ($fundSources as $fund)
                                <option value="{{ $fund->id }}" {{ old('default_fund_source_id', $activity->default_fund_source_id) == $fund->id ? 'selected' : '' }}>
                                    {{ $fund->name }} ({{ $fund->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Estado Operativo <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" required>
                            <option value="ACTIVA" {{ old('status', $activity->status) === 'ACTIVA' ? 'selected' : '' }}>ACTIVA</option>
                            <option value="EN_MANTENIMIENTO" {{ old('status', $activity->status) === 'EN_MANTENIMIENTO' ? 'selected' : '' }}>EN MANTENIMIENTO</option>
                            <option value="INACTIVA" {{ old('status', $activity->status) === 'INACTIVA' ? 'selected' : '' }}>INACTIVA</option>
                            <option value="CERRADA" {{ old('status', $activity->status) === 'CERRADA' ? 'selected' : '' }}>CERRADA</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">% Avance Físico Actual</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="execution_progress_percent" value="{{ old('execution_progress_percent', $activity->execution_progress_percent) }}">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold">Última Observación de Monitoreo</label>
                        <input type="text" class="form-control" name="monitoring_observations" value="{{ old('monitoring_observations', $activity->monitoring_observations) }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Descripción General / Alcance Institucional</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description', $activity->description) }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('productive_activities.index') }}" class="btn btn-light border">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i class="fas fa-save me-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
