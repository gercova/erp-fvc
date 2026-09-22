@extends('admin.layout')
@section('title', 'Registrar Nuevo Bien Patrimonial')
@section('content')
    <div class="container-fluid px-4 py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Bienes Patrimoniales</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Nuevo Registro</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-900 fw-bold">
                    <i class="fas fa-plus-circle me-2"></i>Registrar Bien Patrimonial
                </h1>
                <p class="text-muted small mb-0">Ingrese los datos técnicos del activo conforme al formato institucional
                    único</p>
            </div>
            <div>
                <a href="{{ route('inventory.index', ['area_id' => $selectedAreaId]) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver al Listado
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fs-4 me-3"></i>
                    <div>
                        <strong class="d-block mb-1">Por favor revise los siguientes errores:</strong>
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

        <form action="{{ route('inventory.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-4">
                <!-- Columna Izquierda: Información de Identificación y Técnica -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-info-circle me-1"></i> Identificación y Clasificación del Activo
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-dark">
                                        Departamento / Área <span class="text-danger">*</span>
                                    </label>
                                    <select name="area_id" id="form_area_id" class="form-select" required>
                                        @foreach ($areas as $area)
                                            <option value="{{ $area->id }}"
                                                {{ old('area_id', $selectedAreaId) == $area->id ? 'selected' : '' }}>
                                                {{ $area->name }} ({{ $area->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">
                                        N° ORD (Secuencial)
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light font-monospace fw-bold">#</span>
                                        <input type="number" name="orden" id="form_orden"
                                            class="form-control font-monospace" value="{{ old('orden', $nextOrden) }}"
                                            min="1">
                                    </div>
                                    <small class="text-muted">Calculado automáticamente</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Código de Producto (Catálogo SBN)
                                    </label>
                                    <input type="text" name="codigo_producto" class="form-control font-monospace"
                                        placeholder="Ej: 74648390 (No aplica a todos)" value="{{ old('codigo_producto') }}">
                                    <small class="text-muted">Código general de catálogo de bienes</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Código Interno / Código Patrimonial
                                    </label>
                                    <input type="text" name="codigo" class="form-control font-monospace"
                                        placeholder="Ej: 003-10  01 o 07-22" value="{{ old('codigo') }}">
                                    <small class="text-muted">Código patrimonial del instituto</small>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">
                                        Descripción Detallada del Bien <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="descripcion" class="form-control" rows="3" required
                                        placeholder="Ej: SILLA FIJA DE METAL CON RUEDA, TAPIZADA EN MARRÓN...">{{ old('descripcion') }}</textarea>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Marca</label>
                                    <input type="text" name="marca" class="form-control" placeholder="SIN MARCA"
                                        value="{{ old('marca', 'SIN MARCA') }}">
                                    <small class="text-muted">Si no tiene, dejar "SIN MARCA"</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Modelo</label>
                                    <input type="text" name="modelo" class="form-control" placeholder="SIN MODELO"
                                        value="{{ old('modelo', 'SIN MODELO') }}">
                                    <small class="text-muted">Si no tiene, dejar "SIN MODELO"</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Serie (N° de Serie)</label>
                                    <input type="text" name="serie" class="form-control font-monospace"
                                        placeholder="SIN SERIE" value="{{ old('serie', 'SIN SERIE') }}">
                                    <small class="text-muted">Si no tiene, dejar "SIN SERIE"</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ubicación y Adquisición -->
                    <div class="card shadow-sm border-0 bg-white">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-map-marked-alt me-1"></i> Ubicación, Adquisición y Observaciones
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Ubicación Física <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="ubicacion" class="form-control" required
                                        placeholder="Ej: Oficina Principal, Almacén General, Lab 01"
                                        value="{{ old('ubicacion') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Encargado en el Área / Custodio (ENC. AREA)
                                    </label>
                                    <input type="text" name="custodio" class="form-control"
                                        placeholder="Ej: KARINA, LEONIDAS, JHEYSER" value="{{ old('custodio') }}">
                                    <small class="text-muted">Persona responsable directa del bien</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Costo Estimado (S/)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light fw-bold">S/</span>
                                        <input type="number" step="0.01" min="0" name="costo"
                                            class="form-control" value="{{ old('costo', '0.00') }}">
                                    </div>
                                    <small class="text-muted">Si no aplica, dejar en 0.00</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Fecha de Adquisición</label>
                                    <input type="date" name="fecha_adquisicion" class="form-control"
                                        value="{{ old('fecha_adquisicion') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Año de Adquisición (AÑO ADQ.)</label>
                                    <input type="text" name="anio_adquisicion" class="form-control"
                                        placeholder="Ej: 2010, 2025" value="{{ old('anio_adquisicion') }}">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Observaciones / Detalles</label>
                                    <textarea name="observaciones" class="form-control" rows="2"
                                        placeholder="Ej: RETIRADO ABAST. BAJA, POR REPARAR, FACT.E001-177...">{{ old('observaciones') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Estado, Condición, Tipo Adquisición y Fotografía -->
                <div class="col-lg-4">
                    <!-- Condición Física -->
                    <div class="card shadow-sm border-0 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-heartbeat me-1"></i> Condición Física (CONDICIÓN)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-2">
                                <label class="p-2 border rounded d-flex align-items-center cursor-pointer bg-light">
                                    <input type="radio" name="condicion" value="B" class="form-check-input me-2"
                                        {{ old('condicion', 'B') == 'B' ? 'checked' : '' }}>
                                    <div>
                                        <span class="badge bg-success me-1">B</span>
                                        <strong>Bueno</strong>
                                        <div class="small text-muted">Operativo y en óptimas condiciones</div>
                                    </div>
                                </label>

                                <label class="p-2 border rounded d-flex align-items-center cursor-pointer bg-light">
                                    <input type="radio" name="condicion" value="R" class="form-check-input me-2"
                                        {{ old('condicion') == 'R' ? 'checked' : '' }}>
                                    <div>
                                        <span class="badge bg-warning text-dark me-1">R</span>
                                        <strong>Regular</strong>
                                        <div class="small text-muted">Desgaste moderado, operable</div>
                                    </div>
                                </label>

                                <label class="p-2 border rounded d-flex align-items-center cursor-pointer bg-light">
                                    <input type="radio" name="condicion" value="M" class="form-check-input me-2"
                                        {{ old('condicion') == 'M' ? 'checked' : '' }}>
                                    <div>
                                        <span class="badge bg-danger me-1">M</span>
                                        <strong>Malo</strong>
                                        <div class="small text-muted">Inoperativo, requiere reparación urgente</div>
                                    </div>
                                </label>

                                <label class="p-2 border rounded d-flex align-items-center cursor-pointer bg-light">
                                    <input type="radio" name="condicion" value="BAJA" class="form-check-input me-2"
                                        {{ old('condicion') == 'BAJA' ? 'checked' : '' }}>
                                    <div>
                                        <span class="badge bg-dark me-1">BAJA</span>
                                        <strong>Baja (Written-off)</strong>
                                        <div class="small text-muted">Desecho, chatarra o retiro definitivo</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Tipo de Adquisición -->
                    <div class="card shadow-sm border-0 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-handshake me-1"></i> Tipo de Adquisición (TIPO ADQ.)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label
                                        class="p-2 border rounded d-flex align-items-center justify-content-center cursor-pointer bg-light w-100 text-center">
                                        <input type="radio" name="tipo_adquisicion" value="C"
                                            class="form-check-input me-2"
                                            {{ old('tipo_adquisicion', 'C') == 'C' ? 'checked' : '' }}>
                                        <div>
                                            <span class="badge bg-primary d-block mb-1">C</span>
                                            <span class="small fw-bold">Compra</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <label
                                        class="p-2 border rounded d-flex align-items-center justify-content-center cursor-pointer bg-light w-100 text-center">
                                        <input type="radio" name="tipo_adquisicion" value="D"
                                            class="form-check-input me-2"
                                            {{ old('tipo_adquisicion') == 'D' ? 'checked' : '' }}>
                                        <div>
                                            <span class="badge bg-info text-white d-block mb-1">D</span>
                                            <span class="small fw-bold">Donación</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fotografía Opcional -->
                    <div class="card shadow-sm border-0 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-camera me-1"></i> Fotografía del Activo
                            </h6>
                        </div>
                        <div class="card-body text-center">
                            <div id="image_preview_box"
                                class="mb-3 p-3 border rounded bg-light d-flex align-items-center justify-content-center"
                                style="min-height: 140px;">
                                <div class="text-muted small text-center" id="no_image_text">
                                    <i class="fas fa-image fs-1 d-block mb-2 text-secondary"></i>
                                    Ninguna imagen seleccionada
                                </div>
                                <img id="image_preview" src="#" alt="Vista previa"
                                    class="img-fluid rounded d-none" style="max-height: 180px;">
                            </div>
                            <input type="file" name="foto" id="form_foto" class="form-control form-control-sm"
                                accept="image/*">
                            <small class="text-muted d-block mt-1">Formatos: JPG, PNG, WEBP (Máx. 3MB)</small>
                        </div>
                    </div>

                    <!-- Botón de Guardado -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm fw-bold">
                            <i class="fas fa-save me-2"></i> Registrar Activo
                        </button>
                        <a href="{{ route('inventory.index', ['area_id' => $selectedAreaId]) }}"
                            class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Preview de imagen
            $('#form_foto').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#image_preview').attr('src', e.target.result).removeClass('d-none');
                        $('#no_image_text').addClass('d-none');
                    }
                    reader.readAsDataURL(file);
                } else {
                    $('#image_preview').addClass('d-none');
                    $('#no_image_text').removeClass('d-none');
                }
            });
        });
    </script>
@endsection
