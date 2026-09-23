@extends('admin.layout')
@section('title', 'Editar Préstamo ' . $loan->loan_code)

@section('content')
    <div class="container-fluid px-4 py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Bienes Patrimoniales</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('asset_loans.index') }}">Préstamos</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Editar {{ $loan->loan_code }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-900 fw-bold">
                    <i class="fas fa-edit me-2"></i>Editar Préstamo: <span class="font-monospace text-primary">{{ $loan->loan_code }}</span>
                </h1>
                <p class="text-muted small mb-0">Actualice la información del solicitante, plazos o registre la conformidad de devolución</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('asset_loans.pdf', $loan->id) }}" target="_blank" class="btn btn-outline-danger">
                    <i class="fas fa-file-pdf me-1"></i> Papeleta (PDF)
                </a>
                <a href="{{ route('asset_loans.index', ['area_id' => $loan->area_id]) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver a Préstamos
                </a>
            </div>
        </div>

        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fs-4 me-3"></i>
                    <div>
                        <strong class="d-block mb-1">Por favor revise los siguientes campos:</strong>
                        <ul class="mb-0 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('asset_loans.update', $loan->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-4">
                <!-- Columna Izquierda: Datos del Solicitante y Bien -->
                <div class="col-lg-7">
                    <!-- Tarjeta: Solicitante -->
                    <div class="card shadow-sm border-0 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-user me-1"></i> Datos del Solicitante / Beneficiario
                            </h6>
                            <div>{!! $loan->borrower_type_badge !!}</div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Tipo de Solicitante <span class="text-danger">*</span></label>
                                    <select name="borrower_type" id="borrower_type" class="form-select" required>
                                        <option value="STUDENT" {{ old('borrower_type', $loan->borrower_type) === 'STUDENT' ? 'selected' : '' }}>Estudiante / Alumno</option>
                                        <option value="FACULTY" {{ old('borrower_type', $loan->borrower_type) === 'FACULTY' ? 'selected' : '' }}>Docente / Profesor</option>
                                        <option value="ADMINISTRATIVE" {{ old('borrower_type', $loan->borrower_type) === 'ADMINISTRATIVE' ? 'selected' : '' }}>Personal Administrativo</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-dark">Nombre Completo <span class="text-danger">*</span></label>
                                    <input type="text" name="borrower_name" class="form-control" required
                                        value="{{ old('borrower_name', $loan->borrower_name) }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">N° Documento (DNI/CE) <span class="text-danger">*</span></label>
                                    <input type="text" name="borrower_document" class="form-control font-monospace" required
                                        value="{{ old('borrower_document', $loan->borrower_document) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Código / Matrícula</label>
                                    <input type="text" name="borrower_code" class="form-control font-monospace"
                                        value="{{ old('borrower_code', $loan->borrower_code) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Programa / Área</label>
                                    <input type="text" name="borrower_career_or_area" class="form-control"
                                        value="{{ old('borrower_career_or_area', $loan->borrower_career_or_area) }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Teléfono / Celular</label>
                                    <input type="text" name="borrower_phone" class="form-control"
                                        value="{{ old('borrower_phone', $loan->borrower_phone) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Correo Institucional / Personal</label>
                                    <input type="email" name="borrower_email" class="form-control"
                                        value="{{ old('borrower_email', $loan->borrower_email) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta: Bien Patrimonial y Destino -->
                    <div class="card shadow-sm border-0 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-box me-1"></i> Bien Patrimonial y Destino
                            </h6>
                            <span class="badge bg-light text-muted border">Área: {{ $loan->area?->name }}</span>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="area_id" value="{{ $loan->area_id }}">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Bien Patrimonial Asignado <span class="text-danger">*</span></label>
                                    <select name="asset_id" id="asset_id" class="form-select" required>
                                        @foreach ($availableAssets as $asset)
                                            <option value="{{ $asset->id }}" {{ old('asset_id', $loan->asset_id) == $asset->id ? 'selected' : '' }}>
                                                [{{ $asset->codigo ?: 'ORD #' . $asset->orden }}] {{ $asset->descripcion }}
                                                ({{ $asset->marca !== 'SIN MARCA' ? $asset->marca : 'S/M' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Ambiente / Ubicación de Uso <span class="text-danger">*</span></label>
                                    <input type="text" name="destination" class="form-control" required
                                        value="{{ old('destination', $loan->destination) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Finalidad / Motivo</label>
                                    <input type="text" name="purpose" class="form-control"
                                        value="{{ old('purpose', $loan->purpose) }}">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Observaciones de Entrega Inicial</label>
                                    <textarea name="observations" class="form-control" rows="2">{{ old('observations', $loan->observations) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Estado, Plazos y Devolución -->
                <div class="col-lg-5">
                    <!-- Tarjeta: Estado y Fechas -->
                    <div class="card shadow-sm border-0 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-calendar-alt me-1"></i> Estado y Plazos de Préstamo
                            </h6>
                            <div>{!! $loan->status_badge !!}</div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Estado del Préstamo <span class="text-danger">*</span></label>
                                <select name="status" id="loan_status" class="form-select fw-semibold" required>
                                    <option value="PRESTADO" {{ old('status', $loan->status) === 'PRESTADO' ? 'selected' : '' }}>En Préstamo (Activo)</option>
                                    <option value="DEVUELTO" {{ old('status', $loan->status) === 'DEVUELTO' ? 'selected' : '' }}>Devuelto (Conforme)</option>
                                    <option value="DEVUELTO_OBSERVADO" {{ old('status', $loan->status) === 'DEVUELTO_OBSERVADO' ? 'selected' : '' }}>Devuelto con Observaciones</option>
                                    <option value="EXTRAVIADO" {{ old('status', $loan->status) === 'EXTRAVIADO' ? 'selected' : '' }}>Extraviado / Dañado</option>
                                </select>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Fecha de Entrega <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="loan_date" class="form-control" required
                                        value="{{ old('loan_date', $loan->loan_date?->format('Y-m-d\TH:i')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Retorno Estimado <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="expected_return_date" class="form-control" required
                                        value="{{ old('expected_return_date', $loan->expected_return_date?->format('Y-m-d\TH:i')) }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Condición Física Inicial (Al Entregar)</label>
                                <select name="initial_condition" class="form-select">
                                    <option value="B" {{ old('initial_condition', $loan->initial_condition) === 'B' ? 'selected' : '' }}>Bueno (B) - Conforme</option>
                                    <option value="R" {{ old('initial_condition', $loan->initial_condition) === 'R' ? 'selected' : '' }}>Regular (R) - Con desgaste</option>
                                    <option value="M" {{ old('initial_condition', $loan->initial_condition) === 'M' ? 'selected' : '' }}>Malo (M) - Averiado</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta: Datos de Devolución -->
                    <div class="card shadow-sm border-0 mb-4 bg-white border-start border-4 border-success">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="m-0 fw-bold text-success">
                                <i class="fas fa-check-circle me-1"></i> Recepción y Devolución del Bien
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Fecha y Hora Real de Devolución</label>
                                <input type="datetime-local" name="actual_return_date" id="actual_return_date" class="form-control"
                                    value="{{ old('actual_return_date', $loan->actual_return_date?->format('Y-m-d\TH:i')) }}">
                                <small class="text-muted">Si se deja vacío al marcar como devuelto, tomará la fecha y hora actual.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Condición Física al Devolver</label>
                                <select name="return_condition" class="form-select">
                                    <option value="">-- Seleccionar condición recibida --</option>
                                    <option value="B" {{ old('return_condition', $loan->return_condition) === 'B' ? 'selected' : '' }}>Bueno (B) - Sin observaciones</option>
                                    <option value="R" {{ old('return_condition', $loan->return_condition) === 'R' ? 'selected' : '' }}>Regular (R) - Desgaste menor</option>
                                    <option value="M" {{ old('return_condition', $loan->return_condition) === 'M' ? 'selected' : '' }}>Malo (M) - Dañado o averiado</option>
                                    <option value="BAJA" {{ old('return_condition', $loan->return_condition) === 'BAJA' ? 'selected' : '' }}>Baja - Inoperativo</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Observaciones de Devolución</label>
                                <textarea name="return_observations" class="form-control" rows="3"
                                    placeholder="Indique si el equipo se devolvió con todos sus accesorios, cables, cargadores...">{{ old('return_observations', $loan->return_observations) }}</textarea>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="update_asset_condition" id="update_asset_condition" value="1" checked>
                                <label class="form-check-label small text-dark" for="update_asset_condition">
                                    Actualizar condición física del activo en el inventario general al guardar
                                </label>
                            </div>

                            <div class="p-2 bg-light rounded small text-muted">
                                <div><i class="fas fa-info-circle me-1"></i> Autorizado originalmente por: <strong>{{ $loan->user?->nombres ?? 'Usuario' }}</strong></div>
                                @if ($loan->receivedByUser)
                                    <div class="mt-1"><i class="fas fa-check-double me-1 text-success"></i> Recibido por: <strong>{{ $loan->receivedByUser->nombres }}</strong></div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="card shadow-sm border-0 bg-white">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary py-2 fw-semibold">
                                    <i class="fas fa-save me-1"></i> Guardar Cambios del Préstamo
                                </button>
                                <a href="{{ route('asset_loans.index', ['area_id' => $loan->area_id]) }}"
                                    class="btn btn-outline-secondary py-2">
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
            // Si el estado cambia a devuelto y no hay fecha de retorno, asignar ahora
            $('#loan_status').change(function() {
                const status = $(this).val();
                if (['DEVUELTO', 'DEVUELTO_OBSERVADO'].includes(status)) {
                    if (!$('#actual_return_date').val()) {
                        const now = new Date();
                        const year = now.getFullYear();
                        const month = String(now.getMonth() + 1).padStart(2, '0');
                        const day = String(now.getDate()).padStart(2, '0');
                        const hours = String(now.getHours()).padStart(2, '0');
                        const minutes = String(now.getMinutes()).padStart(2, '0');
                        $('#actual_return_date').val(`${year}-${month}-${day}T${hours}:${minutes}`);
                    }
                }
            });
        });
    </script>
@endsection
