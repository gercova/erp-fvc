@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-fluid px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="log-out"></i></div>
                        Papeleta de Salida N° {{ $slip->correlativo }}
                    </h1>
                    <div class="small text-muted mt-1">Estado actual: {!! $slip->status_badge !!}</div>
                </div>
                <div class="col-auto mb-3 d-flex gap-2">
                    <a href="{{ route('exit-slips.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                    <a href="{{ route('exit-slips.pdf', $slip->id) }}" target="_blank" class="btn btn-danger">
                        <i class="fas fa-file-pdf me-1"></i> Imprimir PDF Oficial
                    </a>
                    @if (in_array($slip->status, ['PENDIENTE', 'OBSERVADO']))
                        <a href="{{ route('exit-slips.edit', $slip->id) }}" class="btn btn-warning">
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
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-user-clock me-1"></i> Datos de la Salida</h6>
            <span class="badge {{ $slip->tipo_personal === 'DOCENTE' ? 'bg-primary' : 'bg-secondary' }}">{{ $slip->tipo_personal }}</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <span class="text-muted small d-block">Personal Solicitante:</span>
                    <strong class="text-dark fs-6">{{ $slip->nombres_apellidos }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">Área / Unidad / P.E.:</span>
                    <strong class="text-dark">{{ $slip->area?->name ?: 'No especificada' }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Fecha y Hora de Salida:</span>
                    <strong class="text-dark">{{ $slip->fecha_salida ? $slip->fecha_salida->format('d/m/Y') : '-' }} a las {{ substr((string)$slip->hora_salida, 0, 5) }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Fecha y Hora de Retorno:</span>
                    <strong class="text-dark">{{ $slip->fecha_retorno ? $slip->fecha_retorno->format('d/m/Y') : '-' }} a las {{ $slip->hora_retorno ? substr((string)$slip->hora_retorno, 0, 5) : '-' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">Destino General:</span>
                    <strong class="text-dark">{{ $slip->destino ?: '-' }}</strong>
                </div>

                <div class="col-12 border-top pt-3">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Motivo Marcado</th>
                                    <th>Lugar / Especificación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold text-primary"><i class="fas fa-check-circle me-1"></i> {{ $slip->motivo }}</td>
                                    <td>{{ $slip->lugar ?: ($slip->motivo_especificar ?: 'Conforme a solicitud') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($slip->observaciones)
                    <div class="col-12">
                        <span class="text-muted small d-block">Observaciones:</span>
                        <div class="p-3 bg-light rounded text-dark mt-1">{{ $slip->observaciones }}</div>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-footer bg-light py-2">
            <small class="text-muted"><i class="fas fa-info-circle me-1"></i> {{ $slip->nota_salud }}</small>
        </div>
    </div>
</div>
@endsection
