@extends('admin.layout')
@section('title', 'Registrar Préstamo de Bien Patrimonial')

@section('content')
    <div class="container-fluid px-4 py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Bienes Patrimoniales</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('asset_loans.index') }}">Préstamos</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Nuevo Préstamo</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-900 fw-bold">
                    <i class="fas fa-hand-holding me-2"></i>Registrar Préstamo de Bien
                </h1>
                <p class="text-muted small mb-0">Complete la información para la asignación y salida temporal del bien patrimonial</p>
            </div>
            <div>
                <a href="{{ route('asset_loans.index', ['area_id' => $selectedAreaId]) }}" class="btn btn-outline-secondary">
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

        <form action="{{ route('asset_loans.store') }}" method="POST">
            @csrf
            <div class="row g-4">
                <!-- Columna Izquierda: Solicitante y Activo -->
                <div class="col-lg-7">
                    <!-- Tarjeta: Perfil del Solicitante -->
                    <div class="card shadow-sm border-0 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-user-check me-1"></i> 1. Clasificación del Solicitante / Beneficiario
                            </h6>
                            <span class="badge bg-light text-muted border">Paso 1 de 2</span>
                        </div>
                        <div class="card-body">
                            <label class="form-label small fw-bold text-dark mb-2">
                                Tipo de Solicitante <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2 mb-3">
                                <div class="col-sm-4">
                                    <input type="radio" class="btn-check" name="borrower_type" id="type_student"
                                        value="STUDENT" {{ old('borrower_type', 'STUDENT') === 'STUDENT' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-info w-100 py-2 d-flex flex-column align-items-center" for="type_student">
                                        <i class="fas fa-user-graduate fs-5 mb-1"></i>
                                        <span class="fw-semibold">Estudiante / Alumno</span>
                                    </label>
                                </div>
                                <div class="col-sm-4">
                                    <input type="radio" class="btn-check" name="borrower_type" id="type_faculty"
                                        value="FACULTY" {{ old('borrower_type') === 'FACULTY' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center" for="type_faculty">
                                        <i class="fas fa-chalkboard-teacher fs-5 mb-1"></i>
                                        <span class="fw-semibold">Docente / Profesor</span>
                                    </label>
                                </div>
                                <div class="col-sm-4">
                                    <input type="radio" class="btn-check" name="borrower_type" id="type_administrative"
                                        value="ADMINISTRATIVE" {{ old('borrower_type') === 'ADMINISTRATIVE' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary w-100 py-2 d-flex flex-column align-items-center" for="type_administrative">
                                        <i class="fas fa-user-tie fs-5 mb-1"></i>
                                        <span class="fw-semibold">Personal Administrativo</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Selector de usuario registrado para docentes o administrativos -->
                            <div id="user_picker_container" class="mb-3 p-3 bg-light rounded border {{ old('borrower_type', 'STUDENT') === 'STUDENT' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-dark">
                                    <i class="fas fa-search me-1"></i> Seleccionar de Personal Registrado (Opcional)
                                </label>
                                <select name="borrower_user_id" id="borrower_user_id" class="form-select">
                                    <option value="">-- Seleccionar usuario o ingresar manualmente abajo --</option>
                                    @foreach ($systemUsers as $u)
                                        <option value="{{ $u->id }}"
                                            data-name="{{ $u->nombres }}"
                                            data-email="{{ $u->correo }}"
                                            data-phone="{{ $u->telefono }}"
                                            {{ old('borrower_user_id') == $u->id ? 'selected' : '' }}>
                                            {{ $u->nombres }} ({{ $u->correo ?: 'Sin correo' }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Si selecciona un usuario, sus datos se completarán automáticamente.</small>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold text-dark">
                                        Nombre Completo del Solicitante <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="borrower_name" id="borrower_name" class="form-control"
                                        placeholder="Ej: Pérez Quispe, Juan Carlos" value="{{ old('borrower_name') }}" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-dark">
                                        N° Documento (DNI / CE) <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="borrower_document" id="borrower_document" class="form-control font-monospace"
                                        placeholder="Ej: 74648119" value="{{ old('borrower_document') }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark" id="label_borrower_code">
                                        Código de Estudiante / Carné
                                    </label>
                                    <input type="text" name="borrower_code" id="borrower_code" class="form-control font-monospace"
                                        placeholder="Ej: 2024-DS-014" value="{{ old('borrower_code') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark" id="label_borrower_career">
                                        Programa de Estudios / Carrera
                                    </label>
                                    <input type="text" name="borrower_career_or_area" id="borrower_career_or_area" class="form-control"
                                        placeholder="Ej: Desarrollo de Sistemas de Información" value="{{ old('borrower_career_or_area') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Teléfono / Celular</label>
                                    <input type="text" name="borrower_phone" id="borrower_phone" class="form-control"
                                        placeholder="Ej: 987654321" value="{{ old('borrower_phone') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Correo Electrónico</label>
                                    <input type="email" name="borrower_email" id="borrower_email" class="form-control"
                                        placeholder="Ej: alumno@instituto.edu.pe" value="{{ old('borrower_email') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta: Bien y Destino -->
                    <div class="card shadow-sm border-0 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-box me-1"></i> 2. Bien Patrimonial y Destino de Uso
                            </h6>
                            <span class="badge bg-light text-muted border">Paso 2 de 2</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-dark">
                                        Departamento / Área de Custodia <span class="text-danger">*</span>
                                    </label>
                                    <select name="area_id" id="area_id" class="form-select" required>
                                        @foreach ($areas as $area)
                                            <option value="{{ $area->id }}" {{ old('area_id', $selectedAreaId) == $area->id ? 'selected' : '' }}>
                                                {{ $area->name }} ({{ $area->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-7">
                                    <label class="form-label small fw-bold text-dark">
                                        Bien Patrimonial Disponible <span class="text-danger">*</span>
                                    </label>
                                    <select name="asset_id" id="asset_id" class="form-select" required>
                                        <option value="">-- Seleccione un activo disponible --</option>
                                        @foreach ($availableAssets as $asset)
                                            <option value="{{ $asset->id }}"
                                                data-cond="{{ $asset->condicion }}"
                                                data-desc="{{ $asset->descripcion }}"
                                                {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
                                                [{{ $asset->codigo ?: 'ORD #' . $asset->orden }}] {{ Str::limit($asset->descripcion, 40) }}
                                                ({{ $asset->marca !== 'SIN MARCA' ? $asset->marca : 'S/M' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small id="asset_loading_text" class="text-muted d-none">
                                        <i class="fas fa-spinner fa-spin me-1"></i>Cargando activos del área...
                                    </small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Ambiente / Ubicación de Uso <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="destination" class="form-control" required
                                        placeholder="Ej: Aula 204, Laboratorio de Cómputo B, Auditorio" value="{{ old('destination') }}">
                                    <small class="text-muted">Lugar físico donde se utilizará el bien</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Finalidad / Motivo del Préstamo</label>
                                    <input type="text" name="purpose" class="form-control"
                                        placeholder="Ej: Clase práctica de Programación Web" value="{{ old('purpose') }}">
                                    <small class="text-muted">Breve justificación académica o laboral</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Plazos y Condiciones -->
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-calendar-check me-1"></i> Plazos y Condiciones del Préstamo
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="p-3 bg-light rounded border mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted fw-bold text-uppercase">Código Folio Generado</span>
                                    <span class="badge bg-primary font-monospace">{{ $nextCode }}</span>
                                </div>
                                <small class="text-muted d-block mt-1">El correlativo oficial se genera automáticamente al guardar.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">
                                    Fecha y Hora de Entrega <span class="text-danger">*</span>
                                </label>
                                <input type="datetime-local" name="loan_date" class="form-control" required
                                    value="{{ old('loan_date', date('Y-m-d\TH:i')) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">
                                    Fecha y Hora Estimada de Devolución <span class="text-danger">*</span>
                                </label>
                                <input type="datetime-local" name="expected_return_date" class="form-control" required
                                    value="{{ old('expected_return_date', date('Y-m-d\TH:i', strtotime('+4 hours'))) }}">
                                <small class="text-muted">Si se excede esta fecha, el préstamo figurará como vencido.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">
                                    Condición Física al Momento de la Entrega <span class="text-danger">*</span>
                                </label>
                                <select name="initial_condition" id="initial_condition" class="form-select" required>
                                    <option value="B" {{ old('initial_condition', 'B') === 'B' ? 'selected' : '' }}>Bueno (B) - Totalmente operativo</option>
                                    <option value="R" {{ old('initial_condition') === 'R' ? 'selected' : '' }}>Regular (R) - Operativo con desgaste</option>
                                    <option value="M" {{ old('initial_condition') === 'M' ? 'selected' : '' }}>Malo (M) - Dañado / Para reparación</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Observaciones de Salida / Accesorios</label>
                                <textarea name="observations" class="form-control" rows="3"
                                    placeholder="Ej: Se entrega equipo completo con cargador, cable HDMI y funda de protección...">{{ old('observations') }}</textarea>
                            </div>

                            <div class="p-3 bg-light rounded border small text-muted">
                                <div><i class="fas fa-user-shield me-1 text-primary"></i> <strong>Responsable que entrega:</strong></div>
                                <div class="text-dark fw-semibold mt-1">{{ Auth::user()->nombres }}</div>
                                <div class="text-muted small">Registrado con cuenta institucional</div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="card shadow-sm border-0 bg-white">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary py-2 fw-semibold">
                                    <i class="fas fa-check-circle me-1"></i> Registrar Préstamo de Bien
                                </button>
                                <a href="{{ route('asset_loans.index', ['area_id' => $selectedAreaId]) }}"
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
            // Cambio de tipo de solicitante
            $('input[name="borrower_type"]').change(function() {
                const type = $(this).val();

                if (type === 'STUDENT') {
                    $('#user_picker_container').addClass('d-none');
                    $('#label_borrower_code').text('Código de Estudiante / Matrícula');
                    $('#label_borrower_career').text('Programa de Estudios / Carrera');
                    $('#borrower_code').attr('placeholder', 'Ej: 2024-DS-014');
                    $('#borrower_career_or_area').attr('placeholder', 'Ej: Desarrollo de Sistemas de Información');
                } else if (type === 'FACULTY') {
                    $('#user_picker_container').removeClass('d-none');
                    $('#label_borrower_code').text('Código Docente / ID');
                    $('#label_borrower_career').text('Departamento Académico / Especialidad');
                    $('#borrower_code').attr('placeholder', 'Ej: DOC-042');
                    $('#borrower_career_or_area').attr('placeholder', 'Ej: Unidad Académica de Informática');
                } else {
                    $('#user_picker_container').removeClass('d-none');
                    $('#label_borrower_code').text('Código de Empleado / Escalafón');
                    $('#label_borrower_career').text('Oficina / Unidad Administrativa');
                    $('#borrower_code').attr('placeholder', 'Ej: ADM-018');
                    $('#borrower_career_or_area').attr('placeholder', 'Ej: Oficina de Abastecimiento');
                }
            });

            // Autocompletar datos al seleccionar un usuario del sistema
            $('#borrower_user_id').change(function() {
                const selected = $(this).find('option:selected');
                if ($(this).val()) {
                    $('#borrower_name').val(selected.data('name') || '');
                    if (selected.data('email')) $('#borrower_email').val(selected.data('email'));
                    if (selected.data('phone')) $('#borrower_phone').val(selected.data('phone'));
                }
            });

            // Cargar activos disponibles al cambiar de área
            $('#area_id').change(function() {
                const areaId = $(this).val();
                if (!areaId) return;

                $('#asset_loading_text').removeClass('d-none');
                $('#asset_id').prop('disabled', true);

                $.ajax({
                    url: "{{ route('asset_loans.assets_by_area') }}",
                    type: 'GET',
                    data: { area_id: areaId },
                    success: function(resp) {
                        $('#asset_loading_text').addClass('d-none');
                        $('#asset_id').prop('disabled', false).empty();

                        if (resp.assets && resp.assets.length > 0) {
                            $('#asset_id').append('<option value="">-- Seleccione un activo disponible (' + resp.assets.length + ') --</option>');
                            resp.assets.forEach(function(a) {
                                const code = a.codigo || ('ACT-' + a.id);
                                const brand = (a.marca && a.marca !== 'SIN MARCA') ? a.marca : 'S/M';
                                $('#asset_id').append(
                                    $('<option></option>')
                                        .val(a.id)
                                        .attr('data-cond', a.condicion)
                                        .text('[' + code + '] ' + a.descripcion + ' (' + brand + ')')
                                );
                            });
                        } else {
                            $('#asset_id').append('<option value="">-- No hay activos disponibles para préstamo en esta área --</option>');
                        }
                    },
                    error: function() {
                        $('#asset_loading_text').addClass('d-none');
                        $('#asset_id').prop('disabled', false);
                        Swal.fire('Error', 'No se pudieron cargar los activos del área seleccionada.', 'error');
                    }
                });
            });

            // Sincronizar condición inicial al seleccionar un activo
            $('#asset_id').change(function() {
                const cond = $(this).find('option:selected').data('cond');
                if (cond && ['B', 'R', 'M'].includes(cond)) {
                    $('#initial_condition').val(cond);
                }
            });
        });
    </script>
@endsection
