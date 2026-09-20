@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-fluid px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="calendar"></i></div>
                        Papeleta de Vacaciones N° {{ $slip->correlativo }}
                    </h1>
                    <div class="small text-muted mt-1">Estado actual: {!! $slip->status_badge !!}</div>
                </div>
                <div class="col-auto mb-3 d-flex gap-2">
                    <a href="{{ route('vacation-exit-slips.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                    <a href="{{ route('vacation-exit-slips.pdf', $slip->id) }}" target="_blank" class="btn btn-danger">
                        <i class="fas fa-file-pdf me-1"></i> Imprimir PDF Oficial
                    </a>
                    @if (in_array($slip->status, ['PENDIENTE', 'OBSERVADO']))
                        <a href="{{ route('vacation-exit-slips.edit', $slip->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid px-4 pb-5">
    @include('admin.documents.partials.workflow_timeline', ['document' => $slip])

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-user me-1"></i> I.- Datos del Servidor</h6>
            <span class="badge bg-light text-dark border">{{ $slip->condicion_laboral }}</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <span class="text-muted small d-block">Apellidos y Nombres:</span>
                    <strong class="text-dark fs-6">{{ $slip->apellidos_nombres }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">DNI:</span>
                    <strong class="text-dark">{{ $slip->dni }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Cargo / Especialidad:</span>
                    <strong class="text-dark">{{ $slip->cargo_especialidad }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">Área / Programa de Estudios:</span>
                    <strong class="text-dark">{{ $slip->area_programa_estudios }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">Dependencia en Organigrama:</span>
                    <strong class="text-dark">{{ $slip->area?->name ?: 'No especificada' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-plane-departure me-1"></i> II.- Datos de la Salida</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <span class="text-muted small d-block">Período de Vacaciones:</span>
                    <strong class="text-dark fs-6">{{ $slip->fecha_desde ? $slip->fecha_desde->format('d/m/Y') : '-' }} al {{ $slip->fecha_hasta ? $slip->fecha_hasta->format('d/m/Y') : '-' }}</strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted small d-block">Total de Días Autorizados:</span>
                    <strong class="text-primary fs-5">{{ $slip->total_dias }} días</strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted small d-block">Resolución Directoral (R.D. N°):</span>
                    <strong class="text-dark">{{ $slip->resolucion_directoral ?: 'En trámite / Por regularizar' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="alert alert-secondary border-0 mb-0">
                <h6 class="fw-bold mb-1">III.- DECLARACIÓN:</h6>
                <p class="mb-0">{{ $slip->declaracion }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
