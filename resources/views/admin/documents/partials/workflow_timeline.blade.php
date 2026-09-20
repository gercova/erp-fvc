<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="m-0 fw-bold text-primary">
            <i class="fas fa-signature me-1"></i> Flujo de Firmas y Aprobaciones
        </h6>
        <div>
            {!! $document->status_badge !!}
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            @foreach ($document->approvals as $approval)
                @php
                    $isApproved = $approval->status === 'APROBADO';
                    $isPending = $approval->status === 'PENDIENTE';
                    $isObserved = $approval->status === 'OBSERVADO';
                    $isRejected = $approval->status === 'RECHAZADO';

                    $borderColor = $isApproved ? 'border-success' : ($isPending ? 'border-warning' : ($isRejected ? 'border-danger' : 'border-secondary'));
                    $badgeBg = $isApproved ? 'bg-success text-white' : ($isPending ? 'bg-warning text-dark' : ($isRejected ? 'bg-danger text-white' : 'bg-light text-muted'));
                    $icon = $isApproved ? 'fa-check' : ($isPending ? 'fa-clock' : ($isRejected ? 'fa-times' : 'fa-hourglass-start'));
                @endphp
                <div class="col-md-6 col-lg">
                    <div class="border rounded p-3 h-100 bg-white {{ $borderColor }}" style="border-width: 2px !important;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge {{ $badgeBg }} rounded-pill px-2 py-1">
                                <i class="fas {{ $icon }} me-1"></i> Paso {{ $approval->step_order }}
                            </span>
                            <span class="small fw-semibold text-uppercase text-muted">
                                {{ $approval->status }}
                            </span>
                        </div>

                        <div class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">
                            {{ $approval->label }}
                        </div>

                        <div class="small text-muted mb-2">
                            @if ($approval->approver_name)
                                <div class="text-dark fw-semibold">{{ $approval->approver_name }}</div>
                                <div>{{ $approval->approver_cargo ?: $approval->role_name }}</div>
                            @else
                                <span class="fst-italic text-muted">Por asignar / Rol: {{ $approval->role_name }}</span>
                            @endif
                        </div>

                        @if ($approval->signed_at)
                            <div class="small text-success mt-2 pt-2 border-top">
                                <div><i class="far fa-calendar-check me-1"></i> {{ $approval->signed_at->format('d/m/Y H:i') }}</div>
                                @if ($approval->signature_token)
                                    <div class="font-monospace text-muted mt-1" style="font-size: 0.75rem;">Token: {{ $approval->signature_token }}</div>
                                @endif
                            </div>
                        @endif

                        @if ($approval->observations)
                            <div class="alert alert-warning py-1 px-2 mt-2 mb-0 small">
                                <strong>Obs:</strong> {{ $approval->observations }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
