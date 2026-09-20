@extends('admin.layout')

@section('title', 'Revisión y Firma: ' . $approval->label)

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center my-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('approvals.index') }}">Bandeja de Aprobaciones</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Revisión de Documento</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="fas fa-file-signature text-primary me-2"></i>{{ $approval->label }}
            </h1>
            <p class="text-muted small mb-0">Etapa de visación correspondiente a: <span class="fw-semibold text-dark">{{ $approval->role_name ?? 'Firma Asignada' }}</span></p>
        </div>
        <div class="d-flex gap-2">
            @if($pdfUrl && $pdfUrl !== '#')
                <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-outline-danger">
                    <i class="fas fa-file-pdf me-1"></i> Ver Formato Oficial PDF
                </a>
            @endif
            <a href="{{ route('approvals.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver a la Bandeja
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Columna Izquierda: Resumen del Documento -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="fas fa-file-alt text-primary me-2"></i>Detalles del Documento Presentado
                    </h6>
                    <span class="badge bg-secondary">
                        {{ class_basename($approval->document_type) }}
                    </span>
                </div>
                <div class="card-body">
                    @php
                        $docType = class_basename($approval->document_type);
                    @endphp

                    @if($docType === 'Requisition')
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Número de Requerimiento:</span>
                                <span class="fw-bold text-danger fs-6">{{ $document->correlativo }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Fecha de Emisión:</span>
                                <span class="fw-bold">{{ $document->fecha ? $document->fecha->format('d/m/Y') : '-' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Solicitante (De):</span>
                                <span class="fw-semibold">{{ $document->de }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Destinatario (A):</span>
                                <span class="fw-semibold">{{ $document->para }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block">Área Solicitante:</span>
                                <span class="fw-bold text-primary">{{ $document->area?->name ?? 'Área Institucional' }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block">Justificación / Objeto:</span>
                                <div class="bg-light p-3 rounded border">{{ $document->justificacion }}</div>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block mb-2">Bienes o Servicios Requeridos:</span>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light small">
                                            <tr>
                                                <th style="width: 40px;" class="text-center">#</th>
                                                <th style="width: 80px;" class="text-center">Cant.</th>
                                                <th style="width: 100px;" class="text-center">U.M.</th>
                                                <th>Descripción</th>
                                                <th style="width: 110px;" class="text-end">Total (S/)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($document->items as $idx => $it)
                                                <tr>
                                                    <td class="text-center">{{ $idx + 1 }}</td>
                                                    <td class="text-center fw-semibold">{{ $it->cantidad }}</td>
                                                    <td class="text-center">{{ $it->unidad_medida }}</td>
                                                    <td>{{ $it->descripcion }}</td>
                                                    <td class="text-end">S/ {{ number_format($it->total, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light">
                                                <td colspan="4" class="text-end fw-bold">TOTAL ESTIMADO:</td>
                                                <td class="text-end fw-bold text-danger">S/ {{ number_format($document->total_estimado, 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                    @elseif($docType === 'ExpenseDeclaration')
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">N° Declaración:</span>
                                <span class="fw-bold text-danger fs-6">{{ $document->correlativo }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Fecha:</span>
                                <span class="fw-bold">{{ $document->fecha ? $document->fecha->format('d/m/Y') : '-' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Servidor / Comisionado:</span>
                                <span class="fw-bold">{{ $document->nombres_apellidos }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">DNI:</span>
                                <span class="fw-semibold">{{ $document->dni }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block">Motivo / Comisión de Servicio:</span>
                                <div class="bg-light p-3 rounded border">{{ $document->motivo_comision }}</div>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block mb-2">Detalle de Gastos Incurridos:</span>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light small">
                                            <tr>
                                                <th style="width: 40px;" class="text-center">#</th>
                                                <th style="width: 100px;">Fecha</th>
                                                <th>Concepto del Gasto</th>
                                                <th style="width: 110px;" class="text-end">Importe (S/)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($document->items as $idx => $it)
                                                <tr>
                                                    <td class="text-center">{{ $idx + 1 }}</td>
                                                    <td>{{ $it->fecha_comprobante ? $it->fecha_comprobante->format('d/m/Y') : '-' }}</td>
                                                    <td>{{ $it->concepto }}</td>
                                                    <td class="text-end">S/ {{ number_format($it->importe, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light">
                                                <td colspan="3" class="text-end fw-bold">TOTAL SUSTENTADO:</td>
                                                <td class="text-end fw-bold text-success">S/ {{ number_format($document->total_monto, 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                    @elseif($docType === 'ExitSlip')
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">N° Papeleta de Salida:</span>
                                <span class="fw-bold text-danger fs-6">{{ $document->correlativo }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Fecha:</span>
                                <span class="fw-bold">{{ $document->fecha ? $document->fecha->format('d/m/Y') : '-' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Servidor(a):</span>
                                <span class="fw-bold">{{ $document->servidor_nombres }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Cargo / Función:</span>
                                <span class="fw-semibold">{{ $document->cargo }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Motivo de Salida:</span>
                                <span class="badge bg-primary fs-6">{{ $document->motivo }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Horario Programado:</span>
                                <span class="fw-bold">{{ substr((string)$document->hora_salida, 0, 5) }} - {{ substr((string)$document->hora_retorno, 0, 5) }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block">Lugar de Destino:</span>
                                <span class="fw-semibold">{{ $document->lugar_destino }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block">Fundamentación:</span>
                                <div class="bg-light p-3 rounded border">{{ $document->fundamentacion }}</div>
                            </div>
                        </div>

                    @elseif($docType === 'VehicleExitSlip')
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">N° Papeleta Vehicular:</span>
                                <span class="fw-bold text-danger fs-6">{{ $document->correlativo }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Fecha:</span>
                                <span class="fw-bold">{{ $document->fecha ? $document->fecha->format('d/m/Y') : '-' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Conductor / Responsable:</span>
                                <span class="fw-bold">{{ $document->conductor_nombres }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Licencia de Conducir:</span>
                                <span class="fw-semibold">{{ $document->licencia_conducir ?: 'No especificada' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Vehículo / Placa:</span>
                                <span class="fw-bold">{{ $document->vehiculo_modelo }} - {{ $document->placa }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Destino:</span>
                                <span class="fw-semibold">{{ $document->destino }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block">Comisión de Servicio:</span>
                                <div class="bg-light p-3 rounded border">{{ $document->motivo_comision }}</div>
                            </div>
                            @if($document->acompanantes)
                            <div class="col-12">
                                <span class="text-muted small d-block">Comisión / Acompañantes:</span>
                                <p class="mb-0 text-muted">{{ $document->acompanantes }}</p>
                            </div>
                            @endif
                        </div>

                    @elseif($docType === 'VacationExitSlip')
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">N° Papeleta de Vacaciones:</span>
                                <span class="fw-bold text-danger fs-6">{{ $document->correlativo }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Fecha de Solicitud:</span>
                                <span class="fw-bold">{{ $document->fecha ? $document->fecha->format('d/m/Y') : '-' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Servidor(a):</span>
                                <span class="fw-bold">{{ $document->apellidos_nombres }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">DNI:</span>
                                <span class="fw-semibold">{{ $document->dni }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Cargo / Función:</span>
                                <span class="fw-semibold">{{ $document->cargo }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Régimen Laboral:</span>
                                <span class="badge bg-secondary fs-6">{{ $document->regimen_laboral }}</span>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info border-0 mb-0">
                                    <div class="fw-bold mb-1"><i class="fas fa-calendar-check me-2"></i>Periodo Vacacional Solicitado:</div>
                                    <div class="fs-6">
                                        Desde: <strong>{{ $document->periodo_desde ? $document->periodo_desde->format('d/m/Y') : '-' }}</strong> 
                                        hasta <strong>{{ $document->periodo_hasta ? $document->periodo_hasta->format('d/m/Y') : '-' }}</strong>
                                        <span class="badge bg-primary ms-2">{{ $document->dias_vacaciones }} días efectivos</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @elseif($docType === 'FuelControlSlip')
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">N° Vale de Control:</span>
                                <span class="fw-bold text-danger fs-6">{{ $document->correlativo }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Fecha y Hora:</span>
                                <span class="fw-bold">{{ $document->fecha ? $document->fecha->format('d/m/Y') : '-' }} {{ substr((string)$document->hora, 0, 5) }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Grifo / Estación:</span>
                                <span class="fw-bold">{{ $document->nombre_grifo }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Vehículo / Placa:</span>
                                <span class="fw-bold">{{ $document->vehiculo_maquina }} ({{ $document->placa ?: 'S/P' }})</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block">Actividad / Comisión:</span>
                                <div class="bg-light p-3 rounded border">{{ $document->actividad_comision }}</div>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block mb-2">Combustibles / Lubricantes:</span>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light small">
                                            <tr>
                                                <th style="width: 80px;" class="text-center">Cant.</th>
                                                <th style="width: 100px;" class="text-center">U.M.</th>
                                                <th>Descripción</th>
                                                <th style="width: 110px;" class="text-end">Total (S/)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($document->items as $it)
                                                <tr>
                                                    <td class="text-center fw-semibold">{{ $it->cantidad }}</td>
                                                    <td class="text-center">{{ $it->unidad_medida }}</td>
                                                    <td>{{ $it->descripcion }}</td>
                                                    <td class="text-end">S/ {{ number_format($it->total, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light">
                                                <td colspan="3" class="text-end fw-bold">TOTAL GENERAL:</td>
                                                <td class="text-end fw-bold text-danger">S/ {{ number_format($document->total_general, 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Panel de Acción y Timeline -->
        <div class="col-lg-5">
            <!-- Tarjeta de Acción: Firmar / Observar / Rechazar -->
            <div class="card shadow-sm border-0 mb-4 border-start border-4 {{ $canSign ? 'border-primary' : 'border-secondary' }}">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="fas fa-pen-fancy text-primary me-2"></i>Acción de Firma y Dictamen
                    </h6>
                </div>
                <div class="card-body">
                    @if($canSign)
                        <div class="alert alert-primary bg-primary text-white border-0 small mb-3">
                            <i class="fas fa-info-circle me-1"></i> Usted está habilitado para firmar y visar este documento en calidad de <strong>{{ $approval->role_name ?? 'Autoridad Correspondiente' }}</strong>.
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
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-warning w-50" id="btn_open_observe">
                                        <i class="fas fa-exclamation-circle me-1"></i> Observar
                                    </button>
                                    <button type="button" class="btn btn-danger w-50" id="btn_open_reject">
                                        <i class="fas fa-times-circle me-1"></i> Rechazar
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        @if($approval->status === 'APROBADO')
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
                                        <span class="badge bg-success rounded-circle p-2"><i class="fas fa-check"></i></span>
                                    @elseif($st->status === 'OBSERVADO')
                                        <span class="badge bg-warning text-dark rounded-circle p-2"><i class="fas fa-exclamation"></i></span>
                                    @elseif($st->status === 'RECHAZADO')
                                        <span class="badge bg-danger rounded-circle p-2"><i class="fas fa-times"></i></span>
                                    @else
                                        <span class="badge {{ $st->id == $approval->id ? 'bg-primary' : 'bg-secondary' }} rounded-circle p-2">
                                            <i class="fas {{ $st->id == $approval->id ? 'fa-pen' : 'fa-clock' }}"></i>
                                        </span>
                                    @endif
                                </div>
                                <div class="w-100">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-semibold {{ $st->id == $approval->id ? 'text-primary' : '' }}">{{ $st->label }}</div>
                                        <small class="text-muted">{{ $st->role_name }}</small>
                                    </div>
                                    <div class="small text-muted">
                                        @if($st->approver_name)
                                            <i class="fas fa-user me-1"></i>{{ $st->approver_name }}
                                        @else
                                            <span class="fst-italic">Por asignar</span>
                                        @endif
                                    </div>
                                    @if($st->signed_at)
                                        <small class="text-success d-block"><i class="fas fa-calendar-check me-1"></i>{{ $st->signed_at->format('d/m/Y H:i') }}</small>
                                    @endif
                                    @if($st->observations)
                                        <div class="mt-1 small bg-light p-1 rounded border text-muted">
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

    $('#btn_open_observe').click(function() {
        currentDecisionType = 'OBSERVADO';
        $('#decisionModalTitle').text('Observar Documento');
        $('#decisionModalDesc').text('El documento regresará al solicitante para que realice las subsanaciones correspondientes.');
        $('#btn_submit_decision').removeClass('btn-danger').addClass('btn-warning text-dark').text('Registrar Observación');
        $('#decisionObservations').val('');
        modal.show();
    });

    $('#btn_open_reject').click(function() {
        currentDecisionType = 'RECHAZADO';
        $('#decisionModalTitle').text('Rechazar Documento');
        $('#decisionModalDesc').text('Esta acción denegará definitivamente la solicitud y cerrará el flujo de aprobación.');
        $('#btn_submit_decision').removeClass('btn-warning text-dark').addClass('btn-danger').text('Confirmar Rechazo');
        $('#decisionObservations').val('');
        modal.show();
    });

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
