@extends('admin.layout')
@section('title', 'Editar Plan de Producción')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('production.plans.index') }}">Planes de Producción</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('production.plans.show', $campaign->id) }}">{{ $campaign->campaign_code }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Editar</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-edit me-2 text-primary"></i>Editar Plan: {{ $campaign->name }}
            </h1>
            <p class="text-muted small mb-0">Código: <span class="font-monospace fw-bold">{{ $campaign->campaign_code }}</span></p>
        </div>
        <div>
            <a href="{{ route('production.plans.show', $campaign->id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver a la Campaña
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

    <form action="{{ route('production.plans.update', $campaign->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-info-circle me-1"></i> Datos de la Campaña
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
                                        <option value="{{ $act->id }}" {{ old('productive_activity_id', $campaign->productive_activity_id) == $act->id ? 'selected' : '' }}>
                                            {{ $act->name }} ({{ $act->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Código Interno</label>
                                <input type="text" name="campaign_code" class="form-control font-monospace"
                                    value="{{ old('campaign_code', $campaign->campaign_code) }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">
                                    Nombre de la Campaña / Temporada <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" class="form-control" required
                                    value="{{ old('name', $campaign->name) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">
                                    Fecha de Inicio <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="start_date" class="form-control" required
                                    value="{{ old('start_date', $campaign->start_date ? $campaign->start_date->format('Y-m-d') : '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Fecha de Cierre</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ old('end_date', $campaign->end_date ? $campaign->end_date->format('Y-m-d') : '') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">Objetivos y Metas de Producción</label>
                                <textarea name="production_targets" class="form-control" rows="3">{{ old('production_targets', $campaign->production_targets) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                                    value="{{ old('target_quantity', $campaign->target_quantity) }}">
                                <input type="text" name="target_unit" class="form-control" style="max-width: 100px;"
                                    value="{{ old('target_unit', $campaign->target_unit) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Presupuesto Estimado (S/)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">S/</span>
                                <input type="number" step="0.01" min="0" name="budget_allocated" class="form-control"
                                    value="{{ old('budget_allocated', $campaign->budget_allocated) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Estado del Plan <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="planned" {{ old('status', $campaign->status) == 'planned' ? 'selected' : '' }}>Planificada (Por Iniciar)</option>
                                <option value="in_progress" {{ old('status', $campaign->status) == 'in_progress' ? 'selected' : '' }}>En Ejecución (Activa)</option>
                                <option value="closed" {{ old('status', $campaign->status) == 'closed' ? 'selected' : '' }}>Cerrada / Liquidada</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Observaciones Adicionales</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $campaign->notes) }}</textarea>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold">
                                <i class="fas fa-save me-1"></i> Actualizar Plan de Producción
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
