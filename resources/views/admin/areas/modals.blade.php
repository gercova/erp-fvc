<div class="modal fade" id="modalAddArea" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="form_save_area" class="modal-content" onsubmit="event.preventDefault()">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Registrar Área / Unidad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Código <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase font-monospace" name="code" placeholder="Ej. ADM, UA, DG" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Nombre del Área <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="Ej. Área de Administración" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tipo de Área <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" required>
                            <option value="">[SELECCIONE]</option>
                            @foreach ($types as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nivel Jerárquico</label>
                        <input type="number" class="form-control" name="level" value="1" min="1" max="10">
                        <small class="text-muted">Nivel dentro del organigrama (1 = Dirección General, 2 = Áreas principales...)</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Depende de (Área Superior)</label>
                        <select class="form-select" name="parent_id">
                            <option value="">[NINGUNA - NIVEL PRINCIPAL]</option>
                            @foreach ($areas as $areaItem)
                                <option value="{{ $areaItem->id }}">{{ $areaItem->code }} - {{ $areaItem->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Responsable / Jefe Asignado</label>
                        <select class="form-select" name="head_user_id">
                            <option value="">[SIN ASIGNAR]</option>
                            @foreach ($heads as $headUser)
                                <option value="{{ $headUser->id }}">{{ $headUser->nombres }} (@ {{ $headUser->user }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_advisory_add" name="is_advisory" value="1">
                            <label class="form-check-label fw-medium" for="is_advisory_add">
                                Es Órgano Consultivo o de Asesoría (Línea punteada)
                            </label>
                        </div>
                        <small class="text-muted d-block ps-4">Las áreas de asesoría no forman parte obligatoria de la cadena de aprobaciones jerárquicas.</small>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-success btn-save-area">
                            <span class="text-save">Guardar cambios</span>
                            <span class="spinner-grow spinner-grow-sm me-1 d-none text-saving" role="status" aria-hidden="true"></span>
                            <span class="text-saving d-none">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditArea" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="form_edit_area" class="modal-content" onsubmit="event.preventDefault()">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Actualizar Área / Unidad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <input type="hidden" name="id">

                    <div class="col-md-4">
                        <label class="form-label">Código <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase font-monospace" name="code" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Nombre del Área <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tipo de Área <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" required>
                            <option value="">[SELECCIONE]</option>
                            @foreach ($types as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nivel Jerárquico</label>
                        <input type="number" class="form-control" name="level" min="1" max="10">
                        <small class="text-muted">Nivel dentro del organigrama</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Depende de (Área Superior)</label>
                        <select class="form-select" name="parent_id">
                            <option value="">[NINGUNA - NIVEL PRINCIPAL]</option>
                            @foreach ($areas as $areaItem)
                                <option value="{{ $areaItem->id }}">{{ $areaItem->code }} - {{ $areaItem->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Responsable / Jefe Asignado</label>
                        <select class="form-select" name="head_user_id">
                            <option value="">[SIN ASIGNAR]</option>
                            @foreach ($heads as $headUser)
                                <option value="{{ $headUser->id }}">{{ $headUser->nombres }} (@ {{ $headUser->user }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_advisory_edit" name="is_advisory" value="1">
                            <label class="form-check-label fw-medium" for="is_advisory_edit">
                                Es Órgano Consultivo o de Asesoría (Línea punteada)
                            </label>
                        </div>
                        <small class="text-muted d-block ps-4">Las áreas de asesoría no forman parte obligatoria de la cadena de aprobaciones jerárquicas.</small>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-success btn-store-area">
                            <span class="text-store">Guardar cambios</span>
                            <span class="spinner-grow spinner-grow-sm me-1 d-none text-storing" role="status" aria-hidden="true"></span>
                            <span class="text-storing d-none">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
