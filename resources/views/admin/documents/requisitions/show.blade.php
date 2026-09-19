@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-fluid px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="file-text"></i></div>
                        Requerimiento N° {{ $requisition->correlativo }}
                    </h1>
                    <div class="small text-muted mt-1">Estado actual: {!! $requisition->status_badge !!}</div>
                </div>
                <div class="col-auto mb-3 d-flex gap-2">
                    <a href="{{ route('requisitions.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                    <a href="{{ route('requisitions.pdf', $requisition->id) }}" target="_blank" class="btn btn-danger">
                        <i class="fas fa-file-pdf me-1"></i> Imprimir PDF Oficial
                    </a>
                    @if (in_array($requisition->status, ['PENDIENTE', 'OBSERVADO']))
                        <a href="{{ route('requisitions.edit', $requisition->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid px-4 pb-5">
    @include('admin.documents.partials.workflow_timeline', ['document' => $requisition])

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-info-circle me-1"></i> Información General</h6>
            <span class="badge bg-light text-dark border">Emisión: {{ $requisition->fecha ? $requisition->fecha->format('d/m/Y') : '-' }}</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <span class="text-muted small d-block">AL (Destinatario):</span>
                    <strong class="text-dark">{{ $requisition->dirigido_a }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">Área / P.E.:</span>
                    <strong class="text-dark">{{ $requisition->area?->name ?: 'No especificada' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">DE (Solicitante):</span>
                    <strong class="text-dark">{{ $requisition->de }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">Cargo:</span>
                    <strong class="text-dark">{{ $requisition->cargo }}</strong>
                </div>
                <div class="col-12 border-top pt-3">
                    <span class="text-muted small d-block">I.- Finalidad del Requerimiento:</span>
                    <div class="p-3 bg-light rounded text-dark mt-1">{{ $requisition->finalidad }}</div>
                </div>
                <div class="col-12">
                    <span class="text-muted small d-block">II.- Fuente de Financiamiento / Actividad:</span>
                    <div class="mt-1">
                        <span class="badge bg-primary text-uppercase me-2">{{ str_replace('_', ' ', $requisition->fuente_financiamiento) }}</span>
                        @if ($requisition->fuente_especificar)
                            <strong>{{ $requisition->fuente_especificar }}</strong>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-list me-1"></i> III.- Detalle de Bienes y Servicios Solicitados</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th style="width: 50px;">N°</th>
                            <th style="width: 120px;">Cantidad</th>
                            <th>Descripción del Bien o Servicio</th>
                            <th style="width: 160px;">Precio Unitario (S/)</th>
                            <th style="width: 160px;">Precio Total (S/)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requisition->items as $item)
                            <tr>
                                <td class="text-center fw-bold">{{ $item->item_number }}</td>
                                <td class="text-center">{{ number_format($item->cantidad, 2) }}</td>
                                <td>{{ $item->descripcion }}</td>
                                <td class="text-end">S/ {{ number_format($item->precio_unitario, 2) }}</td>
                                <td class="text-end fw-bold">S/ {{ number_format($item->precio_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="4" class="text-end fw-bold fs-6">TOTAL GENERAL:</td>
                            <td class="text-end fw-bold fs-6 text-primary">S/ {{ number_format($requisition->total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-clipboard-check me-1"></i> IV.- Justificación y Condiciones Administrativas</h6>
        </div>
        <div class="card-body p-4">
            <div class="mb-3">
                <span class="text-muted small d-block">Justificación Institucional:</span>
                <div class="p-3 bg-light rounded text-dark mt-1">{{ $requisition->justificacion ?: 'Sin justificación adicional.' }}</div>
            </div>
            <div class="alert alert-secondary mb-0 border-0">
                <strong>Condiciones Administrativas:</strong>
                <p class="small mb-0 mt-1">{{ $requisition->condiciones_admin }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
