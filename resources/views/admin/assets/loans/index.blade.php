@extends('admin.layout')
@section('title', 'Control de Préstamos de Bienes Patrimoniales')

@section('styles')
    <style>
        .loan-card,
        .loan-card .card-body,
        .loan-card .table-responsive,
        .loan-card .dropdown,
        .loan-card td,
        .loan-card th {
            overflow: visible !important;
        }

        .loan-card .dropdown-menu {
            z-index: 1055 !important;
        }

        .table td {
            vertical-align: middle;
        }
    </style>
@endsection

@section('content')
    <!-- Header Principal Compacto Oficial -->
    <header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
        <div class="container-xl px-4">
            <div class="page-header-content">
                <div class="row align-items-center justify-content-between pt-3">
                    <div class="col-auto mb-3">
                        <h1 class="page-header-title">
                            <div class="page-header-icon"><i data-feather="repeat"></i></div>
                            Control de Préstamos de Bienes
                        </h1>
                    </div>
                    <div class="col-auto">
                        @can('assets.create')
                            <a href="{{ route('asset_loans.create', ['area_id' => $selectedAreaId]) }}"
                                class="btn btn-primary waves-effect waves-light mb-3 me-2">
                                <i class="ri-add-circle-line align-middle me-1"></i>
                                <span class="d-none d-sm-inline">Nuevo Préstamo</span>
                            </a>
                        @endcan
                        <a href="{{ route('inventory.index', ['area_id' => $selectedAreaId]) }}"
                            class="btn btn-outline-secondary waves-effect waves-light mb-3">
                            <i class="ri-archive-line align-middle me-1"></i>
                            <span class="d-none d-sm-inline">Ver Inventario</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-xl px-4 pb-5">
        <!-- Tarjetas de Resumen en Estilo Oficial (Sin Degradados) -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="card custom-card pro-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="small text-muted text-uppercase fw-semibold">Total Préstamos</div>
                                <div class="h3 mb-0 text-dark">{{ number_format($totalCount) }}</div>
                                <div class="small text-muted mt-1">Histórico registrado</div>
                            </div>
                            <div class="rounded-3 p-3" style="background: rgba(0, 97, 242, 0.1); color: var(--bs-primary);">
                                <i class="ri-file-list-3-line fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card custom-card pro-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="small text-muted text-uppercase fw-semibold">En Préstamo</div>
                                <div class="h3 mb-0 text-primary">{{ number_format($activeCount) }}</div>
                                <div class="small text-muted mt-1">Bienes en uso temporal</div>
                            </div>
                            <div class="rounded-3 p-3" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                                <i class="ri-hand-coin-line fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card custom-card pro-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="small text-muted text-uppercase fw-semibold">Vencidos</div>
                                <div class="h3 mb-0 text-danger">{{ number_format($overdueCount) }}</div>
                                <div class="small text-muted mt-1">Devolución pendiente</div>
                            </div>
                            <div class="rounded-3 p-3" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                                <i class="ri-alarm-warning-line fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card custom-card pro-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="small text-muted text-uppercase fw-semibold">Devueltos</div>
                                <div class="h3 mb-0 text-success">{{ number_format($returnedCount) }}</div>
                                <div class="small text-muted mt-1">Conformidad de entrega</div>
                            </div>
                            <div class="rounded-3 p-3" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                                <i class="ri-checkbox-circle-line fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Filtros -->
        <div class="card custom-card pro-card mb-4">
            <div class="card-body p-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Departamento / Área</label>
                        <select id="filter_area_id" class="form-select form-select-sm">
                            <option value="">-- Todas las áreas --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ $selectedAreaId == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }} ({{ $area->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Tipo de Solicitante</label>
                        <select id="filter_borrower_type" class="form-select form-select-sm">
                            <option value="">-- Todos los perfiles --</option>
                            <option value="STUDENT">Estudiantes / Alumnos</option>
                            <option value="FACULTY">Docentes / Profesores</option>
                            <option value="ADMINISTRATIVE">Personal Administrativo</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted">Estado del Préstamo</label>
                        <select id="filter_status" class="form-select form-select-sm">
                            <option value="">-- Todos los estados --</option>
                            <option value="PRESTADO">En Préstamo (Activos)</option>
                            <option value="OVERDUE">Vencidos (Límite excedido)</option>
                            <option value="RETURNED">Devueltos</option>
                            <option value="EXTRAVIADO">Extraviado / Dañado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Buscar Solicitante, Bien o Folio</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="ri-search-line text-muted"></i></span>
                            <input type="text" id="filter_search" class="form-control border-start-0"
                                placeholder="Ej: PRE-2026, Laptop, Juan Pérez, 74648119...">
                        </div>
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="button" id="btn_filter" class="btn btn-sm btn-primary w-100" title="Aplicar filtros">
                            <i class="ri-filter-2-line"></i>
                        </button>
                        <button type="button" id="btn_reset" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">
                            <i class="ri-refresh-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Préstamos -->
        <div class="card custom-card pro-card loan-card mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark d-flex align-items-center">
                    <i class="ri-table-line text-primary me-2"></i> Registro General de Préstamos de Bienes
                </h6>
                <div class="text-muted small">
                    Mostrando registros de: <strong>{{ $selectedArea?->name ?? 'Todas las áreas permitidas' }}</strong>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle w-100" id="loanTable">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th style="width: 120px;">Folio / Fecha</th>
                                <th>Bien Patrimonial</th>
                                <th>Solicitante / Beneficiario</th>
                                <th style="width: 170px;">Plazos de Préstamo</th>
                                <th>Ubicación / Destino</th>
                                <th class="text-center" style="width: 110px;">Estado</th>
                                <th class="text-center" style="width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Detalle Rápido del Préstamo -->
    <div class="modal fade" id="modalLoanDetail" tabindex="-1" aria-labelledby="modalLoanDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light py-3 border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="modalLoanDetailLabel">
                        <i class="fas fa-file-invoice text-primary me-2"></i>Detalle de Préstamo <span id="detail_code" class="font-monospace text-primary"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="detail_loading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted small mt-2">Cargando información del préstamo...</p>
                    </div>
                    <div id="detail_content" class="d-none">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border">
                                    <div class="small text-muted text-uppercase fw-bold mb-2">Datos del Solicitante</div>
                                    <h6 class="fw-bold text-dark mb-1" id="detail_borrower_name">-</h6>
                                    <div class="mb-2" id="detail_borrower_badge"></div>
                                    <ul class="list-unstyled small mb-0 text-muted">
                                        <li><strong>Documento (DNI/CE):</strong> <span id="detail_borrower_document" class="font-monospace text-dark">-</span></li>
                                        <li><strong>Código / Matrícula:</strong> <span id="detail_borrower_code" class="text-dark">-</span></li>
                                        <li><strong>Programa / Área:</strong> <span id="detail_borrower_career" class="text-dark">-</span></li>
                                        <li><strong>Teléfono:</strong> <span id="detail_borrower_phone" class="text-dark">-</span></li>
                                        <li><strong>Correo:</strong> <span id="detail_borrower_email" class="text-dark">-</span></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border">
                                    <div class="small text-muted text-uppercase fw-bold mb-2">Bien Patrimonial Prestado</div>
                                    <h6 class="fw-bold text-dark mb-1" id="detail_asset_desc">-</h6>
                                    <div class="small text-muted mb-2">
                                        Código: <span class="badge bg-secondary font-monospace" id="detail_asset_code">-</span>
                                        &bull; Condición: <span class="badge bg-info" id="detail_asset_cond">-</span>
                                    </div>
                                    <ul class="list-unstyled small mb-0 text-muted">
                                        <li><strong>Marca / Modelo:</strong> <span id="detail_asset_brand" class="text-dark">-</span></li>
                                        <li><strong>N° Serie:</strong> <span id="detail_asset_serie" class="font-monospace text-dark">-</span></li>
                                        <li><strong>Ubicación habitual:</strong> <span id="detail_asset_ubication" class="text-dark">-</span></li>
                                        <li><strong>Área de custodia:</strong> <span id="detail_area_name" class="text-dark">-</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded border mb-3">
                            <div class="small text-muted text-uppercase fw-bold mb-2">Trazabilidad y Plazos</div>
                            <div class="row g-2 small">
                                <div class="col-md-4">
                                    <div class="text-muted">Fecha de Entrega:</div>
                                    <div class="fw-bold text-dark" id="detail_loan_date">-</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted">Devolución Estimada:</div>
                                    <div class="fw-bold" id="detail_expected_date">-</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted">Devolución Real:</div>
                                    <div class="fw-bold" id="detail_actual_date">-</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted">Ambiente de Uso:</div>
                                    <div class="fw-bold text-dark" id="detail_destination">-</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted">Condición Inicial:</div>
                                    <div class="fw-bold text-dark" id="detail_initial_cond">-</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted">Condición al Devolver:</div>
                                    <div class="fw-bold text-dark" id="detail_return_cond">-</div>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <div class="text-muted">Autorizado y Entregado por:</div>
                                    <div class="fw-bold text-dark" id="detail_registered_by">-</div>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <div class="text-muted">Recibido en Devolución por:</div>
                                    <div class="fw-bold text-dark" id="detail_received_by">-</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Finalidad o Motivo:</label>
                                <p class="small text-dark p-2 bg-light rounded border mb-0" id="detail_purpose">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Observaciones de Entrega:</label>
                                <p class="small text-dark p-2 bg-light rounded border mb-0" id="detail_observations">-</p>
                            </div>
                            <div class="col-12" id="detail_return_obs_container">
                                <label class="form-label small fw-bold text-muted">Observaciones de Devolución:</label>
                                <p class="small text-dark p-2 bg-light rounded border mb-0" id="detail_return_obs">-</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <a href="#" id="btn_detail_pdf" target="_blank" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i> Papeleta de Préstamo (PDF)
                    </a>
                    <a href="#" id="btn_detail_edit" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit me-1"></i> Editar Registro
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Devolución Rápida -->
    <div class="modal fade" id="modalQuickReturn" tabindex="-1" aria-labelledby="modalQuickReturnLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form id="formQuickReturn">
                    @csrf
                    <input type="hidden" id="return_loan_id" name="loan_id">
                    <div class="modal-header bg-light py-3 border-bottom">
                        <h5 class="modal-title fw-bold text-dark" id="modalQuickReturnLabel">
                            <i class="fas fa-undo-alt text-success me-2"></i>Registrar Devolución de Bien
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info py-2 px-3 small border-0 mb-3">
                            <i class="fas fa-info-circle me-1"></i> Registrando retorno del folio:
                            <strong id="return_modal_code"></strong> (<span id="return_modal_borrower"></span>)
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">
                                Condición Física al Retorno <span class="text-danger">*</span>
                            </label>
                            <select name="return_condition" id="return_condition" class="form-select" required>
                                <option value="B" selected>Bueno (B) - Conforme / Sin daños</option>
                                <option value="R">Regular (R) - Desgaste leve o detalles cosméticos</option>
                                <option value="M">Malo (M) - Averiado / Dañado / Falta pieza</option>
                                <option value="BAJA">Baja - Inoperativo totalmente / Para descarte</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Fecha y Hora de Recepción</label>
                            <input type="datetime-local" name="actual_return_date" id="actual_return_date"
                                class="form-control" value="{{ date('Y-m-d\TH:i') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Observaciones o Novedades de Retorno</label>
                            <textarea name="return_observations" id="return_observations" rows="3" class="form-control"
                                placeholder="Ej: Se recibe conforme con cables y adaptador original..."></textarea>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="update_asset_condition" id="update_asset_condition" value="1" checked>
                            <label class="form-check-label small text-dark" for="update_asset_condition">
                                Actualizar también la condición física del activo en el inventario general
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success btn-sm btn-submit-return">
                            <span class="btn-return-text"><i class="fas fa-check me-1"></i>Confirmar Devolución</span>
                            <span class="btn-return-loading d-none"><i class="fas fa-spinner fa-spin me-1"></i>Procesando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            const table = $('#loanTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('asset_loans.get') }}",
                    data: function(d) {
                        d.area_id = $('#filter_area_id').val();
                        d.filter_borrower_type = $('#filter_borrower_type').val();
                        d.filter_status = $('#filter_status').val();
                        d.filter_search = $('#filter_search').val();
                    }
                },
                columns: [
                    { data: 'codigo_col', name: 'loan_code' },
                    { data: 'asset_col', name: 'asset.descripcion' },
                    { data: 'borrower_col', name: 'borrower_name' },
                    { data: 'dates_col', name: 'expected_return_date' },
                    { data: 'destination_col', name: 'destination' },
                    { data: 'status_badge', name: 'status', className: 'text-center' },
                    { data: 'acciones', name: 'acciones', className: 'text-center', orderable: false, searchable: false }
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                },
                order: [[0, 'desc']],
                pageLength: 25
            });

            // Filtros
            $('#btn_filter').click(function() {
                table.draw();
            });

            $('#filter_area_id, #filter_borrower_type, #filter_status').change(function() {
                table.draw();
            });

            $('#filter_search').on('keyup', function(e) {
                if (e.key === 'Enter') table.draw();
            });

            $('#btn_reset').click(function() {
                $('#filter_area_id').val('');
                $('#filter_borrower_type').val('');
                $('#filter_status').val('');
                $('#filter_search').val('');
                table.draw();
            });

            // Abrir Detalle Rápido
            $(document).on('click', '.btn-quick-detail', function() {
                const id = $(this).data('id');
                $('#modalLoanDetail').modal('show');
                $('#detail_loading').removeClass('d-none');
                $('#detail_content').addClass('d-none');

                $.ajax({
                    url: "{{ url('asset-loans/quick-detail') }}/" + id,
                    type: 'GET',
                    success: function(resp) {
                        if (resp.success) {
                            const l = resp.loan;
                            $('#detail_code').text(l.loan_code);
                            $('#detail_borrower_name').text(l.borrower_name);
                            $('#detail_borrower_badge').html(l.borrower_type_badge + ' ' + l.status_badge);
                            $('#detail_borrower_document').text(l.borrower_document);
                            $('#detail_borrower_code').text(l.borrower_code);
                            $('#detail_borrower_career').text(l.borrower_career_or_area);
                            $('#detail_borrower_phone').text(l.borrower_phone);
                            $('#detail_borrower_email').text(l.borrower_email);

                            $('#detail_asset_desc').text(l.asset.descripcion);
                            $('#detail_asset_code').text(l.asset.codigo);
                            $('#detail_asset_cond').text(l.asset.condicion);
                            $('#detail_asset_brand').text((l.asset.marca || 'SIN MARCA') + ' / ' + (l.asset.modelo || 'SIN MODELO'));
                            $('#detail_asset_serie').text(l.asset.serie || 'SIN SERIE');
                            $('#detail_asset_ubication').text(l.asset.ubicacion || '-');
                            $('#detail_area_name').text(l.area_name || '-');

                            $('#detail_loan_date').text(l.loan_date);
                            $('#detail_expected_date').text(l.expected_return_date);
                            if (l.is_overdue) {
                                $('#detail_expected_date').addClass('text-danger');
                            } else {
                                $('#detail_expected_date').removeClass('text-danger');
                            }
                            $('#detail_actual_date').text(l.actual_return_date);
                            $('#detail_destination').text(l.destination);
                            $('#detail_initial_cond').text(l.initial_condition);
                            $('#detail_return_cond').text(l.return_condition);
                            $('#detail_registered_by').text(l.registered_by);
                            $('#detail_received_by').text(l.received_by);

                            $('#detail_purpose').text(l.purpose);
                            $('#detail_observations').text(l.observations);
                            $('#detail_return_obs').text(l.return_observations);

                            $('#btn_detail_pdf').attr('href', l.pdf_url);
                            $('#btn_detail_edit').attr('href', l.edit_url);

                            $('#detail_loading').addClass('d-none');
                            $('#detail_content').removeClass('d-none');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo cargar la información del préstamo.', 'error');
                        $('#modalLoanDetail').modal('hide');
                    }
                });
            });

            // Abrir Modal Devolución Rápida
            $(document).on('click', '.btn-quick-return', function() {
                const id = $(this).data('id');
                const code = $(this).data('code');
                const borrower = $(this).data('borrower');

                $('#return_loan_id').val(id);
                $('#return_modal_code').text(code);
                $('#return_modal_borrower').text(borrower);
                $('#return_observations').val('');
                $('#return_condition').val('B');
                $('#modalQuickReturn').modal('show');
            });

            // Enviar Devolución Rápida
            $('#formQuickReturn').on('submit', function(e) {
                e.preventDefault();
                const id = $('#return_loan_id').val();
                const formData = $(this).serialize();

                $('.btn-submit-return').prop('disabled', true);
                $('.btn-return-text').addClass('d-none');
                $('.btn-return-loading').removeClass('d-none');

                $.ajax({
                    url: "{{ url('asset-loans') }}/" + id + "/return",
                    type: 'POST',
                    data: formData,
                    success: function(resp) {
                        $('.btn-submit-return').prop('disabled', false);
                        $('.btn-return-text').removeClass('d-none');
                        $('.btn-return-loading').addClass('d-none');

                        if (resp.status) {
                            $('#modalQuickReturn').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: '¡Devolución Registrada!',
                                text: resp.message,
                                confirmButtonColor: '#0d6efd'
                            }).then(() => {
                                table.draw();
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Atención', resp.message || 'Error al registrar la devolución.', 'warning');
                        }
                    },
                    error: function(xhr) {
                        $('.btn-submit-return').prop('disabled', false);
                        $('.btn-return-text').removeClass('d-none');
                        $('.btn-return-loading').addClass('d-none');
                        const msg = xhr.responseJSON?.message || 'Error al procesar la devolución.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            });

            // Eliminar Registro
            $(document).on('click', '.btn-delete-loan', function() {
                const id = $(this).data('id');

                Swal.fire({
                    title: '¿Eliminar registro de préstamo?',
                    text: 'Esta acción cancelará el registro del préstamo.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('asset_loans.delete') }}",
                            type: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                id: id
                            },
                            success: function(resp) {
                                if (resp.status) {
                                    Swal.fire('Eliminado', resp.message, 'success');
                                    table.draw();
                                } else {
                                    Swal.fire('Error', resp.message || 'No se pudo eliminar.', 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'No se pudo completar la solicitud.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
