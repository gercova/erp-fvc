@extends('admin.layout')

@section('title', 'Revisión y Firma: ' . $approval->label)

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center my-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('approvals.index') }}" class="text-decoration-none">Bandeja de Aprobaciones</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Revisión de Documento</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="fas fa-file-signature text-primary me-2"></i>{{ $approval->label }}
            </h1>
            <p class="text-muted small mb-0">Etapa de visación asignada a: <span class="badge bg-light text-dark border fw-semibold">{{ $approval->role_name ?? 'Firma Asignada' }}</span></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($pdfUrl && $pdfUrl !== '#')
                <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-outline-danger shadow-sm">
                    <i class="fas fa-file-pdf me-1"></i> Abrir PDF Externo
                </a>
            @endif
            <button type="button" class="btn btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalFullDocumentViewer">
                <i class="fas fa-expand-arrows-alt me-1"></i> Visor Pantalla Completa
            </button>
            <a href="{{ route('approvals.index') }}" class="btn btn-outline-secondary shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver a la Bandeja
            </a>
        </div>
    </div>

    <!-- Alert si el documento está anulado o no existe -->
    @if(!$document)
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
            <div>
                <h6 class="fw-bold mb-1">Documento Original No Encontrado</h6>
                <p class="small mb-0">El registro del documento original asociado a este paso de aprobación (ID: #{{ $approval->document_id }}) no se encuentra en la base de datos o fue eliminado permanentemente.</p>
            </div>
        </div>
    @elseif(!empty($isDocumentAnulado))
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="fas fa-ban fa-2x text-danger me-3"></i>
            <div>
                <h6 class="fw-bold text-dark mb-1">Documento Anulado</h6>
                <p class="small text-muted mb-0">
                    Este documento se encuentra en estado <strong>ANULADO</strong>
                    @if(method_exists($document, 'trashed') && $document->trashed() && $document->deleted_at)
                        (eliminado del sistema el {{ $document->deleted_at->format('d/m/Y H:i') }}).
                    @elseif($document->updated_at)
                        (actualizado el {{ $document->updated_at->format('d/m/Y H:i') }}).
                    @endif
                    La información se presenta únicamente como registro histórico de auditoría. Las acciones de firma han sido deshabilitadas.
                </p>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Columna Izquierda: Visor del Documento a Aprobar -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="fs-5 text-primary"><i class="fas fa-file-invoice"></i></span>
                        <div>
                            <h6 class="m-0 fw-bold text-dark d-inline">
                                Visor:
                                @if($document)
                                    <span class="text-danger">{{ $document->correlativo ?? ($document->code ?? ($document->closure_code ?? 'ID: #' . $document->id)) }}</span>
                                @else
                                    <span class="text-muted">Documento #{{ $approval->document_id }}</span>
                                @endif
                            </h6>
                            @php
                                $docType = class_basename($approval->document_type);
                                $typeBadges = [
                                    'Requisition'           => ['Requerimiento', 'bg-primary'],
                                    'ExpenseDeclaration'    => ['Declaración Jurada', 'bg-success'],
                                    'ExitSlip'              => ['Papeleta Salida', 'bg-info text-dark'],
                                    'VehicleExitSlip'       => ['Papeleta Vehículo', 'bg-warning text-dark'],
                                    'VacationExitSlip'      => ['Papeleta Vacaciones', 'bg-purple text-white'],
                                    'FuelControlSlip'       => ['Vale de Combustible', 'bg-danger'],
                                    'AssetInventory'        => ['Inventario Patrimonial', 'bg-dark'],
                                    'ProductiveActivity'    => ['Actividad Productiva', 'bg-indigo text-white'],
                                    'ActivityPeriodClosure' => ['Cierre de Período', 'bg-secondary'],
                                ];
                                $badgeInfo = $typeBadges[$docType] ?? [$docType, 'bg-secondary'];
                            @endphp
                            <span class="badge {{ $badgeInfo[1] }} ms-2" style="{{ $docType === 'VacationExitSlip' ? 'background:#6f42c1;' : '' }}">
                                {{ $badgeInfo[0] }}
                            </span>
                            @if(!empty($isDocumentAnulado))
                                <span class="badge bg-danger"><i class="fas fa-ban me-1"></i>ANULADO</span>
                            @elseif($document && $document->status)
                                <span class="badge bg-light text-dark border">{{ $document->status }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Pestañas del Visor (Detalle vs PDF) -->
                    <ul class="nav nav-pills nav-pills-custom" id="viewerPills" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active btn-sm" id="pills-detail-tab" data-bs-toggle="pill" data-bs-target="#pills-detail" type="button" role="tab" aria-controls="pills-detail" aria-selected="true">
                                <i class="fas fa-list-check me-1"></i> Detalle de Ítems
                            </button>
                        </li>
                        @if($pdfUrl && $pdfUrl !== '#')
                            <li class="nav-item" role="presentation">
                                <button class="nav-link btn-sm" id="pills-pdf-tab" data-bs-toggle="pill" data-bs-target="#pills-pdf" type="button" role="tab" aria-controls="pills-pdf" aria-selected="false">
                                    <i class="fas fa-file-pdf text-danger me-1"></i> Formato PDF
                                </button>
                            </li>
                        @endif
                    </ul>
                </div>

                <div class="card-body p-3 p-md-4">
                    <div class="tab-content" id="viewerTabContent">
                        <!-- TAB 1: DETALLE ESTRUCTURADO DEL DOCUMENTO -->
                        <div class="tab-pane fade show active" id="pills-detail" role="tabpanel" aria-labelledby="pills-detail-tab">
                            @if(!$document)
                                <div class="text-center py-5">
                                    <i class="fas fa-folder-open text-muted fa-3x mb-3"></i>
                                    <h6 class="fw-bold text-muted">Información no disponible</h6>
                                    <p class="text-muted small">No es posible mostrar el contenido detallado debido a que el registro del documento no existe.</p>
                                </div>
                            @else
                                <!-- 1. REQUERIMIENTO -->
                                @if($docType === 'Requisition')
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Número de Requerimiento:</span>
                                            <span class="fw-bold text-danger fs-6">{{ $document->correlativo ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Fecha de Emisión:</span>
                                            <span class="fw-bold">{{ $document->fecha ? $document->fecha->format('d/m/Y') : '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Solicitante (De):</span>
                                            <span class="fw-semibold">{{ $document->de ?? ($document->user?->nombres ?? '-') }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Destinatario (Para):</span>
                                            <span class="fw-semibold">{{ $document->para ?? '-' }}</span>
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted small d-block">Área Solicitante:</span>
                                            <span class="fw-bold text-primary">{{ $document->area?->name ?? 'Área Institucional' }}</span>
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted small d-block">Justificación / Objeto del Requerimiento:</span>
                                            <div class="bg-light p-3 rounded border text-dark">{{ $document->justificacion ?: 'Sin justificación registrada.' }}</div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted small fw-bold text-uppercase"><i class="fas fa-boxes me-1"></i> Bienes o Servicios Requeridos:</span>
                                                <span class="badge bg-secondary">{{ count($document->items ?? []) }} ítems</span>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered table-hover align-middle mb-0">
                                                    <thead class="table-light small">
                                                        <tr>
                                                            <th style="width: 40px;" class="text-center">#</th>
                                                            <th style="width: 80px;" class="text-center">Cant.</th>
                                                            <th style="width: 100px;" class="text-center">U.M.</th>
                                                            <th>Descripción</th>
                                                            <th style="width: 120px;" class="text-end">Total (S/)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($document->items ?? [] as $idx => $it)
                                                            <tr>
                                                                <td class="text-center text-muted small">{{ $idx + 1 }}</td>
                                                                <td class="text-center fw-bold">{{ $it->cantidad }}</td>
                                                                <td class="text-center">{{ $it->unidad_medida }}</td>
                                                                <td>{{ $it->descripcion }}</td>
                                                                <td class="text-end fw-semibold">S/ {{ number_format($it->total, 2) }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-center text-muted py-3">No hay ítems registrados en este requerimiento.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="table-light">
                                                            <td colspan="4" class="text-end fw-bold">TOTAL ESTIMADO:</td>
                                                            <td class="text-end fw-bold text-danger fs-6">S/ {{ number_format($document->total_estimado ?? 0, 2) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                <!-- 2. DECLARACIÓN JURADA -->
                                @elseif($docType === 'ExpenseDeclaration')
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">N° Declaración:</span>
                                            <span class="fw-bold text-danger fs-6">{{ $document->correlativo ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Fecha:</span>
                                            <span class="fw-bold">{{ $document->fecha ? $document->fecha->format('d/m/Y') : '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Servidor / Comisionado:</span>
                                            <span class="fw-bold">{{ $document->nombres_apellidos ?? ($document->user?->nombres ?? '-') }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">DNI:</span>
                                            <span class="fw-semibold">{{ $document->dni ?? '-' }}</span>
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted small d-block">Motivo / Comisión de Servicio:</span>
                                            <div class="bg-light p-3 rounded border text-dark">{{ $document->motivo_comision ?: 'Sin motivo registrado.' }}</div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted small fw-bold text-uppercase"><i class="fas fa-receipt me-1"></i> Detalle de Gastos Incurridos:</span>
                                                <span class="badge bg-secondary">{{ count($document->items ?? []) }} comprobantes</span>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered table-hover align-middle mb-0">
                                                    <thead class="table-light small">
                                                        <tr>
                                                            <th style="width: 40px;" class="text-center">#</th>
                                                            <th style="width: 110px;" class="text-center">Fecha</th>
                                                            <th>Concepto del Gasto</th>
                                                            <th style="width: 120px;" class="text-end">Importe (S/)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($document->items ?? [] as $idx => $it)
                                                            <tr>
                                                                <td class="text-center text-muted small">{{ $idx + 1 }}</td>
                                                                <td class="text-center">{{ $it->fecha_comprobante ? $it->fecha_comprobante->format('d/m/Y') : '-' }}</td>
                                                                <td>{{ $it->concepto }}</td>
                                                                <td class="text-end fw-semibold">S/ {{ number_format($it->importe, 2) }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="4" class="text-center text-muted py-3">No hay gastos registrados en esta declaración.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="table-light">
                                                            <td colspan="3" class="text-end fw-bold">TOTAL SUSTENTADO:</td>
                                                            <td class="text-end fw-bold text-success fs-6">S/ {{ number_format($document->total_monto ?? 0, 2) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                <!-- 3. PAPELETA DE SALIDA -->
                                @elseif($docType === 'ExitSlip')
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">N° Papeleta de Salida:</span>
                                            <span class="fw-bold text-danger fs-6">{{ $document->correlativo ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Fecha:</span>
                                            <span class="fw-bold">{{ $document->fecha ? $document->fecha->format('d/m/Y') : '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Servidor(a):</span>
                                            <span class="fw-bold">{{ $document->servidor_nombres ?? ($document->user?->nombres ?? '-') }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Cargo / Función:</span>
                                            <span class="fw-semibold">{{ $document->cargo ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Motivo de Salida:</span>
                                            <span class="badge bg-primary fs-6">{{ $document->motivo ?? 'Comisión' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Horario Programado:</span>
                                            <span class="fw-bold">{{ substr((string)($document->hora_salida ?? ''), 0, 5) }} - {{ substr((string)($document->hora_retorno ?? ''), 0, 5) }}</span>
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted small d-block">Lugar de Destino:</span>
                                            <span class="fw-semibold text-dark">{{ $document->lugar_destino ?? '-' }}</span>
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted small d-block">Fundamentación:</span>
                                            <div class="bg-light p-3 rounded border text-dark">{{ $document->fundamentacion ?: 'Sin fundamentación registrada.' }}</div>
                                        </div>
                                    </div>

                                <!-- 4. PAPELETA VEHICULAR -->
                                @elseif($docType === 'VehicleExitSlip')
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">N° Papeleta Vehicular:</span>
                                            <span class="fw-bold text-danger fs-6">{{ $document->correlativo ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Fecha:</span>
                                            <span class="fw-bold">{{ $document->fecha ? $document->fecha->format('d/m/Y') : '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Conductor / Responsable:</span>
                                            <span class="fw-bold">{{ $document->conductor_nombres ?? ($document->user?->nombres ?? '-') }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Licencia de Conducir:</span>
                                            <span class="fw-semibold">{{ $document->licencia_conducir ?: 'No especificada' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Vehículo / Placa:</span>
                                            <span class="fw-bold">{{ $document->vehiculo_modelo ?? '-' }} - <span class="badge bg-dark">{{ $document->placa ?: 'S/P' }}</span></span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Destino:</span>
                                            <span class="fw-semibold text-dark">{{ $document->destino ?? '-' }}</span>
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted small d-block">Comisión de Servicio:</span>
                                            <div class="bg-light p-3 rounded border text-dark">{{ $document->motivo_comision ?: 'Sin motivo registrado.' }}</div>
                                        </div>
                                        @if($document->acompanantes)
                                            <div class="col-12">
                                                <span class="text-muted small d-block">Comisión / Acompañantes:</span>
                                                <p class="mb-0 text-dark bg-light p-2 rounded border">{{ $document->acompanantes }}</p>
                                            </div>
                                        @endif
                                    </div>

                                <!-- 5. PAPELETA DE VACACIONES -->
                                @elseif($docType === 'VacationExitSlip')
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">N° Papeleta de Vacaciones:</span>
                                            <span class="fw-bold text-danger fs-6">{{ $document->correlativo ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Fecha de Solicitud:</span>
                                            <span class="fw-bold">{{ $document->fecha ? $document->fecha->format('d/m/Y') : '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Servidor(a):</span>
                                            <span class="fw-bold">{{ $document->apellidos_nombres ?? ($document->user?->nombres ?? '-') }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">DNI:</span>
                                            <span class="fw-semibold">{{ $document->dni ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Cargo / Función:</span>
                                            <span class="fw-semibold">{{ $document->cargo ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Régimen Laboral:</span>
                                            <span class="badge bg-secondary fs-6">{{ $document->regimen_laboral ?? '-' }}</span>
                                        </div>
                                        <div class="col-12">
                                            <div class="alert alert-info border-0 mb-0 shadow-sm">
                                                <div class="fw-bold mb-1"><i class="fas fa-calendar-check me-2"></i>Periodo Vacacional Solicitado:</div>
                                                <div class="fs-6">
                                                    Desde: <strong>{{ $document->periodo_desde ? $document->periodo_desde->format('d/m/Y') : '-' }}</strong> 
                                                    hasta <strong>{{ $document->periodo_hasta ? $document->periodo_hasta->format('d/m/Y') : '-' }}</strong>
                                                    <span class="badge bg-primary ms-2 fs-6">{{ $document->dias_vacaciones ?? 0 }} días efectivos</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <!-- 6. VALE DE CONTROL DE COMBUSTIBLE -->
                                @elseif($docType === 'FuelControlSlip')
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">N° Vale de Control:</span>
                                            <span class="fw-bold text-danger fs-5">{{ $document->correlativo ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Fecha y Hora:</span>
                                            <span class="fw-bold text-dark">
                                                <i class="fas fa-calendar-alt text-muted me-1"></i>
                                                {{ $document->fecha ? $document->fecha->format('d/m/Y') : '-' }} 
                                                <span class="badge bg-light text-dark border ms-1">{{ substr((string)($document->hora ?? ''), 0, 5) ?: '--:--' }}</span>
                                            </span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Grifo / Estación Proveedora:</span>
                                            <span class="fw-bold text-primary"><i class="fas fa-gas-pump me-1"></i>{{ $document->nombre_grifo ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Vehículo / Maquinaria y Placa:</span>
                                            <span class="fw-bold text-dark">
                                                <i class="fas fa-truck me-1"></i>{{ $document->vehiculo_maquina ?? '-' }} 
                                                <span class="badge bg-dark ms-1">Placa: {{ $document->placa ?: 'S/P' }}</span>
                                            </span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Kilometraje / Horómetro:</span>
                                            <span class="fw-semibold text-secondary">{{ $document->kilometraje_horometro ?: 'No registrado' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Facturar a:</span>
                                            <span class="fw-semibold small text-dark">{{ $document->facturar_a ?? 'IESTP "FRANCISCO VIGO CABALLERO"' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Requerimiento / Orden:</span>
                                            <span class="fw-semibold">Req: {{ $document->requerimiento_nro ?: '-' }} | O/C: {{ $document->orden_compra_nro ?: '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Recibido por (Conductor):</span>
                                            <span class="fw-semibold text-dark">{{ $document->recibido_por ?: ($document->user?->nombres ?? '-') }}</span>
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted small d-block">Actividad / Comisión:</span>
                                            <div class="bg-light p-3 rounded border text-dark">{{ $document->actividad_comision ?: 'Sin descripción de actividad.' }}</div>
                                        </div>
                                        @if($document->observaciones)
                                            <div class="col-12">
                                                <span class="text-muted small d-block">Observaciones Adicionales:</span>
                                                <div class="bg-light p-2 rounded border small text-muted">{{ $document->observaciones }}</div>
                                            </div>
                                        @endif
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted small fw-bold text-uppercase"><i class="fas fa-oil-can me-1 text-danger"></i> Combustibles / Lubricantes Autorizados:</span>
                                                <span class="badge bg-secondary">{{ count($document->items ?? []) }} productos</span>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered table-hover align-middle mb-0">
                                                    <thead class="table-light small">
                                                        <tr>
                                                            <th style="width: 40px;" class="text-center">#</th>
                                                            <th style="width: 90px;" class="text-center">Cantidad</th>
                                                            <th style="width: 110px;" class="text-center">U.M.</th>
                                                            <th>Descripción</th>
                                                            <th style="width: 120px;" class="text-end">P. Unitario</th>
                                                            <th style="width: 120px;" class="text-end">Total (S/)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($document->items ?? [] as $idx => $it)
                                                            <tr>
                                                                <td class="text-center text-muted small">{{ $idx + 1 }}</td>
                                                                <td class="text-center fw-bold text-dark">{{ $it->cantidad }}</td>
                                                                <td class="text-center"><span class="badge bg-light text-dark border">{{ $it->unidad_medida }}</span></td>
                                                                <td>{{ $it->descripcion }}</td>
                                                                <td class="text-end text-muted">S/ {{ number_format($it->precio_unitario ?? 0, 2) }}</td>
                                                                <td class="text-end fw-bold text-dark">S/ {{ number_format($it->total, 2) }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="6" class="text-center text-muted py-3">No hay ítems registrados en este vale.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="table-light">
                                                            <td colspan="5" class="text-end fw-bold">TOTAL GENERAL:</td>
                                                            <td class="text-end fw-bold text-danger fs-6">S/ {{ number_format($document->total_general ?? 0, 2) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                <!-- 7. ACTA DE INVENTARIO PATRIMONIAL -->
                                @elseif($docType === 'AssetInventory')
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Correlativo / Acta:</span>
                                            <span class="fw-bold text-danger fs-6">{{ $document->correlativo ?? ('INV-' . $document->periodo) }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Período Fiscal:</span>
                                            <span class="badge bg-dark fs-6">{{ $document->periodo ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Área Inventariada:</span>
                                            <span class="fw-bold text-primary">{{ $document->area?->name ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Responsable del Área:</span>
                                            <span class="fw-bold text-dark">{{ $document->responsable ?? ($document->area?->head?->nombres ?? '-') }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Realizado por:</span>
                                            <span class="fw-semibold">{{ $document->realizado_por ?? ($document->user?->nombres ?? '-') }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Fecha de Inventario:</span>
                                            <span class="fw-semibold">{{ $document->fecha_inventario ? $document->fecha_inventario->format('d/m/Y') : '-' }}</span>
                                        </div>
                                        @if($document->observaciones)
                                            <div class="col-12">
                                                <span class="text-muted small d-block">Observaciones:</span>
                                                <div class="bg-light p-2 rounded border small text-muted">{{ $document->observaciones }}</div>
                                            </div>
                                        @endif
                                        <div class="col-12">
                                            <div class="alert alert-secondary border-0 mb-0 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-boxes me-2"></i>
                                                    Total de bienes patrimoniales registrados en esta acta:
                                                    <strong>{{ method_exists($document, 'assets') ? $document->assets()->count() : '-' }}</strong>
                                                </div>
                                                @if(Route::has('inventory.index'))
                                                    <a href="{{ route('inventory.index', ['area_id' => $document->area_id]) }}" target="_blank" class="btn btn-sm btn-outline-dark">
                                                        <i class="fas fa-external-link-alt me-1"></i> Ver Módulo de Inventario
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                <!-- 8. ACTIVIDAD PRODUCTIVA -->
                                @elseif($docType === 'ProductiveActivity')
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Código de Actividad:</span>
                                            <span class="fw-bold text-danger fs-6">{{ $document->code ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Tipo:</span>
                                            <span class="badge bg-primary fs-6">{{ $document->type ?? 'Productiva' }}</span>
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted small d-block">Nombre de la Actividad:</span>
                                            <span class="fw-bold text-dark fs-6">{{ $document->name ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Área Responsable:</span>
                                            <span class="fw-semibold">{{ $document->area?->name ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Jefe / Encargado:</span>
                                            <span class="fw-semibold">{{ $document->head?->nombres ?? ($document->user?->nombres ?? '-') }}</span>
                                        </div>
                                        @if($document->description)
                                            <div class="col-12">
                                                <span class="text-muted small d-block">Descripción / Alcance:</span>
                                                <div class="bg-light p-3 rounded border text-dark">{{ $document->description }}</div>
                                            </div>
                                        @endif
                                    </div>

                                <!-- 9. CIERRE DE PERÍODO -->
                                @elseif($docType === 'ActivityPeriodClosure')
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Código de Cierre:</span>
                                            <span class="fw-bold text-danger fs-6">{{ $document->closure_code ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Período Mensual:</span>
                                            <span class="badge bg-info text-dark fs-6">{{ sprintf('%02d/%d', $document->period_month, $document->period_year) }}</span>
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted small d-block">Actividad Productiva:</span>
                                            <span class="fw-bold text-dark fs-6">{{ $document->activity?->name ?? '-' }}</span>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="bg-light p-3 rounded border text-center">
                                                <span class="text-muted small d-block">Total Ingresos:</span>
                                                <span class="fw-bold text-success fs-5">S/ {{ number_format($document->total_income ?? 0, 2) }}</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="bg-light p-3 rounded border text-center">
                                                <span class="text-muted small d-block">Total Egresos:</span>
                                                <span class="fw-bold text-danger fs-5">S/ {{ number_format($document->total_expense ?? 0, 2) }}</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="bg-light p-3 rounded border text-center">
                                                <span class="text-muted small d-block">Saldo Neto:</span>
                                                <span class="fw-bold {{ ($document->net_balance ?? 0) >= 0 ? 'text-primary' : 'text-danger' }} fs-5">
                                                    S/ {{ number_format($document->net_balance ?? 0, 2) }}
                                                </span>
                                            </div>
                                        </div>
                                        @if($document->notes)
                                            <div class="col-12">
                                                <span class="text-muted small d-block">Notas del Cierre:</span>
                                                <div class="bg-light p-3 rounded border small text-dark">{{ $document->notes }}</div>
                                            </div>
                                        @endif
                                    </div>

                                <!-- FALLBACK GENÉRICO -->
                                @else
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Documento:</span>
                                            <span class="fw-bold text-danger">{{ $document->correlativo ?? ('ID: ' . $document->id) }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block">Tipo:</span>
                                            <span class="fw-semibold">{{ $docType }}</span>
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted small d-block mb-1">Resumen de Atributos:</span>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    @foreach(collect($document->getAttributes())->except(['id', 'created_at', 'updated_at', 'deleted_at']) as $k => $v)
                                                        @if(!is_array($v) && !is_object($v) && strlen((string)$v) < 255)
                                                            <tr>
                                                                <th class="table-light text-muted small" style="width: 35%;">{{ ucwords(str_replace('_', ' ', $k)) }}</th>
                                                                <td class="small">{{ $v ?? '-' }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <!-- TAB 2: VISOR PDF OFICIAL INTEGRADO -->
                        @if($pdfUrl && $pdfUrl !== '#')
                            <div class="tab-pane fade" id="pills-pdf" role="tabpanel" aria-labelledby="pills-pdf-tab">
                                <div class="bg-light p-2 rounded border mb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="small text-muted">
                                        <i class="fas fa-file-pdf text-danger me-1"></i> Formato oficial generado por el sistema institucional.
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-light border" id="btnReloadPdf" title="Recargar vista previa">
                                            <i class="fas fa-sync-alt me-1"></i> Recargar
                                        </button>
                                        <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-sm btn-light border" title="Abrir en ventana completa">
                                            <i class="fas fa-external-link-alt me-1"></i> Abrir en Pestaña
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalFullDocumentViewer">
                                            <i class="fas fa-expand me-1"></i> Pantalla Completa
                                        </button>
                                    </div>
                                </div>
                                <div class="position-relative border rounded overflow-hidden shadow-sm" style="min-height: 650px; background: #525659;">
                                    <iframe id="inlinePdfFrame" src="{{ $pdfUrl }}" style="width: 100%; height: 680px; border: none;" allowfullscreen></iframe>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Panel de Acción y Timeline -->
        <div class="col-lg-5">
            <!-- Tarjeta de Acción: Firmar / Observar / Rechazar -->
            <div class="card shadow-sm border-0 mb-4 border-start border-4 {{ $canSign ? 'border-primary' : (empty($isDocumentAnulado) && $document && $approval->status === 'APROBADO' ? 'border-success' : 'border-secondary') }}">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="fas fa-pen-fancy text-primary me-2"></i>Acción de Firma y Dictamen
                    </h6>
                </div>
                <div class="card-body">
                    @if($canSign)
                        <div class="alert alert-primary bg-primary text-white border-0 small mb-3 shadow-sm">
                            <i class="fas fa-info-circle me-1"></i> Usted está habilitado para visar y firmar este documento como <strong>{{ $approval->role_name ?? 'Autoridad Correspondiente' }}</strong>.
                        </div>

                        <form id="formSignApproval">
                            @csrf
                            <input type="hidden" name="approval_id" value="{{ $approval->id }}">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Observaciones / Proveído (Opcional al aprobar)</label>
                                <textarea name="observations" id="approve_observations" class="form-control" rows="2" placeholder="Ej: Conforme para su tramitación..."></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success btn-lg shadow-sm" id="btn_approve">
                                    <i class="fas fa-check-circle me-2"></i> Firmar y Aprobar Documento
                                </button>
                                <div class="d-flex gap-2 mt-1">
                                    <button type="button" class="btn btn-warning w-50 shadow-sm" id="btn_open_observe">
                                        <i class="fas fa-exclamation-circle me-1"></i> Observar
                                    </button>
                                    <button type="button" class="btn btn-danger w-50 shadow-sm" id="btn_open_reject">
                                        <i class="fas fa-times-circle me-1"></i> Rechazar
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        @if(!$document)
                            <div class="alert alert-secondary border-0 text-center py-4 mb-0">
                                <i class="fas fa-exclamation-triangle fa-3x mb-3 text-warning"></i>
                                <h6 class="fw-bold mb-1">Documento No Disponible</h6>
                                <p class="small text-muted mb-0">El documento original no fue encontrado. La firma se encuentra deshabilitada.</p>
                            </div>
                        @elseif(!empty($isDocumentAnulado))
                            <div class="alert alert-secondary border-0 text-center py-4 mb-0">
                                <i class="fas fa-ban fa-3x mb-3 text-danger"></i>
                                <h6 class="fw-bold text-danger mb-1">Documento Anulado</h6>
                                <p class="small text-muted mb-0">Este documento ha sido anulado. No se permiten acciones de firma sobre documentos anulados.</p>
                            </div>
                        @elseif($approval->status === 'APROBADO')
                            <div class="alert alert-success border-0 text-center py-4 mb-0">
                                <i class="fas fa-check-double fa-3x mb-3 text-success"></i>
                                <h6 class="fw-bold mb-1">¡Paso Aprobado y Firmado!</h6>
                                <p class="small text-muted mb-2">Firmado por: <strong>{{ $approval->approver_name }}</strong></p>
                                <p class="small text-muted mb-2">Fecha: {{ $approval->signed_at ? $approval->signed_at->format('d/m/Y H:i:s') : '-' }}</p>
                                <div class="badge bg-light text-dark border p-2 text-wrap font-monospace small">
                                    Token: {{ $approval->signature_token ?: 'VIGENTE' }}
                                </div>
                                @if($approval->observations)
                                    <div class="mt-3 p-2 bg-white rounded border small text-start">
                                        <strong>Nota:</strong> {{ $approval->observations }}
                                    </div>
                                @endif
                            </div>
                        @elseif($approval->status === 'OBSERVADO')
                            <div class="alert alert-warning border-0 text-center py-4 mb-0">
                                <i class="fas fa-exclamation-triangle fa-3x mb-3 text-warning"></i>
                                <h6 class="fw-bold mb-1">Documento Observado</h6>
                                <p class="small text-muted mb-2">Por: <strong>{{ $approval->approver_name }}</strong></p>
                                <p class="small mb-0 text-start bg-white p-2 rounded border"><strong>Motivo:</strong> {{ $approval->observations }}</p>
                            </div>
                        @elseif($approval->status === 'RECHAZADO')
                            <div class="alert alert-danger border-0 text-center py-4 mb-0">
                                <i class="fas fa-times-circle fa-3x mb-3 text-danger"></i>
                                <h6 class="fw-bold mb-1">Documento Rechazado</h6>
                                <p class="small text-muted mb-2">Por: <strong>{{ $approval->approver_name }}</strong></p>
                                <p class="small mb-0 text-start bg-white p-2 rounded border"><strong>Motivo:</strong> {{ $approval->observations }}</p>
                            </div>
                        @else
                            <div class="alert alert-secondary border-0 text-center py-4 mb-0">
                                <i class="fas fa-clock fa-3x mb-3 text-muted"></i>
                                <h6 class="fw-bold mb-1">Esperando Firma</h6>
                                <p class="small text-muted mb-0">Este paso se encuentra a la espera de la firma de {{ $approval->role_name }}.</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Timeline de todos los pasos del documento -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="fas fa-stream text-primary me-2"></i>Flujo Completo de Aprobación
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline-workflow">
                        @foreach($allSteps as $st)
                            <div class="d-flex mb-3">
                                <div class="me-3 text-center">
                                    @if($st->status === 'APROBADO')
                                        <span class="badge bg-success rounded-circle p-2" title="Aprobado"><i class="fas fa-check"></i></span>
                                    @elseif($st->status === 'OBSERVADO')
                                        <span class="badge bg-warning text-dark rounded-circle p-2" title="Observado"><i class="fas fa-exclamation"></i></span>
                                    @elseif($st->status === 'RECHAZADO')
                                        <span class="badge bg-danger rounded-circle p-2" title="Rechazado"><i class="fas fa-times"></i></span>
                                    @else
                                        <span class="badge {{ $st->id == $approval->id ? 'bg-primary' : 'bg-secondary' }} rounded-circle p-2" title="Pendiente">
                                            <i class="fas {{ $st->id == $approval->id ? 'fa-pen' : 'fa-clock' }}"></i>
                                        </span>
                                    @endif
                                </div>
                                <div class="w-100">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-semibold {{ $st->id == $approval->id ? 'text-primary' : '' }}">{{ $st->label }}</div>
                                        <small class="badge bg-light text-muted border">{{ $st->role_name }}</small>
                                    </div>
                                    <div class="small text-muted">
                                        @if($st->approver_name)
                                            <i class="fas fa-user me-1"></i>{{ $st->approver_name }}
                                        @else
                                            <span class="fst-italic text-secondary">Por asignar</span>
                                        @endif
                                    </div>
                                    @if($st->signed_at)
                                        <small class="text-success d-block"><i class="fas fa-calendar-check me-1"></i>{{ $st->signed_at->format('d/m/Y H:i') }}</small>
                                    @endif
                                    @if($st->observations)
                                        <div class="mt-1 small bg-light p-2 rounded border text-muted">
                                            "{{ $st->observations }}"
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Visor en Pantalla Completa -->
<div class="modal fade" id="modalFullDocumentViewer" tabindex="-1" aria-labelledby="modalFullDocumentViewerLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content border-0">
            <div class="modal-header bg-dark text-white py-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-file-invoice text-info"></i>
                    <h5 class="modal-title fw-bold fs-6 mb-0" id="modalFullDocumentViewerLabel">
                        Visor de Documento: 
                        {{ $document->correlativo ?? ($document ? 'ID #' . $document->id : '#' . $approval->document_id) }}
                        <span class="badge bg-secondary ms-2 small">{{ $badgeInfo[0] }}</span>
                    </h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($pdfUrl && $pdfUrl !== '#')
                        <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-external-link-alt me-1"></i> Abrir en Pestaña
                        </a>
                    @endif
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0" style="background:#525659;">
                @if($pdfUrl && $pdfUrl !== '#')
                    <iframe id="modalPdfFrame" src="{{ $pdfUrl }}" style="width: 100%; height: 100%; min-height: 85vh; border: none;" allowfullscreen></iframe>
                @else
                    <div class="bg-white p-4 h-100 overflow-auto">
                        <div class="container py-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i> Mostrando el detalle completo del documento presentado:
                            </div>
                            <div id="modalStructuredContent">
                                <!-- Se clona el contenido del tab estructurado -->
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer bg-light py-2 justify-content-between">
                <div>
                    <span class="small text-muted">
                        Paso actual: <strong>{{ $approval->label }} ({{ $approval->role_name }})</strong>
                    </span>
                </div>
                <div class="d-flex gap-2">
                    @if($canSign)
                        <button type="button" class="btn btn-sm btn-success" onclick="$('#modalFullDocumentViewer').modal('hide'); $('#btn_approve').click();">
                            <i class="fas fa-check-circle me-1"></i> Firmar y Aprobar
                        </button>
                        <button type="button" class="btn btn-sm btn-warning" onclick="$('#modalFullDocumentViewer').modal('hide'); $('#btn_open_observe').click();">
                            <i class="fas fa-exclamation-circle me-1"></i> Observar
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="$('#modalFullDocumentViewer').modal('hide'); $('#btn_open_reject').click();">
                            <i class="fas fa-times-circle me-1"></i> Rechazar
                        </button>
                    @endif
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar Visor</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Observar / Rechazar -->
<div class="modal fade" id="decisionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="decisionModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="decisionModalDesc" class="small text-muted"></p>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Motivo / Fundamentación Obligatoria <span class="text-danger">*</span></label>
                    <textarea id="decisionObservations" class="form-control" rows="4" placeholder="Especifique con claridad los motivos o correcciones solicitadas..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn_submit_decision">Confirmar Dictamen</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let currentDecisionType = null;
    const modal = new bootstrap.Modal(document.getElementById('decisionModal'));

    // Recargar PDF inline
    $('#btnReloadPdf').click(function() {
        const frame = document.getElementById('inlinePdfFrame');
        if (frame) {
            frame.src = frame.src;
        }
    });

    // Copiar contenido estructurado para el modal fullscreen si no hay PDF
    $('#modalFullDocumentViewer').on('show.bs.modal', function() {
        const modalContainer = $('#modalStructuredContent');
        if (modalContainer.length && modalContainer.is(':empty')) {
            const detailHtml = $('#pills-detail').html();
            modalContainer.html(detailHtml);
        }
    });

    // Acción: Aprobar y Firmar
    $('#btn_approve').click(function() {
        Swal.fire({
            title: '¿Confirmar Firma y Aprobación?',
            text: 'Su firma institucional y sello electrónico quedarán estampados en el documento oficial.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, firmar y aprobar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#btn_approve');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Procesando firma...');

                $.ajax({
                    url: "{{ route('approvals.approve') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        approval_id: '{{ $approval->id }}',
                        observations: $('#approve_observations').val()
                    },
                    success: function(resp) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Firmado!',
                            text: resp.message || 'El documento fue aprobado exitosamente.',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.href = resp.redirect || "{{ route('approvals.index') }}";
                        });
                    },
                    error: function(err) {
                        btn.prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i> Firmar y Aprobar Documento');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.responseJSON?.message || 'No se pudo registrar la firma.'
                        });
                    }
                });
            }
        });
    });

    // Acción: Abrir modal para Observar
    $('#btn_open_observe').click(function() {
        currentDecisionType = 'OBSERVADO';
        $('#decisionModalTitle').text('Observar Documento');
        $('#decisionModalDesc').text('El documento regresará al solicitante para que realice las subsanaciones correspondientes.');
        $('#btn_submit_decision').removeClass('btn-danger').addClass('btn-warning text-dark').text('Registrar Observación');
        $('#decisionObservations').val('');
        modal.show();
    });

    // Acción: Abrir modal para Rechazar
    $('#btn_open_reject').click(function() {
        currentDecisionType = 'RECHAZADO';
        $('#decisionModalTitle').text('Rechazar Documento');
        $('#decisionModalDesc').text('Esta acción denegará definitivamente la solicitud y cerrará el flujo de aprobación.');
        $('#btn_submit_decision').removeClass('btn-warning text-dark').addClass('btn-danger').text('Confirmar Rechazo');
        $('#decisionObservations').val('');
        modal.show();
    });

    // Confirmar Dictamen (Observar / Rechazar)
    $('#btn_submit_decision').click(function() {
        const obs = $('#decisionObservations').val().trim();
        if (obs.length < 5) {
            Swal.fire('Observación requerida', 'Por favor ingrese al menos 5 caracteres para fundamentar la decisión.', 'warning');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).text('Procesando...');

        $.ajax({
            url: "{{ route('approvals.reject') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                approval_id: '{{ $approval->id }}',
                status: currentDecisionType,
                observations: obs
            },
            success: function(resp) {
                modal.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Completado',
                    text: resp.message || 'Dictamen registrado con éxito.',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    window.location.href = resp.redirect || "{{ route('approvals.index') }}";
                });
            },
            error: function(err) {
                btn.prop('disabled', false).text('Confirmar Dictamen');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.responseJSON?.message || 'No se pudo procesar la solicitud.'
                });
            }
        });
    });
});
</script>
@endpush
