<div class="modal fade" id="modalAddUser" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="form_save" class="modal-content" enctype="multipart/form-data" onsubmit="event.preventDefault()">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Registrar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nombres <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" name="nombres" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Usuario <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-lowercase" name="user" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" required>
                            <option value="">[SELECCIONE]</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado">
                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Caja <span class="text-danger">*</span></label>
                        <select class="form-select" name="idcaja" required>
                            @foreach ($cashes as $cash)
                                <option value="{{ $cash->id }}">{{ $cash->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Almacenes asignados <span class="text-danger">*</span></label>
                        <select class="form-select" name="warehouse_ids[]" multiple required>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->descripcion }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">El primer almacén seleccionado quedará como activo inicial.</small>
                    </div>

                    <!-- Asignación de Área y Cargo Institucional -->
                    <div class="col-12 mt-4 pt-2 border-top">
                        <h6 class="fw-bold text-dark mb-2"><i class="ri-building-line me-1 text-primary"></i>Área y Cargo Institucional</h6>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Área de Pertenencia</label>
                        <select class="form-select" name="area_id">
                            <option value="">[SIN ÁREA ASIGNADA]</option>
                            @foreach ($areas as $areaItem)
                                <option value="{{ $areaItem->id }}">{{ $areaItem->code }} - {{ $areaItem->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Cargo / Puesto</label>
                        <input type="text" class="form-control" name="cargo" placeholder="Ej. Docente, Administrador...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Condición Laboral</label>
                        <select class="form-select" name="condicion_laboral">
                            <option value="">[SELECCIONE]</option>
                            <option value="NOMBRADO">Nombrado</option>
                            <option value="CONTRATADO">Contratado</option>
                            <option value="CAS">CAS</option>
                            <option value="LOCACIÓN DE SERVICIOS">Locación de servicios</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>

                    <!-- Firma Digital -->
                    <div class="col-12 mt-4 pt-2 border-top">
                        <h6 class="fw-bold text-dark mb-2"><i class="ri-quill-pen-line me-1 text-primary"></i>Firma Digitalizada / Rúbrica</h6>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Archivo de Firma Digital (Imagen)</label>
                        <input type="file" class="form-control" name="firma_digital" accept="image/png, image/jpeg, image/webp, image/svg+xml">
                        <small class="text-muted">Formatos permitidos: PNG, JPG, WEBP. Utilizada en la visación y aprobación de documentos institucionales.</small>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-success btn-save">
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

<div class="modal fade" id="modalEditUser" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="form_edit" class="modal-content" enctype="multipart/form-data" onsubmit="event.preventDefault()">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Actualizar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <input type="hidden" name="id">
                    <input type="hidden" name="remove_firma_digital" id="edit_remove_firma_digital" value="0">

                    <div class="col-12">
                        <label class="form-label">Nombres <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" name="nombres" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Usuario <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-lowercase" name="user" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contraseña</label>
                        <input type="password" class="form-control" name="password" placeholder="Solo si desea cambiarla">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" required>
                            <option value="">[SELECCIONE]</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Caja <span class="text-danger">*</span></label>
                        <select class="form-select" name="idcaja" required>
                            @foreach ($cashes as $cash)
                                <option value="{{ $cash->id }}">{{ $cash->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Almacenes asignados <span class="text-danger">*</span></label>
                        <select class="form-select" name="warehouse_ids[]" multiple required>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->descripcion }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">El primer almacén seleccionado quedará como activo inicial.</small>
                    </div>

                    <!-- Asignación de Área y Cargo Institucional -->
                    <div class="col-12 mt-4 pt-2 border-top">
                        <h6 class="fw-bold text-dark mb-2"><i class="ri-building-line me-1 text-primary"></i>Área y Cargo Institucional</h6>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Área de Pertenencia</label>
                        <select class="form-select" name="area_id">
                            <option value="">[SIN ÁREA ASIGNADA]</option>
                            @foreach ($areas as $areaItem)
                                <option value="{{ $areaItem->id }}">{{ $areaItem->code }} - {{ $areaItem->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Cargo / Puesto</label>
                        <input type="text" class="form-control" name="cargo" placeholder="Ej. Docente, Administrador...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Condición Laboral</label>
                        <select class="form-select" name="condicion_laboral">
                            <option value="">[SELECCIONE]</option>
                            <option value="NOMBRADO">Nombrado</option>
                            <option value="CONTRATADO">Contratado</option>
                            <option value="CAS">CAS</option>
                            <option value="LOCACIÓN DE SERVICIOS">Locación de servicios</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>

                    <!-- Firma Digital -->
                    <div class="col-12 mt-4 pt-2 border-top">
                        <h6 class="fw-bold text-dark mb-2"><i class="ri-quill-pen-line me-1 text-primary"></i>Firma Digitalizada / Rúbrica</h6>
                    </div>

                    <div class="col-12">
                        <div id="wrapper_signature_preview" class="d-none mb-3 p-3 bg-light border rounded">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <img id="img_signature_preview" src="" alt="Firma Digital" style="max-height: 55px; max-width: 160px; object-fit: contain;" class="bg-white border p-1 rounded me-3">
                                    <div>
                                        <div class="small fw-semibold text-dark">Firma digital actual registrada</div>
                                        <div class="text-muted" style="font-size: 11px;">Esta firma se estampa en los documentos oficiales.</div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="btn_remove_signature">
                                    <i class="ri-delete-bin-line me-1"></i> Quitar firma
                                </button>
                            </div>
                        </div>

                        <label class="form-label">Nueva Firma Digital (Imagen)</label>
                        <input type="file" class="form-control" name="firma_digital" accept="image/png, image/jpeg, image/webp, image/svg+xml">
                        <small class="text-muted">Seleccione solo si desea registrar o sustituir la firma existente (PNG, JPG, WEBP).</small>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-success btn-store">
                            <span class="text-store">Guardar cambios</span>
                            <span class="me-1 d-none text-storing" role="status" aria-hidden="true"><i class="fas fa-spinner fa-spin"></i></span>
                            <span class="text-storing d-none">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalUploadUsers" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="form_excel_users" class="modal-content" onsubmit="event.preventDefault()" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-file-excel-2-line me-2 text-success"></i>Carga Masiva de Usuarios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="p-3 bg-light border rounded">
                            <h6 class="fw-semibold text-dark mb-1">Instrucciones de Importación</h6>
                            <p class="small text-muted mb-2">
                                Suba un archivo en formato <strong>.xlsx</strong> para registrar o actualizar usuarios en lote. Puede asignar automáticamente roles, almacenes y áreas institucionales.
                            </p>
                            <a href="{{ route('users.download_template') }}" class="btn btn-sm btn-dark">
                                <i class="ri-download-2-line me-1"></i> Descargar Plantilla Excel Oficial
                            </a>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="excel_users_file" class="form-label fw-medium">Seleccionar archivo Excel (.xlsx)</label>
                        <input type="file" id="excel_users_file" class="form-control" name="excel" accept=".xlsx, .xls" required>
                        <small class="text-muted">Asegúrese de respetar los nombres de columnas de la plantilla descargada.</small>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-success btn-upload-user-submit">
                            <span class="text-upload-user">Importar Usuarios</span>
                            <span class="spinner-border spinner-border-sm me-1 d-none text-uploading-user" role="status" aria-hidden="true"></span>
                            <span class="text-uploading-user d-none">Importando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalUpdateRole" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <form id="form_update_role" class="modal-content" onsubmit="event.preventDefault()">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Rol Rápido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="hidden" name="id">
                        <input type="text" class="form-control text-uppercase" name="usuario" disabled>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Rol</label>
                        <div id="wrapper_roles"></div>
                    </div>

                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button class="btn btn-success btn-update-role">
                            <span class="text-update-role">Guardar cambios</span>
                            <span class="me-1 text-saving-role d-none" role="status" aria-hidden="true"><i class="fas fa-spinner fa-spin"></i></span>
                            <span class="text-saving-role d-none">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
