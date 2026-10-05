@extends('admin.layout')

@section('styles')
    <style>
        .areas-list-card,
        .areas-list-card .card-body,
        .areas-list-card .table-responsive {
            overflow: visible;
        }

        .areas-list-card .dropdown-menu {
            z-index: 1055;
        }
    </style>
@endsection

@section('content')
<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-xl px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title">
                        <div class="page-header-icon"><i data-feather="grid"></i></div>
                        Gestión de Áreas y Unidades
                    </h1>
                </div>
                <div class="col-auto d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-danger waves-effect waves-light mb-3 d-none" id="btn_bulk_delete_header">
                        <i class="ri-delete-bin-line align-middle me-1"></i>
                        <span>Eliminar seleccionados (<span class="selected-count">0</span>)</span>
                    </button>
                    <button class="dt-button create-new btn btn-success waves-effect waves-light btn-create mb-3" tabindex="0">
                        <i class="ri-add-circle-line align-middle"></i>
                        <span class="d-none d-sm-inline">Agregar área</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-xl px-4 mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card custom-card pro-card areas-list-card">
                <div class="card-body">
                    <!-- Bulk actions bar -->
                    <div id="bulk_toolbar" class="alert alert-danger-subtle border border-danger-subtle d-none mb-3 py-2 px-3 align-items-center justify-content-between rounded-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <i class="ri-checkbox-multiple-line fs-5 text-danger"></i>
                            <span class="fw-semibold text-danger">
                                <span class="selected-count fw-bold">0</span> área(s) seleccionada(s)
                                <span id="selection_page_info" class="text-muted fw-normal ms-1 small"></span>
                            </span>
                            <span id="select_all_pages_prompt" class="d-none ms-2">
                                <span class="text-muted small me-2">|</span>
                                <button type="button" class="btn btn-link btn-sm text-primary p-0 fw-semibold text-decoration-underline" id="btn_select_all_pages">
                                    Seleccionar todas las <span class="total-table-count">0</span> áreas de la tabla
                                </button>
                            </span>
                            <span id="all_pages_selected_notice" class="d-none ms-2 badge bg-danger text-white">
                                <i class="ri-check-double-line me-1"></i>Todas las áreas de la tabla seleccionadas
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_clear_selection">
                                <i class="ri-close-line me-1"></i> Cancelar selección
                            </button>
                            <button type="button" class="btn btn-sm btn-danger shadow-sm" id="btn_bulk_delete">
                                <i class="ri-delete-bin-2-line me-1"></i> Eliminar seleccionados (<span class="selected-count">0</span>)
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="table" class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th width="4%" class="text-center">
                                        <div class="form-check d-flex justify-content-center m-0">
                                            <input class="form-check-input" type="checkbox" id="check_all_areas" title="Seleccionar todos">
                                        </div>
                                    </th>
                                    <th width="12%">Código</th>
                                    <th>Nombre del Área</th>
                                    <th width="14%" class="text-center">Tipo</th>
                                    <th width="17%">Área Superior</th>
                                    <th width="17%">Responsable / Jefe</th>
                                    <th width="8%" class="text-center">Nivel</th>
                                    <th width="9%" class="text-center">Naturaleza</th>
                                    <th width="9%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.areas.modals')
</div>
@endsection

@section('scripts')
    @include('admin.areas.js-datatable')
    @include('admin.areas.js-store')
@endsection
