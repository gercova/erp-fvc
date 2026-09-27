@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 mb-0 text-gray-800 fw-bold">{{ $activity->name }}</h1>
                <span class="badge bg-primary font-monospace">{{ $activity->code }}</span>
                {!! $activity->status_badge !!}
            </div>
            <p class="text-muted small mb-0">Área: {{ $activity->area?->name ?? 'No asignada' }} &bull; Centro de Costos: {{ $activity->cost_center_code ?? 'S/C' }}</p>
        </div>
        <div class="d-flex gap-2">
            @if(!$activity->approvals()->exists())
                <button type="button" class="btn btn-outline-primary btn-sm btn-submit-approval" data-id="{{ $activity->id }}">
                    <i class="fas fa-file-signature me-1"></i> Enviar a Aprobación Institucional
                </button>
            @endif
            <a href="{{ route('productive_activities.edit', $activity->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-edit me-1"></i> Editar
            </a>
            <a href="{{ route('productive_activities.index') }}" class="btn btn-outline-dark btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- Progress & Approval Status Banner -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-6 border-end">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold text-uppercase text-muted">Avance Físico-Financiero</span>
                        <span class="badge bg-primary">{{ (float) $activity->execution_progress_percent }}%</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ (float) $activity->execution_progress_percent }}%"></div>
                    </div>
                    <div class="small text-muted mt-1">
                        <i class="fas fa-comment-dots me-1"></i> {{ $activity->monitoring_observations ?: 'Sin observaciones recientes de monitoreo.' }}
                    </div>
                </div>

                <div class="col-md-6 ps-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold text-uppercase text-muted">Estado del Flujo de Aprobación</span>
                        {!! $activity->status_badge !!}
                    </div>
                    @if($activity->approvals()->exists())
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($activity->approvals as $app)
                                <div class="p-1 px-2 rounded border bg-white small d-flex align-items-center gap-1">
                                    @if($app->status === 'APROBADO')
                                        <i class="fas fa-check-circle text-success"></i>
                                    @elseif($app->status === 'PENDIENTE')
                                        <i class="fas fa-clock text-warning"></i>
                                    @else
                                        <i class="fas fa-times-circle text-danger"></i>
                                    @endif
                                    <span class="fw-bold">{{ $app->label }}:</span>
                                    <span class="text-muted">{{ $app->approver_name ?: 'Pendiente' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i> Esta actividad aún no ha sido sometida al flujo de firmas oficial (`Area::approvalChain`).
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Details & Sub-units -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-info-circle me-1"></i> Información General</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th style="width: 40%;" class="text-muted">Tipo de Actividad:</th>
                            <td class="fw-bold">{{ $activity->type }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Responsable Directo:</th>
                            <td>{{ $activity->head?->nombres ?? 'No asignado' }} ({{ $activity->head?->email ?? '-' }})</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Área Institucional:</th>
                            <td>{{ $activity->area?->name ?? 'No asignada' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Fuente de Fondos Default:</th>
                            <td>{{ $activity->defaultFundSource?->name ?? 'Sin asignar' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Descripción / Objetivos:</th>
                            <td>{{ $activity->description ?: 'Sin descripción registrada.' }}</td>
                        </tr>
                    </table>

                    <hr>
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-sitemap me-1"></i> Unidades o Líneas de Explotación (Sub-actividades)</h6>
                    @if($activity->productiveUnits->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($activity->productiveUnits as $unit)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <div>
                                        <span class="fw-bold font-monospace text-primary me-2">{{ $unit->code }}</span>
                                        <span>{{ $unit->name }}</span>
                                        <div class="small text-muted">Encargado: {{ $unit->inChargeUser?->nombres ?? 'Sin asignar' }}</div>
                                    </div>
                                    <span class="badge bg-light text-dark border">{{ $unit->status }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-light border small text-muted mb-0">
                            No se han registrado unidades o sub-actividades específicas (ej. Vacunos, Porcinos). Las operaciones se imputan a nivel global de la actividad.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Physical-Financial Tracking Log -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-history me-1"></i> Historial de Seguimiento Físico-Financiero</h6>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newTrackingModal">
                        <i class="fas fa-plus me-1"></i> Registrar Hito
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover table-sm align-middle mb-0" id="trackingLogsTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 100px;">Fecha</th>
                                    <th style="width: 80px;">Avance</th>
                                    <th style="width: 90px;">Estado</th>
                                    <th>Comentario / Observación</th>
                                    <th style="width: 120px;">Registrado Por</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activity->trackingLogs as $log)
                                    <tr>
                                        <td class="font-monospace small">{{ $log->log_date->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ (float) $log->progress_percent }}%</span>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">{{ $log->status }}</span></td>
                                        <td class="small">{{ $log->comment }}</td>
                                        <td class="small text-muted">{{ $log->user?->nombres ?? 'Sistema' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted small">
                                            No hay registros de seguimiento en la bitácora física-financiera.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Registrar Hito de Seguimiento -->
<div class="modal fade" id="newTrackingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title h6 mb-0"><i class="fas fa-clipboard-check me-2"></i> Registrar Hito de Monitoreo Físico-Financiero</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="trackingLogForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Fecha del Hito <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="log_date" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">% Avance <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control" name="progress_percent" value="{{ (float) $activity->execution_progress_percent }}" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="col-6">
                            <label class="form-label fw-bold">Estado <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="ACTIVA" {{ $activity->status === 'ACTIVA' ? 'selected' : '' }}>ACTIVA</option>
                                <option value="EN_MANTENIMIENTO" {{ $activity->status === 'EN_MANTENIMIENTO' ? 'selected' : '' }}>EN MANTENIMIENTO</option>
                                <option value="CERRADA" {{ $activity->status === 'CERRADA' ? 'selected' : '' }}>CERRADA</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Comentario / Observación de Monitoreo <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="comment" rows="3" placeholder="Detalle los avances físicos, contingencias climáticas o justificación del hito..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3" id="btnSaveTracking">
                        <i class="fas fa-save me-1"></i> Guardar Hito
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Guardar Hito de Monitoreo
    $('#trackingLogForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSaveTracking');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

        $.ajax({
            url: '{{ route("productive_activities.tracking_logs.store", $activity->id) }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                Swal.fire('¡Registrado!', resp.message, 'success').then(() => {
                    location.reload();
                });
            },
            error: function(err) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Hito');
                let msg = err.responseJSON ? err.responseJSON.message : 'Error al registrar el hito.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // Enviar a aprobación
    $('.btn-submit-approval').on('click', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: '¿Enviar a Aprobación Institucional?',
            text: 'Se iniciará la cadena de firmas con las autoridades correspondientes.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0061f2',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, enviar documento',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("productive-activities") }}/' + id + '/submit-approval',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(resp) {
                        Swal.fire('¡Enviado!', resp.message, 'success').then(() => {
                            location.reload();
                        });
                    },
                    error: function(err) {
                        let msg = err.responseJSON ? err.responseJSON.message : 'Error al enviar a aprobación.';
                        Swal.fire('Atención', msg, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
