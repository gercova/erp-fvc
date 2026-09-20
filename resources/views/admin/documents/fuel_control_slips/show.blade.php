@extends('admin.layout')

@section('title', 'Vale de Control N° ' . $slip->correlativo)

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center my-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('fuel-control-slips.index') }}">Vales de Control</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detalle N° {{ $slip->correlativo }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="fas fa-gas-pump text-danger me-2"></i>Vale de Control Interno N° <span class="text-danger">{{ $slip->correlativo }}</span>
            </h1>
            <p class="text-muted small mb-0">Control de combustible y lubricantes emitido por {{ $slip->user?->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('fuel-control-slips.pdf', $slip->id) }}" target="_blank" class="btn btn-outline-danger">
                <i class="fas fa-file-pdf me-1"></i> Imprimir PDF
            </a>
            @if(in_array($slip->status, ['PENDIENTE', 'OBSERVADO']))
                <a href="{{ route('fuel-control-slips.edit', $slip->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Editar
                </a>
            @endif
            <a href="{{ route('fuel-control-slips.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- Circuito de Firmas y Aprobaciones (Full Width) -->
    @include('admin.documents.partials.workflow_timeline', ['document' => $slip])

    <div class="row g-4">
        <!-- Columna Principal: Detalle del Vale -->
        <div class="col-lg-12">
            <!-- Tarjeta de Estado & Información Principal -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="fas fa-info-circle text-primary me-2"></i>Datos del Abastecimiento
                    </h6>
                    <div>{!! $slip->status_badge !!}</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-3">
                            <span class="text-muted small d-block">Fecha:</span>
                            <span class="fw-bold">{{ $slip->fecha ? $slip->fecha->format('d/m/Y') : '-' }}</span>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <span class="text-muted small d-block">Hora:</span>
                            <span class="fw-bold">{{ substr((string)$slip->hora, 0, 5) }}</span>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <span class="text-muted small d-block">Área Solicitante:</span>
                            <span class="fw-bold">{{ $slip->area?->name ?? 'No especificada' }}</span>
                        </div>

                        <div class="col-md-6">
                            <span class="text-muted small d-block">Grifo / Estación:</span>
                            <span class="fw-bold text-dark">{{ $slip->nombre_grifo }}</span>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <span class="text-muted small d-block">Requerimiento N°:</span>
                            <span class="fw-bold">{{ $slip->requerimiento_nro ?: 'S/N' }}</span>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <span class="text-muted small d-block">Orden de Compra N°:</span>
                            <span class="fw-bold">{{ $slip->orden_compra_nro ?: 'S/N' }}</span>
                        </div>

                        <div class="col-sm-6 col-md-4">
                            <span class="text-muted small d-block">Vehículo / Maquinaria:</span>
                            <span class="fw-bold">{{ $slip->vehiculo_maquina }}</span>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <span class="text-muted small d-block">Placa:</span>
                            <span class="badge bg-secondary fs-6">{{ $slip->placa ?: 'S/P' }}</span>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <span class="text-muted small d-block">Kilometraje / Horómetro:</span>
                            <span class="fw-bold">{{ $slip->kilometraje_horometro ?: 'No registrado' }}</span>
                        </div>

                        <div class="col-12">
                            <span class="text-muted small d-block">Actividad / Comisión:</span>
                            <p class="mb-0 bg-light p-2 rounded border">{{ $slip->actividad_comision }}</p>
                        </div>

                        <div class="col-12">
                            <span class="text-muted small d-block">Facturar a:</span>
                            <span class="fw-semibold text-primary">{{ $slip->facturar_a }}</span>
                        </div>

                        @if($slip->observaciones)
                        <div class="col-12">
                            <span class="text-muted small d-block">Observaciones:</span>
                            <p class="mb-0 text-muted fst-italic">{{ $slip->observaciones }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tabla de Ítems Despachados -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="fas fa-oil-can text-danger me-2"></i>Combustible y Lubricantes Despachados
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th style="width: 100px;" class="text-center">Cantidad</th>
                                    <th style="width: 120px;" class="text-center">U. Medida</th>
                                    <th>Descripción</th>
                                    <th style="width: 120px;" class="text-end">P. Unitario</th>
                                    <th style="width: 120px;" class="text-end">Importe (S/)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slip->items as $idx => $it)
                                    <tr>
                                        <td class="text-center">{{ $idx + 1 }}</td>
                                        <td class="text-center fw-bold">{{ number_format($it->cantidad, 2) }}</td>
                                        <td class="text-center">{{ $it->unidad_medida }}</td>
                                        <td>{{ $it->descripcion }}</td>
                                        <td class="text-end">{{ $it->precio_unitario ? 'S/ ' . number_format($it->precio_unitario, 2) : '-' }}</td>
                                        <td class="text-end fw-semibold">S/ {{ number_format($it->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No hay ítems registrados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="5" class="text-end fw-bold">TOTAL GENERAL (S/):</td>
                                    <td class="text-end fw-bold text-danger fs-6">S/ {{ number_format($slip->total_general, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
