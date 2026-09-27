@extends('admin.layout')
@section('title', 'Nueva Campaña / Plan de Producción')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('production.plans.index') }}">Planes de Producción</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nuevo Plan</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-plus-circle me-2 text-primary"></i>Registrar Plan de Producción / Campaña
            </h1>
            <p class="text-muted small mb-0">Configure el cronograma, objetivos y metas de rendimiento para el proyecto</p>
        </div>
        <div>
            <a href="{{ route('production.plans.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver al Listado
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <strong class="d-block mb-1"><i class="fas fa-exclamation-triangle me-2"></i>Por favor corrija los siguientes errores:</strong>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('production.plans.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            <!-- Left Column: Primary Plan Data -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-info-circle me-1"></i> Datos Generales de la Campaña
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">
                                    Actividad Productiva <span class="text-danger">*</span>
                                </label>
                                <select name="productive_activity_id" class="form-select" required>
                                    @foreach($activities as $act)
                                        <option value="{{ $act->id }}" {{ old('productive_activity_id', $selectedActivityId) == $act->id ? 'selected' : '' }}>
                                            {{ $act->name }} ({{ $act->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Código Interno (Opcional)</label>
                                <input type="text" name="campaign_code" class="form-control font-monospace"
                                    placeholder="Ej: CAMP-2026-001 (Auto si vacío)" value="{{ old('campaign_code') }}">
                                <small class="text-muted">Si se deja en blanco se generará automáticamente</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">
                                    Nombre de la Campaña / Temporada <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" class="form-control" required
                                    placeholder="Ej: Campaña Palma Aceitera 2026, Campaña Trucha Arcoíris I" value="{{ old('name') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">
                                    Fecha de Inicio <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="start_date" class="form-control" required
                                    value="{{ old('start_date', date('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Fecha Estimada de Cierre</label>
                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">Objetivos y Metas de Producción</label>
                                <textarea name="production_targets" class="form-control" rows="3"
                                    placeholder="Ej: Lograr 180 toneladas de fruta de palma para entrega a Palmas del Espino; tasa de mortalidad menor al 8%...">{{ old('production_targets') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Goals, Budget & Status -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-bullseye me-1"></i> Metas y Presupuesto
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Meta Cuantitativa de Cosecha</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" name="target_quantity" class="form-control"
                                    placeholder="0.00" value="{{ old('target_quantity', '0.00') }}">
                                <input type="text" name="target_unit" class="form-control" style="max-width: 100px;"
                                    placeholder="KG / TN" value="{{ old('target_unit', 'KG') }}">
                            </div>
                            <small class="text-muted">Volumen proyectado a cosechar</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Presupuesto Estimado (S/)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">S/</span>
                                <input type="number" step="0.01" min="0" name="budget_allocated" class="form-control"
                                    placeholder="0.00" value="{{ old('budget_allocated', '0.00') }}">
                            </div>
                            <small class="text-muted">Costo proyectado en insumos y jornales</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Estado del Plan <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="planned" {{ old('status') == 'planned' ? 'selected' : '' }}>Planificada (Por Iniciar)</option>
                                <option value="in_progress" {{ old('status', 'in_progress') == 'in_progress' ? 'selected' : '' }}>En Ejecución (Activa)</option>
                                <option value="closed" {{ old('status') == 'closed' ? 'selected' : '' }}>Cerrada / Liquidada</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Observaciones Adicionales</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Notas de campo...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold">
                                <i class="fas fa-save me-1"></i> Guardar Plan de Producción
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
