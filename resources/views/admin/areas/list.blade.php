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
                <div class="col-auto">
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
                    <div class="table-responsive">
                        <table id="table" class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th width="12%">Código</th>
                                    <th>Nombre del Área</th>
                                    <th width="15%" class="text-center">Tipo</th>
                                    <th width="18%">Área Superior</th>
                                    <th width="18%">Responsable / Jefe</th>
                                    <th width="8%" class="text-center">Nivel</th>
                                    <th width="10%" class="text-center">Naturaleza</th>
                                    <th width="10%" class="text-center">Acciones</th>
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
