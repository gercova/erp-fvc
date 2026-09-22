@extends('admin.layout')
@section('title', 'Ficha Técnica: ' . $asset->descripcion)

@section('content')
    <div class="container-fluid px-4 py-4">
        <!-- Breadcrumb & Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Bienes Patrimoniales</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('inventory.index', ['area_id' => $asset->area_id]) }}">{{ $asset->area?->name }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Ficha Activo #{{ $asset->formatted_orden }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-900 fw-bold d-flex align-items-center">
                    <span class="badge bg-primary me-2 font-monospace">ORD #{{ $asset->formatted_orden }}</span>
                    {{ $asset->descripcion }}
                </h1>
                <p class="text-muted small mb-0">Ficha técnica y control patrimonial del bien institucional</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('inventory.qr_labels', ['asset_id' => $asset->id]) }}" target="_blank" class="btn btn-outline-dark shadow-sm">
                    <i class="fas fa-print me-1"></i> Imprimir Etiqueta QR
                </a>
                <a href="{{ route('inventory.edit', $asset->id) }}" class="btn btn-warning shadow-sm text-dark">
                    <i class="fas fa-edit me-1"></i> Editar
                </a>
                <a href="{{ route('inventory.index', ['area_id' => $asset->area_id]) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Datos Principales -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-file-alt me-1"></i> Especificaciones Técnicas y Registrales
                        </h6>
                        <div class="d-flex gap-2">
                            {!! $asset->condicion_badge !!}
                            {!! $asset->tipo_adquisicion_badge !!}
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-light">
                                    <span class="text-muted small d-block">Departamento / Área</span>
                                    <span class="fw-bold text-dark fs-6">{{ $asset->area?->name }}</span>
                                    <span class="badge bg-secondary font-monospace ms-1">{{ $asset->area?->code }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-light">
                                    <span class="text-muted small d-block">Ubicación Física</span>
                                    <span class="fw-bold text-dark fs-6"><i class="fas fa-map-marker-alt text-danger me-1"></i>{{ $asset->ubicacion }}</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block">Código de Producto (Catálogo SBN)</span>
                                    <span class="font-monospace fw-bold text-primary fs-6">{{ $asset->codigo_producto ?: 'No asignado' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block">Código Interno / Patrimonial</span>
                                    <span class="font-monospace fw-bold text-secondary fs-6">{{ $asset->codigo ?: 'Sin código' }}</span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block">Marca</span>
                                    <span class="fw-bold text-dark">{{ $asset->marca }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block">Modelo</span>
                                    <span class="fw-bold text-dark">{{ $asset->modelo }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block">Número de Serie</span>
                                    <span class="font-monospace fw-bold text-dark">{{ $asset->serie }}</span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block">Costo Declarado</span>
                                    <span class="fw-bold text-success fs-5">S/ {{ number_format((float)$asset->costo, 2) }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block">Fecha de Adquisición</span>
                                    <span class="fw-bold text-dark">{{ $asset->fecha_adquisicion ? $asset->fecha_adquisicion->format('d/m/Y') : ($asset->anio_adquisicion ?: 'No registrada') }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block">Custodio en el Área (ENC. AREA)</span>
                                    <span class="fw-bold text-dark">{{ $asset->custodio ?: 'Sin custodio asignado' }}</span>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-3 border rounded">
                                    <span class="text-muted small d-block mb-1">Observaciones / Detalles</span>
                                    <p class="mb-0 text-dark">{{ $asset->observaciones ?: 'Ninguna observación registrada.' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de Auditoría / Conciliación Física -->
                <div class="card shadow-sm border-0 bg-white">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-clipboard-check me-1"></i> Estado de Conciliación y Auditoría Física
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-reconcile-toggle" data-id="{{ $asset->id }}">
                            <i class="fas fa-sync-alt me-1"></i> Alternar Verificación
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between p-3 border rounded {{ $asset->is_reconciled ? 'bg-success-subtle border-success-subtle' : 'bg-warning-subtle border-warning-subtle' }}">
                            <div class="d-flex align-items-center">
                                <i class="fas {{ $asset->is_reconciled ? 'fa-check-circle text-success' : 'fa-clock text-warning' }} fs-2 me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold {{ $asset->is_reconciled ? 'text-success' : 'text-warning-emphasis' }}">
                                        {{ $asset->is_reconciled ? 'Activo Físicamente Verificado' : 'Pendiente de Conciliación Física' }}
                                    </h6>
                                    <small class="text-muted">
                                        @if ($asset->is_reconciled)
                                            Auditado el {{ $asset->reconciled_at?->format('d/m/Y H:i') }} por {{ $asset->reconciledBy?->nombres ?? 'Auditor' }}
                                        @else
                                            Este bien aún no ha sido corroborado en la toma de inventario físico del período.
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Código QR y Fotografía -->
            <div class="col-lg-4">
                <!-- Tarjeta de Código QR -->
                <div class="card shadow-sm border-0 mb-4 bg-white text-center">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-qrcode me-1"></i> Código QR Oficial
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="p-3 bg-light rounded d-inline-block shadow-sm mb-3 border">
                            {!! $qrSvg !!}
                        </div>
                        <div class="font-monospace fw-bold text-dark fs-6 mb-1">
                            {{ $asset->codigo ?: ($asset->codigo_producto ?: 'ACT-' . $asset->id) }}
                        </div>
                        <div class="small text-muted mb-3 font-monospace">UUID: {{ $asset->uuid }}</div>

                        <div class="d-grid gap-2">
                            <a href="{{ route('inventory.qr_labels', ['asset_id' => $asset->id]) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-tag me-1"></i> Formato Etiqueta Adhesiva
                            </a>
                            <a href="{{ route('inventory.public_verify', $asset->uuid) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i> Probar Escaneo Público
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Fotografía del Activo -->
                <div class="card shadow-sm border-0 bg-white text-center">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-camera me-1"></i> Fotografía del Bien
                        </h6>
                    </div>
                    <div class="card-body">
                        @if ($asset->foto_path)
                            <img src="{{ asset($asset->foto_path) }}" alt="{{ $asset->descripcion }}"
                                class="img-fluid rounded shadow-sm border" style="max-height: 260px; object-fit: cover;">
                        @else
                            <div class="p-4 bg-light rounded border text-muted">
                                <i class="fas fa-image fs-1 d-block mb-2 text-secondary"></i>
                                <div class="small">No se adjuntó fotografía para este bien tangible.</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).on('click', '.btn-reconcile-toggle', function() {
            const assetId = $(this).data('id');
            $.ajax({
                url: "{{ url('/inventory') }}/" + assetId + "/reconcile",
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(resp) {
                    location.reload();
                },
                error: function(err) {
                    Swal.fire('Error', 'No se pudo alternar el estado de auditoría.', 'error');
                }
            });
        });
    </script>
@endsection
