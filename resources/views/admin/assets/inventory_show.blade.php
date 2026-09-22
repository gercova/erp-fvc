@extends('admin.layout')
@section('title', 'Acta de Inventario: ' . $inventory->correlativo)

@section('content')
    <div class="container-fluid px-4 py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Bienes Patrimoniales</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $inventory->correlativo }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-900 fw-bold d-flex align-items-center">
                    <span class="badge bg-primary me-2 font-monospace">{{ $inventory->correlativo }}</span>
                    {{ $inventory->titulo }}
                </h1>
                <p class="text-muted small mb-0">Seguimiento de las 5 firmas oficiales y conciliación de inventario departamental</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('inventory.pdf', ['area_id' => $inventory->area_id, 'periodo' => $inventory->periodo]) }}" target="_blank"
                    class="btn btn-outline-danger shadow-sm">
                    <i class="fas fa-file-pdf me-1"></i> Ver Formato PDF
                </a>
                <a href="{{ route('inventory.index', ['area_id' => $inventory->area_id]) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver a Bienes
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Columna Izquierda: Información del Acta y Bienes Comprendidos -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-clipboard-list me-1"></i> Información del Inventario
                        </h6>
                        {!! $inventory->status_badge !!}
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-light">
                                    <span class="text-muted small d-block">Departamento / Área</span>
                                    <span class="fw-bold text-dark fs-6">{{ $inventory->area?->name }}</span>
                                    <span class="badge bg-secondary font-monospace ms-1">{{ $inventory->area?->code }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-light">
                                    <span class="text-muted small d-block">Período / Fecha</span>
                                    <span class="fw-bold text-dark fs-6">{{ $inventory->periodo }} &bull; {{ $inventory->formatted_fecha_inventario }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block">Responsable del Área</span>
                                    <span class="fw-bold text-dark">{{ $inventory->responsable }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block">Realizado Por</span>
                                    <span class="fw-bold text-dark">{{ $inventory->realizado_por }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block">Total de Bienes Inventariados</span>
                                    <span class="fw-bold text-primary fs-5">{{ $inventory->total_assets }} items</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block">Valorización Total</span>
                                    <span class="fw-bold text-success fs-5">S/ {{ number_format($inventory->total_cost, 2) }}</span>
                                </div>
                            </div>
                            @if ($inventory->observaciones)
                                <div class="col-12">
                                    <div class="p-3 border rounded">
                                        <span class="text-muted small d-block mb-1">Observaciones</span>
                                        <p class="mb-0">{{ $inventory->observaciones }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Cadena de las 5 Firmas Institucionales -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 bg-white">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-file-signature me-1"></i> Cadena de las 5 Firmas Oficiales
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach ($inventory->approvals as $approval)
                                <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark mb-0">
                                            <span class="badge bg-secondary me-1 font-monospace">{{ $approval->step_order }}</span>
                                            {{ $approval->label }}
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-user me-1"></i>
                                            {{ $approval->approver_name ?: 'Pendiente de asignación / firma' }}
                                        </small>
                                        @if ($approval->signed_at)
                                            <small class="text-success fw-semibold d-block">
                                                <i class="fas fa-check-circle me-1"></i> Firmado: {{ $approval->signed_at->format('d/m/Y H:i') }}
                                            </small>
                                        @endif
                                    </div>
                                    <div>
                                        @if ($approval->isApproved())
                                            <span class="badge bg-success text-white px-2 py-1"><i class="fas fa-check me-1"></i>Aprobado</span>
                                        @elseif ($approval->isPending())
                                            <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-clock me-1"></i>Pendiente</span>
                                        @elseif ($approval->isObserved())
                                            <span class="badge bg-secondary text-white px-2 py-1">Observado</span>
                                        @elseif ($approval->isRejected())
                                            <span class="badge bg-danger text-white px-2 py-1">Rechazado</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer bg-light text-center py-3">
                        <a href="{{ route('approvals.index') }}" class="btn btn-outline-primary btn-sm fw-bold">
                            <i class="fas fa-signature me-1"></i> Ir a Bandeja de Aprobaciones
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
