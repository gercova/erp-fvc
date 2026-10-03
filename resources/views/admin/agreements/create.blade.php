@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Nuevo Convenio Institucional</h1>
            <p class="text-muted small mb-0">Registre un convenio marco o específico, estableciendo contraparte, vigencia y compromisos.</p>
        </div>
        <div>
            <a href="{{ route('agreements.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver al Catálogo
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i> Errores de validación encontrados:</div>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('agreements.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            <!-- Columna Izquierda: Datos Principales -->
            <div class="col-lg-8">
                <!-- Card 1: Tipificación y Objeto -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-file-contract me-1"></i> Identificación del Convenio</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Nombre o Título del Convenio <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Ej. Convenio Marco de Cooperación Interinstitucional..." required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Código Institucional</label>
                                <input type="text" name="code" class="form-control font-monospace @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="Autogenerado (CONV-YYYY-XXXX)">
                                <small class="text-muted">Deje en blanco para correlativo automático.</small>
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tipo de Convenio <span class="text-danger">*</span></label>
                                <select name="type" id="agreementType" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="FRAMEWORK" {{ old('type') === 'FRAMEWORK' ? 'selected' : '' }}>Convenio Marco</option>
                                    <option value="SPECIFIC" {{ old('type') === 'SPECIFIC' ? 'selected' : '' }}>Convenio Específico</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Alcance <span class="text-danger">*</span></label>
                                <select name="scope" class="form-select @error('scope') is-invalid @enderror" required>
                                    <option value="NATIONAL" {{ old('scope', 'NATIONAL') === 'NATIONAL' ? 'selected' : '' }}>Nacional</option>
                                    <option value="REGIONAL" {{ old('scope') === 'REGIONAL' ? 'selected' : '' }}>Regional</option>
                                    <option value="LOCAL" {{ old('scope') === 'LOCAL' ? 'selected' : '' }}>Local</option>
                                    <option value="INTERNATIONAL" {{ old('scope') === 'INTERNATIONAL' ? 'selected' : '' }}>Internacional</option>
                                </select>
                                @error('scope')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4" id="parentAgreementCol" style="display: {{ old('type') === 'SPECIFIC' ? 'block' : 'none' }};">
                                <label class="form-label small fw-bold">Convenio Marco Padre</label>
                                <select name="parent_agreement_id" class="form-select @error('parent_agreement_id') is-invalid @enderror">
                                    <option value="">-- Sin Convenio Marco --</option>
                                    @foreach($frameworkAgreements as $fw)
                                        <option value="{{ $fw->id }}" {{ old('parent_agreement_id') == $fw->id ? 'selected' : '' }}>{{ $fw->code }} - {{ $fw->name }}</option>
                                    @endforeach
                                </select>
                                @error('parent_agreement_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Objeto / Propósito Sustantivo <span class="text-danger">*</span></label>
                                <textarea name="objective" rows="3" class="form-control @error('objective') is-invalid @enderror" placeholder="Describa la finalidad, objetivos conjuntos y materias de cooperación..." required>{{ old('objective') }}</textarea>
                                @error('objective')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Cláusulas y Condiciones Clave -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-list-check me-1"></i> Cláusulas y Condiciones Operativas</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">Cláusulas Clave y Obligaciones Principales</label>
                                <textarea name="key_clauses" rows="4" class="form-control @error('key_clauses') is-invalid @enderror" placeholder="Resumen de cláusulas medulares, responsabilidades mutuas y entregables acordados...">{{ old('key_clauses') }}</textarea>
                                @error('key_clauses')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Condiciones de Resolución o Terminación</label>
                                <textarea name="termination_conditions" rows="2" class="form-control @error('termination_conditions') is-invalid @enderror" placeholder="Causales de resolución de mutuo acuerdo, incumplimiento fortuito o penalidades...">{{ old('termination_conditions') }}</textarea>
                                @error('termination_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="has_economic_obligation" value="1" id="chkEconomic" {{ old('has_economic_obligation') ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-bold" for="chkEconomic">
                                        Implica Obligación Económica / Contraprestación
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="requires_mutual_reports" value="1" id="chkReports" {{ old('requires_mutual_reports') ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-bold" for="chkReports">
                                        Requiere Informes Periódicos de Seguimiento Mutuo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Expediente Digital Inicial -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-paperclip me-1"></i> Expediente Digital (Documento Escaneado)</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">Archivo del Convenio Firmado o Proyecto (PDF/Word)</label>
                                <input type="file" name="signed_document" class="form-control @error('signed_document') is-invalid @enderror" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <small class="text-muted">Se almacenará en el repositorio privado institucional (Máx 20MB).</small>
                                @error('signed_document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Notas u Observaciones Generales</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="Anotaciones para el expediente institucional...">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Partes, Fechas y Montos -->
            <div class="col-lg-4">
                <!-- Card Contraparte -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-building me-1"></i> Contraparte y Gobernanza</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Contraparte (Entidad / Empresa) <span class="text-danger">*</span></label>
                            <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
                                <option value="">-- Seleccionar Contraparte --</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->nombres }} (RUC/Doc: {{ $c->nro_documento }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Debe contar con Tax ID / RUC registrado.</small>
                            @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Departamento Responsable <span class="text-danger">*</span></label>
                            <select name="area_id" class="form-select @error('area_id') is-invalid @enderror" required>
                                <option value="">-- Seleccionar Área --</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>
                                        {{ $area->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('area_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Coordinador Técnico / Responsable</label>
                            <select name="coordinator_user_id" class="form-select @error('coordinator_user_id') is-invalid @enderror">
                                <option value="">-- Usuario Actual por Defecto --</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ old('coordinator_user_id') == $u->id ? 'selected' : '' }}>
                                        {{ $u->nombres }} ({{ $u->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('coordinator_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label small fw-bold">Actividad Productiva Asociada (Opcional)</label>
                            <select name="productive_activity_id" class="form-select @error('productive_activity_id') is-invalid @enderror">
                                <option value="">-- Ninguna (General / Institucional) --</option>
                                @foreach($productiveActivities as $pa)
                                    <option value="{{ $pa->id }}" {{ old('productive_activity_id') == $pa->id ? 'selected' : '' }}>
                                        {{ $pa->code }} - {{ $pa->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Vincular para costeo y centro de costo RDR.</small>
                            @error('productive_activity_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <!-- Card Vigencia y Presupuesto -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-calendar-check me-1"></i> Vigencia y Presupuesto</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Fecha de Inicio de Vigencia <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', date('Y-m-d')) }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Fecha de Término de Vigencia <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', date('Y-m-d', strtotime('+1 year'))) }}" required>
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Fecha de Suscripción / Firma</label>
                            <input type="date" name="signing_date" class="form-control @error('signing_date') is-invalid @enderror" value="{{ old('signing_date') }}">
                            @error('signing_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label small fw-bold">Moneda <span class="text-danger">*</span></label>
                                <select name="currency" class="form-select @error('currency') is-invalid @enderror" required>
                                    <option value="PEN" {{ old('currency', 'PEN') === 'PEN' ? 'selected' : '' }}>PEN (S/)</option>
                                    <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD ($)</option>
                                    <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                </select>
                            </div>
                            <div class="col-8">
                                <label class="form-label small fw-bold">Monto Comprometido <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="total_amount" class="form-control text-end fw-bold @error('total_amount') is-invalid @enderror" value="{{ old('total_amount', '0.00') }}" required>
                                @error('total_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary fw-bold py-2 shadow-sm">
                                <i class="fas fa-save me-1"></i> Guardar Convenio (Borrador)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('agreementType');
    const parentCol = document.getElementById('parentAgreementCol');

    typeSelect.addEventListener('change', function() {
        if (this.value === 'SPECIFIC') {
            parentCol.style.display = 'block';
        } else {
            parentCol.style.display = 'none';
        }
    });
});
</script>
@endpush
