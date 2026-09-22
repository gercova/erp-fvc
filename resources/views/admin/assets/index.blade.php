@extends('admin.layout')
@section('title', 'Inventario de Bienes Patrimoniales')

@section('styles')
    <style>
        .asset-card,
        .asset-card .card-body,
        .asset-card .table-responsive,
        .asset-card .dropdown,
        .asset-card td,
        .asset-card th {
            overflow: visible !important;
        }

        .asset-card .dropdown-menu {
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
                            <div class="page-header-icon"><i data-feather="archive"></i></div>
                            Inventario de Bienes Patrimoniales
                        </h1>
                    </div>
                    <div class="col-auto">
                        @can('assets.create')
                            <a href="{{ route('inventory.create', ['area_id' => $selectedAreaId]) }}"
                                class="btn btn-success waves-effect waves-light mb-3 me-2">
                                <i class="ri-add-circle-line align-middle me-1"></i>
                                <span class="d-none d-sm-inline">Nuevo Bien</span>
                            </a>
                            <button type="button" class="btn btn-info waves-effect waves-light btn-upload-excel mb-3 me-2"
                                data-bs-toggle="modal" data-bs-target="#modalUploadAssets">
                                <i class="ri-upload-2-line align-middle me-1"></i>
                                <span class="d-none d-sm-inline">Cargar Excel</span>
                            </button>
                        @endcan
                        @can('assets.export')
                            <a href="{{ route('inventory.pdf', ['area_id' => $selectedAreaId]) }}" target="_blank"
                                class="btn btn-danger waves-effect waves-light mb-3 me-2" title="Descargar Formato Oficial PDF">
                                <i class="ri-file-pdf-line align-middle me-1"></i>
                                <span class="d-none d-sm-inline">Formato PDF</span>
                            </a>
                        @endcan
                        @can('assets.qr')
                            <a href="{{ route('inventory.qr_labels', ['area_id' => $selectedAreaId]) }}" target="_blank"
                                class="btn btn-dark waves-effect waves-light mb-3 me-2" title="Imprimir Rótulos QR">
                                <i class="ri-qr-code-line align-middle me-1"></i>
                                <span class="d-none d-sm-inline">Rótulos QR</span>
                            </a>
                        @endcan
                        @can('assets.approve')
                            <button type="button" class="btn btn-primary waves-effect waves-light mb-3"
                                data-bs-toggle="modal" data-bs-target="#modalCreateInventory">
                                <i class="ri-file-shield-2-line align-middle me-1"></i>
                                <span class="d-none d-sm-inline">Iniciar Acta</span>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-xl px-4 pb-5">
        <!-- Tarjetas de Resumen en Estilo Oficial del Template (Sin Degradados) -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="card custom-card pro-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="small text-muted text-uppercase fw-semibold">Total Bienes</div>
                                <div class="h3 mb-0 text-dark">{{ number_format($totalCount) }}</div>
                                <div class="small text-muted mt-1">Activos registrados</div>
                            </div>
                            <div class="rounded-3 p-3" style="background: rgba(0, 97, 242, 0.1); color: var(--bs-primary);">
                                <i class="ri-archive-line fs-4"></i>
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
                                <div class="small text-muted text-uppercase fw-semibold">Valor Declarado</div>
                                <div class="h3 mb-0 text-success">S/ {{ number_format($totalCost, 2) }}</div>
                                <div class="small text-muted mt-1">Costo total acumulado</div>
                            </div>
                            <div class="rounded-3 p-3" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                                <i class="ri-money-dollar-circle-line fs-4"></i>
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
                                <div class="small text-muted text-uppercase fw-semibold">Condición Física</div>
                                <div class="d-flex gap-1 mt-1">
                                    <span class="badge bg-success" title="Bueno">{{ $goodCount }} B</span>
                                    <span class="badge bg-warning text-dark" title="Regular">{{ $fairCount }} R</span>
                                    <span class="badge bg-danger" title="Malo">{{ $poorCount }} M</span>
                                    <span class="badge bg-secondary" title="Baja">{{ $writtenOffCount }} Baja</span>
                                </div>
                                <div class="small text-muted mt-1">{{ $goodCount + $fairCount }} en operación</div>
                            </div>
                            <div class="rounded-3 p-3" style="background: rgba(23, 162, 184, 0.1); color: #17a2b8;">
                                <i class="ri-heart-pulse-line fs-4"></i>
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
                                <div class="small text-muted text-uppercase fw-semibold">Auditoría / Físico</div>
                                <div class="h3 mb-0 text-primary">{{ $reconciledPercent }}%</div>
                                <div class="small text-muted mt-1">{{ $reconciledCount }} de {{ $totalCount }} verificados</div>
                            </div>
                            <div class="rounded-3 p-3" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                                <i class="ri-checkbox-circle-line fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selector de Departamento y Filtros -->
        <div class="card custom-card pro-card mb-4">
            <div class="card-body">
                <div class="row align-items-center g-3 pb-3 border-bottom mb-3">
                    <div class="col-lg-5 col-md-6">
                        <label for="area_selector" class="form-label small fw-bold text-uppercase text-muted mb-1">
                            <i class="ri-building-line text-primary me-1"></i> Departamento / Área Institucional
                        </label>
                        <select id="area_selector" class="form-select fw-semibold text-dark">
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ $selectedAreaId == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }} ({{ $area->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-7 col-md-6">
                        @if ($selectedArea)
                            <div class="p-2 rounded bg-light border d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <div class="small fw-bold text-dark mb-0">
                                        <i class="ri-user-star-line text-muted me-1"></i> Responsable:
                                        <span class="text-primary">{{ $selectedArea->head?->nombres ?? 'Sin asignar' }}</span>
                                    </div>
                                    <div class="small text-muted">
                                        Código: <span class="badge bg-secondary font-monospace">{{ $selectedArea->code }}</span>
                                        &bull; Tipo: <span class="badge bg-info text-white">{{ ucfirst(str_replace('_', ' ', $selectedArea->type)) }}</span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success text-white px-2 py-1">
                                        <i class="ri-check-line me-1"></i> Activo
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Filtros rápidos de búsqueda -->
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">Buscar por Descripción, Código o Serie</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="ri-search-line text-muted"></i></span>
                            <input type="text" id="filter_search" class="form-control border-start-0"
                                placeholder="Ej: Laptop, Mesa, 07-22, 74648119...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted">Condición</label>
                        <select id="filter_condicion" class="form-select form-select-sm">
                            <option value="">-- Todas --</option>
                            <option value="B">Bueno (B)</option>
                            <option value="R">Regular (R)</option>
                            <option value="M">Malo (M)</option>
                            <option value="BAJA">Baja (W)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted">Tipo Adquisición</label>
                        <select id="filter_tipo_adquisicion" class="form-select form-select-sm">
                            <option value="">-- Todos --</option>
                            <option value="C">Compra (C)</option>
                            <option value="D">Donación (D)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted">Auditoría Física</label>
                        <select id="filter_reconciled" class="form-select form-select-sm">
                            <option value="">-- Todos --</option>
                            <option value="1">Auditados</option>
                            <option value="0">Pendientes</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="button" id="btn_filter" class="btn btn-sm btn-primary w-100">
                            <i class="ri-filter-2-line me-1"></i> Filtrar
                        </button>
                        <button type="button" id="btn_reset" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">
                            <i class="ri-refresh-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla Optimizada con las Columnas Clave (8 Columnas Esenciales) -->
        <div class="card custom-card pro-card asset-card mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark d-flex align-items-center">
                    <i class="ri-table-line text-primary me-2"></i> Formato Único Institucional de Inventario de Bienes Muebles
                </h6>
                <div class="text-muted small">
                    Departamento: <strong>{{ $selectedArea?->name ?? 'Área' }}</strong>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle w-100" id="assetTable">
                        <thead class="table-light text-uppercase fs-xs">
                            <tr>
                                <th style="width: 55px;" class="text-center">N° Ord</th>
                                <th style="width: 135px;">Código</th>
                                <th>Descripción del Bien</th>
                                <th style="width: 170px;">Marca / Serie</th>
                                <th style="width: 90px;" class="text-center">Condición</th>
                                <th style="width: 140px;">Ubicación</th>
                                <th style="width: 95px;" class="text-center">Auditoría</th>
                                <th style="width: 85px;" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.88rem;"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Carga Masiva desde Excel (Formato Oficial Institucional) -->
    <div class="modal fade" id="modalUploadAssets" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalUploadAssetsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="form_excel_assets" class="modal-content" onsubmit="event.preventDefault()" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalUploadAssetsLabel">
                        <i class="ri-file-excel-2-line me-2 text-success"></i>Importar Inventario desde Archivo Excel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="p-3 bg-light border rounded">
                                <h6 class="fw-semibold text-dark mb-1">Estructura del Formato Institucional</h6>
                                <p class="small text-muted mb-2">
                                    El sistema procesa automáticamente el archivo oficial de inventario de bienes muebles del IESTP Francisco Vigo Caballero (reconociendo las columnas <code>Nº ORD</code>, <code>CODIGO PRODUCTO</code>, <code>CODIGO</code>, <code>DESCRIPCIÓN</code>, <code>MARCA</code>, <code>MODELO</code>, <code>SERIE</code>, subcolumnas de condición <code>B/R/M/BAJA</code> y tipo de adquisición <code>C/D</code>).
                                </p>
                                <div class="d-flex gap-2">
                                    <a id="btn_download_template" href="{{ route('inventory.download_template', ['area_id' => $selectedAreaId]) }}" class="btn btn-sm btn-dark">
                                        <i class="ri-download-2-line me-1"></i> Descargar Plantilla Oficial Excel (.xlsx)
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="excel_area_id" class="form-label fw-medium small">Área o Departamento Destino</label>
                            <select name="area_id" id="excel_area_id" class="form-select form-select-sm" required>
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}" {{ $selectedAreaId == $area->id ? 'selected' : '' }}>
                                        {{ $area->name }} ({{ $area->code }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Los bienes importados se asignarán a este departamento.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Tratamiento de Duplicados</label>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="overwrite" id="excel_overwrite" value="1">
                                <label class="form-check-label small" for="excel_overwrite">
                                    Actualizar datos si el código patrimonial o SBN ya existe
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="excel_assets_file" class="form-label fw-medium small">Seleccionar archivo Excel (.xlsx / .xls)</label>
                            <input type="file" id="excel_assets_file" class="form-control" name="excel" accept=".xlsx, .xls" required>
                            <small class="text-muted">Formatos admitidos: Microsoft Excel (.xlsx, .xls)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success btn-upload-asset-submit">
                        <span class="text-upload-asset"><i class="ri-upload-cloud-line me-1"></i> Iniciar Importación</span>
                        <span class="spinner-border spinner-border-sm me-1 d-none text-uploading-asset" role="status" aria-hidden="true"></span>
                        <span class="text-uploading-asset d-none">Procesando archivo...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Vista Rápida de Detalle Completo del Activo -->
    <div class="modal fade" id="modalAssetDetail" tabindex="-1" aria-labelledby="modalAssetDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalAssetDetailLabel">
                        <i class="ri-file-info-line text-primary me-2"></i>Ficha Técnica Rápida del Bien
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-primary font-monospace" id="detail_orden">ORD #01</span>
                                <span class="badge bg-secondary font-monospace" id="detail_codigo">CODIGO</span>
                                <span id="detail_condicion_badge"></span>
                            </div>
                            <h5 class="fw-bold text-dark mb-1" id="detail_descripcion">DESCRIPCIÓN</h5>
                            <p class="text-muted small mb-3" id="detail_area_name">Departamento</p>

                            <div class="row g-2 small border rounded p-3 bg-light">
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Código Producto SBN:</span>
                                    <strong id="detail_codigo_producto">-</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Marca / Modelo:</span>
                                    <strong id="detail_marca_modelo">-</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Número de Serie:</span>
                                    <strong class="font-monospace" id="detail_serie">-</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Costo Declarado:</span>
                                    <strong class="text-success" id="detail_costo">S/ 0.00</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Tipo y Año de Adquisición:</span>
                                    <span id="detail_adquisicion">-</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Ubicación / Custodio:</span>
                                    <span id="detail_ubicacion">-</span>
                                </div>
                                <div class="col-12 mt-2 pt-2 border-top">
                                    <span class="text-muted d-block">Observaciones:</span>
                                    <span id="detail_observaciones" class="fst-italic text-dark">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="p-3 bg-light border rounded mb-3" id="detail_photo_container">
                                <!-- Foto o placeholder -->
                            </div>
                            <div class="d-grid gap-2">
                                <a id="detail_full_link" href="#" class="btn btn-sm btn-primary">
                                    <i class="ri-external-link-line me-1"></i> Ficha Completa
                                </a>
                                <a id="detail_edit_link" href="#" class="btn btn-sm btn-outline-warning text-dark">
                                    <i class="ri-edit-line me-1"></i> Editar Bien
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Vista Rápida de Código QR -->
    <div class="modal fade" id="modalQrCode" tabindex="-1" aria-labelledby="modalQrCodeLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content text-center border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold" id="modalQrCodeLabel">Código QR del Activo</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <div id="qr_preview_container" class="p-3 bg-light rounded d-flex justify-content-center mb-2"></div>
                    <div id="qr_asset_code" class="fw-bold text-primary font-monospace fs-6"></div>
                    <div id="qr_asset_desc" class="small text-muted text-truncate px-2 mb-3"></div>
                    <div class="d-grid gap-2">
                        <a id="btn_print_single_qr" href="#" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-print me-1"></i> Imprimir Etiqueta
                        </a>
                        <a id="btn_open_verify_url" href="#" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-external-link-alt me-1"></i> Abrir Ficha Pública
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Iniciar Acta Oficial de Inventario con las 5 Firmas -->
    <div class="modal fade" id="modalCreateInventory" tabindex="-1" aria-labelledby="modalCreateInventoryLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('asset-inventories.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="area_id" value="{{ $selectedAreaId }}">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold" id="modalCreateInventoryLabel">
                            <i class="ri-file-shield-2-line me-2"></i> Iniciar Acta de Inventario Oficial
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small py-2">
                            <i class="ri-information-line me-1"></i>
                            Se generará el acta formal para <strong>{{ $selectedArea?->name }}</strong> y se iniciará la cadena de <strong>5 firmas digitales</strong> oficiales.
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Título del Inventario</label>
                            <input type="text" name="titulo" class="form-control" required
                                value="INVENTARIO GENERAL DEL ÁREA {{ strtoupper($selectedArea?->name ?? 'INSTITUCIONAL') }}">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Período / Año</label>
                                <input type="text" name="periodo" class="form-control" required value="{{ date('Y') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Fecha del Inventario</label>
                                <input type="date" name="fecha_inventario" class="form-control" required value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Responsable del Área</label>
                            <input type="text" name="responsable" class="form-control" required
                                value="{{ $selectedArea?->head?->nombres ?? 'RESPONSABLE DE AREA' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Realizado por (Técnico / Encargado)</label>
                            <input type="text" name="realizado_por" class="form-control" required
                                value="Tec. OSORIO SANCHEZ, CECILIA ISABEL">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Observaciones Generales</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas sobre el estado general del inventario..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="ri-send-plane-line me-1"></i> Crear y Enviar a Firmas
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
            // Inicializar DataTables con las 8 Columnas Clave
            const table = $('#assetTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('inventory.get') }}",
                    data: function(d) {
                        d.area_id = $('#area_selector').val();
                        d.filter_condicion = $('#filter_condicion').val();
                        d.filter_tipo_adquisicion = $('#filter_tipo_adquisicion').val();
                        d.filter_reconciled = $('#filter_reconciled').val();
                        d.filter_search = $('#filter_search').val();
                    }
                },
                columns: [
                    { data: 'orden_fmt', name: 'orden', className: 'text-center' },
                    { data: 'codigo_col', name: 'codigo' },
                    { data: 'descripcion_col', name: 'descripcion' },
                    { data: 'tecnico_col', name: 'marca' },
                    { data: 'condicion_badge', name: 'condicion', className: 'text-center' },
                    { data: 'ubicacion_col', name: 'ubicacion' },
                    { data: 'reconciled_badge', name: 'is_reconciled', className: 'text-center', orderable: false, searchable: false },
                    { data: 'acciones', name: 'acciones', className: 'text-center', orderable: false, searchable: false }
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                },
                order: [[0, 'asc']],
                pageLength: 25
            });

            // Cambio de departamento desde el dropdown
            $('#area_selector').change(function() {
                const newAreaId = $(this).val();
                window.location.href = "{{ route('inventory.index') }}?area_id=" + newAreaId;
            });

            $('#btn_filter').click(function() {
                table.draw();
            });

            $('#filter_search').on('keyup', function(e) {
                if (e.key === 'Enter') table.draw();
            });

            $('#filter_condicion, #filter_tipo_adquisicion, #filter_reconciled').on('change', function() {
                table.draw();
            });

            $('#btn_reset').click(function() {
                $('#filter_condicion').val('');
                $('#filter_tipo_adquisicion').val('');
                $('#filter_reconciled').val('');
                $('#filter_search').val('');
                table.draw();
            });

            // Actualizar URL de descarga de plantilla según el área seleccionada
            $('#excel_area_id').change(function() {
                const areaId = $(this).val();
                $('#btn_download_template').attr('href', "{{ route('inventory.download_template') }}?area_id=" + areaId);
            });

            // Importación Masiva Excel con AJAX
            $('#form_excel_assets').on('submit', function(e) {
                e.preventDefault();

                const form = document.getElementById('form_excel_assets');
                const formData = new FormData(form);

                $.ajax({
                    url: "{{ route('inventory.upload_excel') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $('.btn-upload-asset-submit').prop('disabled', true);
                        $('.text-uploading-asset').removeClass('d-none');
                        $('.text-upload-asset').addClass('d-none');
                    },
                    success: function(resp) {
                        $('.btn-upload-asset-submit').prop('disabled', false);
                        $('.text-uploading-asset').addClass('d-none');
                        $('.text-upload-asset').removeClass('d-none');

                        if (!resp.status) {
                            Swal.fire('Observación', resp.msg, resp.type || 'warning');
                            return;
                        }

                        $('#modalUploadAssets').modal('hide');
                        form.reset();

                        Swal.fire({
                            title: '¡Importación Exitosa!',
                            text: resp.msg,
                            icon: 'success',
                            confirmButtonColor: '#0061f2'
                        }).then(() => {
                            table.draw();
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        $('.btn-upload-asset-submit').prop('disabled', false);
                        $('.text-uploading-asset').addClass('d-none');
                        $('.text-upload-asset').removeClass('d-none');

                        const errorMsg = xhr.responseJSON?.msg || xhr.responseJSON?.message || 'Error al procesar el archivo Excel.';
                        Swal.fire('Error', errorMsg, 'error');
                    }
                });
            });

            // Vista Rápida de Detalle
            $(document).on('click', '.btn-quick-detail', function() {
                const id = $(this).data('id');
                $.ajax({
                    url: "{{ url('/inventory/quick-detail') }}/" + id,
                    type: 'GET',
                    success: function(resp) {
                        if (resp.success) {
                            const a = resp.asset;
                            $('#detail_orden').text('ORD #' + a.orden);
                            $('#detail_codigo').text(a.codigo);
                            $('#detail_condicion_badge').html(a.condicion_badge);
                            $('#detail_descripcion').text(a.descripcion);
                            $('#detail_area_name').text('Departamento: ' + a.area_name);
                            $('#detail_codigo_producto').text(a.codigo_producto);
                            $('#detail_marca_modelo').text(a.marca + ' / ' + a.modelo);
                            $('#detail_serie').text(a.serie);
                            $('#detail_costo').text('S/ ' + a.costo);
                            $('#detail_adquisicion').text(a.tipo_adquisicion + ' (' + a.fecha_adquisicion + ')');
                            $('#detail_ubicacion').text(a.ubicacion + ' / ' + a.custodio);
                            $('#detail_observaciones').text(a.observaciones);

                            if (a.foto_url) {
                                $('#detail_photo_container').html('<img src="' + a.foto_url + '" class="img-fluid rounded border" style="max-height: 180px; object-fit: cover;">');
                            } else {
                                $('#detail_photo_container').html('<img src="' + a.qr_url + '" style="width: 130px; height: 130px;"><div class="small text-muted mt-1">Sin foto adjunta</div>');
                            }

                            $('#detail_full_link').attr('href', a.show_url);
                            $('#detail_edit_link').attr('href', a.edit_url);

                            const modal = new bootstrap.Modal(document.getElementById('modalAssetDetail'));
                            modal.show();
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo cargar los detalles del activo.', 'error');
                    }
                });
            });

            // Abrir Modal de QR
            $(document).on('click', '.btn-show-qr', function() {
                const assetId = $(this).data('id');
                const uuid = $(this).data('uuid');
                const codigo = $(this).data('codigo');
                const desc = $(this).data('desc');

                $('#qr_asset_code').text(codigo);
                $('#qr_asset_desc').text(desc);

                const qrSvgUrl = "{{ url('/inventory/qr-svg') }}/" + uuid;
                $('#qr_preview_container').html('<img src="' + qrSvgUrl + '" alt="QR Code" style="width: 140px; height: 140px;" />');

                $('#btn_print_single_qr').attr('href', "{{ route('inventory.qr_labels') }}?asset_id=" + assetId);
                $('#btn_open_verify_url').attr('href', "{{ url('/inventory/verify') }}/" + uuid);

                const modal = new bootstrap.Modal(document.getElementById('modalQrCode'));
                modal.show();
            });

            // Toggle rápido de conciliación física
            $(document).on('click', '.btn-reconcile-toggle', function() {
                const btn = $(this);
                const assetId = btn.data('id');

                $.ajax({
                    url: "{{ url('/inventory') }}/" + assetId + "/reconcile",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(resp) {
                        if (resp.success) {
                            table.draw(false);
                            const toastEl = document.getElementById('toastSuccess');
                            if (toastEl) {
                                $('#toastSuccess .toast-body').text(resp.message);
                                new bootstrap.Toast(toastEl).show();
                            }
                        }
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON?.message || 'No se pudo actualizar el estado de auditoría.', 'error');
                    }
                });
            });

            // Eliminar o dar de baja un bien
            $(document).on('click', '.btn-delete-asset', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: '¿Dar de baja / eliminar activo?',
                    text: 'El bien patrimonial será retirado del inventario activo del departamento.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, dar de baja',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('inventory.delete') }}",
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                id: id
                            },
                            success: function(resp) {
                                Swal.fire('Completado', resp.message || 'El bien fue dado de baja.', 'success');
                                table.draw(false);
                            },
                            error: function(err) {
                                Swal.fire('Error', err.responseJSON?.message || 'No se pudo eliminar el activo.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
