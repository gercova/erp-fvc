@extends('admin.layout')
@section('title', 'Editar Bien Patrimonial #' . $asset->orden)

@section('content')
    <div class="container-fluid px-4 py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Bienes Patrimoniales</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Editar Activo #{{ $asset->formatted_orden }}
                        </li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-900 fw-bold">
                    <i class="fas fa-edit me-2"></i>Editar Bien Patrimonial
                </h1>
                <p class="text-muted small mb-0">Actualice los datos técnicos, condición física o reubicación del activo</p>
            </div>
            <div>
                <a href="{{ route('inventory.index', ['area_id' => $asset->area_id]) }}" class="btn btn-outline-secondary">
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

        <form action="{{ route('inventory.update', $asset->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row g-4">
                <!-- Columna Izquierda: Identificación y Clasificación -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 mb-4 bg-white">
                        <div
                            class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-info-circle me-1"></i> Identificación y Clasificación del Activo
                            </h6>
                            <span class="badge bg-light text-dark font-monospace border">UUID:
                                {{ substr($asset->uuid, 0, 8) }}...</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-dark">
                                        Departamento / Área <span class="text-danger">*</span>
                                    </label>
                                    <select name="area_id" class="form-select" required>
                                        @foreach ($areas as $area)
                                            <option value="{{ $area->id }}"
                                                {{ old('area_id', $asset->area_id) == $area->id ? 'selected' : '' }}>
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
                                        <input type="number" name="orden" class="form-control font-monospace"
                                            value="{{ old('orden', $asset->orden) }}" min="1">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Código de Producto (Catálogo SBN)
                                    </label>
                                    <input type="text" name="codigo_producto" class="form-control font-monospace"
                                        placeholder="Ej: 74648390"
                                        value="{{ old('codigo_producto', $asset->codigo_producto) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Código Interno / Código Patrimonial
                                    </label>
                                    <input type="text" name="codigo" class="form-control font-monospace"
                                        placeholder="Ej: 003-10  01" value="{{ old('codigo', $asset->codigo) }}">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">
                                        Descripción Detallada del Bien <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="descripcion" class="form-control" rows="3" required>{{ old('descripcion', $asset->descripcion) }}</textarea>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Marca</label>
                                    <input type="text" name="marca" class="form-control"
                                        value="{{ old('marca', $asset->marca) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Modelo</label>
                                    <input type="text" name="modelo" class="form-control"
                                        value="{{ old('modelo', $asset->modelo) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Serie (N° de Serie)</label>
                                    <input type="text" name="serie" class="form-control font-monospace"
                                        value="{{ old('serie', $asset->serie) }}">
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
                                        value="{{ old('ubicacion', $asset->ubicacion) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Encargado en el Área / Custodio (ENC. AREA)
                                    </label>
                                    <input type="text" name="custodio" class="form-control"
                                        value="{{ old('custodio', $asset->custodio) }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Costo Estimado (S/)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light fw-bold">S/</span>
                                        <input type="number" step="0.01" min="0" name="costo"
                                            class="form-control" value="{{ old('costo', $asset->costo) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Fecha de Adquisición</label>
                                    <input type="date" name="fecha_adquisicion" class="form-control"
                                        value="{{ old('fecha_adquisicion', $asset->fecha_adquisicion?->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Año de Adquisición (AÑO ADQ.)</label>
                                    <input type="text" name="anio_adquisicion" class="form-control"
                                        value="{{ old('anio_adquisicion', $asset->anio_adquisicion) }}">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Observaciones / Detalles</label>
                                    <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones', $asset->observaciones) }}</textarea>
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
                                        {{ old('condicion', $asset->condicion) == 'B' ? 'checked' : '' }}>
                                    <div>
                                        <span class="badge bg-success me-1">B</span>
                                        <strong>Bueno</strong>
                                        <div class="small text-muted">Operativo y en óptimas condiciones</div>
                                    </div>
                                </label>

                                <label class="p-2 border rounded d-flex align-items-center cursor-pointer bg-light">
                                    <input type="radio" name="condicion" value="R" class="form-check-input me-2"
                                        {{ old('condicion', $asset->condicion) == 'R' ? 'checked' : '' }}>
                                    <div>
                                        <span class="badge bg-warning text-dark me-1">R</span>
                                        <strong>Regular</strong>
                                        <div class="small text-muted">Desgaste moderado, operable</div>
                                    </div>
                                </label>

                                <label class="p-2 border rounded d-flex align-items-center cursor-pointer bg-light">
                                    <input type="radio" name="condicion" value="M" class="form-check-input me-2"
                                        {{ old('condicion', $asset->condicion) == 'M' ? 'checked' : '' }}>
                                    <div>
                                        <span class="badge bg-danger me-1">M</span>
                                        <strong>Malo</strong>
                                        <div class="small text-muted">Inoperativo, requiere reparación urgente</div>
                                    </div>
                                </label>

                                <label class="p-2 border rounded d-flex align-items-center cursor-pointer bg-light">
                                    <input type="radio" name="condicion" value="BAJA" class="form-check-input me-2"
                                        {{ old('condicion', $asset->condicion) == 'BAJA' ? 'checked' : '' }}>
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
                                            {{ old('tipo_adquisicion', $asset->tipo_adquisicion) == 'C' ? 'checked' : '' }}>
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
                                            {{ old('tipo_adquisicion', $asset->tipo_adquisicion) == 'D' ? 'checked' : '' }}>
                                        <div>
                                            <span class="badge bg-info text-white d-block mb-1">D</span>
                                            <span class="small fw-bold">Donación</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fotografía -->
                    <div class="card shadow-sm border-0 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="m-0 fw-bold text-primary">
                                <i class="fas fa-camera me-1"></i> Fotografía del Activo
                            </h6>
                        </div>
                        <div class="card-body text-center">
                            <div id="image_preview_box"
                                class="mb-3 p-2 border rounded bg-light d-flex align-items-center justify-content-center"
                                style="min-height: 140px;">
                                @if ($asset->foto_path)
                                    <img id="image_preview" src="{{ asset($asset->foto_path) }}" alt="Foto del activo"
                                        class="img-fluid rounded" style="max-height: 180px;">
                                @else
                                    <div class="text-muted small text-center" id="no_image_text">
                                        <i class="fas fa-image fs-1 d-block mb-2 text-secondary"></i>
                                        Sin imagen registrada
                                    </div>
                                    <img id="image_preview" src="#" alt="Vista previa"
                                        class="img-fluid rounded d-none" style="max-height: 180px;">
                                @endif
                            </div>
                            <input type="file" name="foto" id="form_foto" class="form-control form-control-sm"
                                accept="image/*">
                            <small class="text-muted d-block mt-1">Seleccione un archivo si desea reemplazar la foto
                                actual.</small>
                        </div>
                    </div>

                    <!-- Botón de Guardado -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning btn-lg shadow-sm fw-bold text-dark">
                            <i class="fas fa-save me-2"></i> Guardar Cambios
                        </button>
                        <a href="{{ route('inventory.index', ['area_id' => $asset->area_id]) }}"
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
            $('#form_foto').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#image_preview').attr('src', e.target.result).removeClass('d-none');
                        $('#no_image_text').addClass('d-none');
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
@endsection
