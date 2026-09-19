@extends('admin.layout')

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-fluid px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title text-primary">
                        <div class="page-header-icon"><i data-feather="file-text"></i></div>
                        Declaración Jurada N° {{ $declaration->correlativo }}
                    </h1>
                    <div class="small text-muted mt-1">Estado actual: {!! $declaration->status_badge !!}</div>
                </div>
                <div class="col-auto mb-3 d-flex gap-2">
                    <a href="{{ route('expense-declarations.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                    <a href="{{ route('expense-declarations.pdf', $declaration->id) }}" target="_blank" class="btn btn-danger">
                        <i class="fas fa-file-pdf me-1"></i> Imprimir PDF Oficial
                    </a>
                    @if (in_array($declaration->status, ['PENDIENTE', 'OBSERVADO']))
                        <a href="{{ route('expense-declarations.edit', $declaration->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid px-4 pb-5">
    @include('admin.documents.partials.workflow_timeline', ['document' => $declaration])

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-user-check me-1"></i> Datos del Servidor y Documento</h6>
            <span class="badge bg-light text-dark border">Fecha: {{ $declaration->fecha ? $declaration->fecha->format('d/m/Y') : '-' }}</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <span class="text-muted small d-block">Servidor (a):</span>
                    <strong class="text-dark">{{ $declaration->servidor_nombres }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">DNI:</span>
                    <strong class="text-dark">{{ $declaration->dni }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Cargo:</span>
                    <strong class="text-dark">{{ $declaration->cargo }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">Área / Unidad / P.E.:</span>
                    <strong class="text-dark">{{ $declaration->area?->name ?: 'No especificada' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">Lugar de Emisión:</span>
                    <strong class="text-dark">{{ $declaration->lugar }}</strong>
                </div>
                <div class="col-12 border-top pt-3">
                    <span class="text-muted small d-block">Conceptos Declarados:</span>
                    <div class="p-3 bg-light rounded text-dark mt-1">{{ $declaration->conceptos }}</div>
                </div>
                <div class="col-12">
                    <span class="text-muted small d-block">Monto en Letras:</span>
                    <strong class="text-primary">{{ $declaration->total_letras }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-receipt me-1"></i> Detalle de la Gestión</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th style="width: 150px;">Fecha</th>
                            <th>Detalle de la Gestión</th>
                            <th style="width: 180px;">Importe (S/)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($declaration->items as $item)
                            <tr>
                                <td class="text-center">{{ $item->fecha ? $item->fecha->format('d/m/Y') : '-' }}</td>
                                <td>{{ $item->detalle_gestion }}</td>
                                <td class="text-end fw-bold">S/ {{ number_format($item->importe, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2" class="text-end fw-bold fs-6">TOTAL GENERAL:</td>
                            <td class="text-end fw-bold fs-6 text-primary">S/ {{ number_format($declaration->total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="alert alert-secondary border-0 mb-0">
                <h6 class="fw-bold mb-1">CERTIFICO:</h6>
                <p class="mb-0">{{ $declaration->certifico }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
