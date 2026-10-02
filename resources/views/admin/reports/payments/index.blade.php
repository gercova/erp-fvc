@extends('admin.layout')

@section('content')

<header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-xl px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    <h1 class="page-header-title">
                        <div class="page-header-icon"><i data-feather="credit-card"></i></div>
                        Reporte de Ventas por Método de Pago
                    </h1>
                </div>
                <div class="col-12 col-xl-auto mb-3">
                    <button type="button" id="btnExportPdf" class="btn btn-danger btn-sm me-2">
                        <i class="ri-file-pdf-line me-1"></i> Exportar PDF
                    </button>
                    <button type="button" id="btnExportExcel" class="btn btn-success btn-sm">
                        <i class="ri-file-excel-line me-1"></i> Exportar Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container-xl px-4 mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card custom-card">
                <div class="card-body">
                    <form id="formReport">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Fecha inicio</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}">
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">Fecha fin</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ now()->endOfMonth()->toDateString() }}">
                            </div>
                            <div class="col-md-3">
                                <label for="id_almacen" class="form-label">Almacén</label>
                                <select id="id_almacen" name="id_almacen" class="form-select">
                                    <option value="">Todos los almacenes</option>
                                    @if(isset($warehouses))
                                        @foreach ($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}">{{ $warehouse->descripcion }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                            </div>
                        </div>
                    </form>

                    <div class="mt-4">
                        <table class="table table-sm table-striped" id="salesReportTable">
                            <thead>
                                <tr>
                                    <th class="text-center">Método de Pago</th>
                                    <th class="text-center">Cantidad de Transacciones</th>
                                    <th class="text-center">Total Recaudado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Datos dinámicos -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    @include('admin.reports.payments.js')
@endsection
