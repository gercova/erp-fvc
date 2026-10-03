@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="fas fa-file-contract text-primary me-2"></i>Nueva Contratación de Servicio Tecnológico
            </h1>
            <p class="text-muted small mb-0">Contratación bajo convenio marco/específico o servicio técnico directo a clientes externos.</p>
        </div>
        <a href="{{ route('services.engagements.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver al Catálogo
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm mb-4">
            <h6 class="fw-bold mb-2"><i class="fas fa-exclamation-circle me-1"></i> Corrija los siguientes errores:</h6>
            <ul class="mb-0 small">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('services.engagements.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            <!-- Left Column: Core Data -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-info-circle me-1"></i> 1. Especificación del Servicio y Partes</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Servicio Tecnológico del Catálogo <span class="text-danger">*</span></label>
                                <select name="technological_service_id" id="technological_service_id" class="form-select" required>
                                    <option value="">-- Seleccionar Servicio --</option>
                                    @foreach($services as $svc)
                                        <option value="{{ $svc->id }}" 
                                            data-price="{{ $svc->unit_price }}" 
                                            data-activity="{{ $svc->productive_activity_id }}"
                                            data-hours="{{ $svc->estimated_hours }}"
                                            data-modality="{{ $svc->delivery_modality }}"
                                            {{ old('technological_service_id') == $svc->id ? 'selected' : '' }}>
                                            {{ $svc->code }} - {{ $svc->name }} (Tarifa: S/ {{ number_format($svc->unit_price, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Cliente / Contraparte Contratante <span class="text-danger">*</span></label>
                                <select name="client_id" class="form-select" required>
                                    <option value="">-- Seleccionar Cliente --</option>
                                    @foreach($clients as $cli)
                                        <option value="{{ $cli->id }}" {{ old('client_id') == $cli->id ? 'selected' : '' }}>
                                            {{ $cli->nombres }} (RUC/Doc: {{ $cli->nro_documento }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Convenio Marco / Específico Asignado (Opcional)</label>
                                <select name="agreement_id" class="form-select">
                                    <option value="">-- Sin Convenio (Servicio Directo / Ad-Hoc) --</option>
                                    @foreach($agreements as $ag)
                                        <option value="{{ $ag->id }}" {{ old('agreement_id') == $ag->id ? 'selected' : '' }}>
                                            {{ $ag->code }} - {{ Str::limit($ag->name, 45) }} (Disp: S/ {{ number_format($ag->total_amount, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small">Si se asocia a un convenio, se computa dentro de su techo presupuestal.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Especialista / Responsable Técnico <span class="text-danger">*</span></label>
                                <select name="responsible_user_id" class="form-select" required>
                                    <option value="">-- Seleccionar Especialista --</option>
                                    @foreach($specialists as $sp)
                                        <option value="{{ $sp->id }}" {{ old('responsible_user_id') == $sp->id ? 'selected' : '' }}>
                                            {{ $sp->nombres }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Modalidad de Ejecución <span class="text-danger">*</span></label>
                                <select name="delivery_modality" id="delivery_modality" class="form-select" required>
                                    <option value="IN_PERSON" {{ old('delivery_modality') == 'IN_PERSON' ? 'selected' : '' }}>Presencial (Aulas / Laboratorio)</option>
                                    <option value="VIRTUAL" {{ old('delivery_modality') == 'VIRTUAL' ? 'selected' : '' }}>Virtual (Sincrónica / Asincrónica)</option>
                                    <option value="HYBRID" {{ old('delivery_modality') == 'HYBRID' ? 'selected' : '' }}>Híbrida</option>
                                    <option value="FIELD" {{ old('delivery_modality') == 'FIELD' ? 'selected' : '' }}>En Campo / Fundo / Parcela</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Centro de Costo / Actividad APE</label>
                                <select name="productive_activity_id" id="productive_activity_id" class="form-select">
                                    <option value="">-- Automático según servicio --</option>
                                    @foreach($activities as $act)
                                        <option value="{{ $act->id }}" {{ old('productive_activity_id') == $act->id ? 'selected' : '' }}>
                                            {{ $act->code }} - {{ $act->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Objeto y Descripción del Servicio Contratado <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="3" required placeholder="Detalle los alcances, módulos o ensayos específicos contratados...">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bag of Hours & Dates -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-stopwatch me-1"></i> 2. Bolsa de Horas, Asistencia y Cronograma</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Bolsa de Horas Contratada (hrs)</label>
                                <input type="number" step="0.5" min="0" name="contracted_hours" id="contracted_hours" class="form-control" value="{{ old('contracted_hours', '0.00') }}">
                                <div class="form-text small">Total de horas asignadas para deducir.</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Umbral de Asistencia para Certificado (%)</label>
                                <input type="number" step="1" min="0" max="100" name="min_attendance_percent" class="form-control" value="{{ old('min_attendance_percent', '80') }}">
                                <div class="form-text small">% mínimo para emitir certificado.</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Alerta Saldo Bajo (hrs)</label>
                                <input type="number" step="0.5" min="0" name="low_balance_threshold_hours" class="form-control" value="{{ old('low_balance_threshold_hours', '5.00') }}">
                                <div class="form-text small">Dispara alerta cuando resten estas horas.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Fecha de Inicio <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', date('Y-m-d')) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Fecha Comprometida de Entrega / Fin <span class="text-danger">*</span></label>
                                <input type="date" name="expected_delivery_date" class="form-control" value="{{ old('expected_delivery_date', date('Y-m-d', strtotime('+30 days'))) }}" required>
                            </div>

                            <div class="col-12 mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="allow_pool_overage" id="allow_pool_overage" value="1" {{ old('allow_pool_overage') ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-bold" for="allow_pool_overage">
                                        Permitir sobregiro explícito de la bolsa de horas contratadas
                                    </label>
                                    <div class="form-text small">Si está desactivado, el sistema bloqueará estrictamente cualquier registro que exceda las horas disponibles.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Economics & Submit -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4 bg-light">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="m-0 fw-bold text-dark"><i class="fas fa-calculator me-1"></i> 3. Estructura Económica</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Moneda</label>
                            <select name="currency" class="form-select">
                                <option value="PEN" {{ old('currency') == 'PEN' ? 'selected' : '' }}>PEN - Soles (S/)</option>
                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD - Dólares ($)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Cantidad / Unidades</label>
                            <input type="number" step="0.01" min="0.01" name="quantity" id="quantity" class="form-control" value="{{ old('quantity', '1.00') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Precio Unitario / Tarifa <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" min="0" name="unit_price" id="unit_price" class="form-control" value="{{ old('unit_price', '0.00') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tarifa por Hora (Opcional)</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" min="0" name="hourly_rate" class="form-control" value="{{ old('hourly_rate') }}">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Monto Total Pactado</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" min="0" name="total_amount" id="total_amount" class="form-control fw-bold fs-5 text-primary" value="{{ old('total_amount', '0.00') }}">
                            </div>
                            <div class="form-text small">Calculado automáticamente (Cantidad &times; Precio Unitario).</div>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-bold">
                                <i class="fas fa-check-circle me-1"></i> Guardar y Generar Orden
                            </button>
                            <a href="{{ route('services.engagements.index') }}" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    function calcTotal() {
        const qty   = parseFloat($('#quantity').val()) || 0;
        const price = parseFloat($('#unit_price').val()) || 0;
        $('#total_amount').val((qty * price).toFixed(2));
    }

    $('#quantity, #unit_price').on('input change', calcTotal);

    $('#technological_service_id').on('change', function() {
        const opt = $(this).find(':selected');
        const price = opt.data('price');
        const activity = opt.data('activity');
        const hours = opt.data('hours');
        const modality = opt.data('modality');

        if (price !== undefined) {
            $('#unit_price').val(price);
            calcTotal();
        }
        if (activity) {
            $('#productive_activity_id').val(activity);
        }
        if (hours) {
            $('#contracted_hours').val(hours);
        }
        if (modality) {
            $('#delivery_modality').val(modality);
        }
    });
});
</script>
@endsection
