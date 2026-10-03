@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fas fa-tools text-primary me-2"></i>Contrataciones y Órdenes de Servicios Tecnológicos
            </h1>
            <p class="text-muted small mb-0">
                Gestión operativa de servicios técnicos, capacitación, asesoría en campo, entregables y bolsa de horas.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('services.engagements.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus me-1"></i> Nueva Orden de Servicio
            </a>
            <a href="{{ route('agreements.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-handshake me-1"></i> Convenios
            </a>
        </div>
    </div>

    <!-- KPIs Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Contrataciones</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $totalEngagements }}</div>
                            <small class="text-muted">{{ $inProgressCount }} en ejecución &bull; {{ $completedCount }} concluidas</small>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Monto Total Contratado</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">S/ {{ number_format($totalAmount, 2) }}</div>
                            <small class="text-muted">Servicios directos y bajo convenio</small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                            <i class="fas fa-coins fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Bolsa de Horas Consumida</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $totalConsumedHours }} / {{ $totalContractedHours }} hrs</div>
                            @php
                                $poolPercent = $totalContractedHours > 0 ? min(100, round(($totalConsumedHours / $totalContractedHours) * 100, 1)) : 0;
                            @endphp
                            <div class="progress mt-2" style="height: 5px; width: 140px;">
                                <div class="progress-bar bg-info" style="width: {{ $poolPercent }}%;"></div>
                            </div>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                            <i class="fas fa-hourglass-half fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Alertas: Saldo Bajo</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $lowBalanceCount }}</div>
                            <small class="text-muted">Órdenes con bolsa próxima a agotarse</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & DataTables Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 mb-3 align-items-center">
                <div class="col-md-3">
                    <input type="text" id="filter_search" class="form-control form-control-sm" placeholder="Buscar código, cliente, descripción...">
                </div>
                <div class="col-md-3">
                    <select id="filter_service" class="form-select form-select-sm">
                        <option value="">-- Todos los Servicios --</option>
                        @foreach($services as $svc)
                            <option value="{{ $svc->id }}">{{ $svc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filter_agreement" class="form-select form-select-sm">
                        <option value="">-- Todos los Convenios / Directos --</option>
                        @foreach($agreements as $ag)
                            <option value="{{ $ag->id }}">{{ $ag->code }} - {{ Str::limit($ag->name, 35) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">-- Todos los Estados --</option>
                        <option value="DRAFT">Borrador</option>
                        <option value="IN_PROGRESS">En Ejecución</option>
                        <option value="COMPLETED">Concluido</option>
                        <option value="CANCELLED">Cancelado</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" id="btn-reset-filters" class="btn btn-outline-secondary btn-sm w-100" title="Limpiar Filtros">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="services-table" style="width:100%">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th>Código</th>
                            <th>Servicio / Marco</th>
                            <th>Cliente</th>
                            <th>Modalidad & Plazo</th>
                            <th>Bolsa de Horas</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const table = $('#services-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('services.engagements.data') }}",
            data: function (d) {
                d.filter_search    = $('#filter_search').val();
                d.filter_service   = $('#filter_service').val();
                d.filter_agreement = $('#filter_agreement').val();
                d.filter_status    = $('#filter_status').val();
            }
        },
        columns: [
            { data: 'codigo', name: 'code' },
            { data: 'servicio', name: 'technological_service_id' },
            { data: 'cliente', name: 'client_id' },
            { data: 'modalidad_plazo', name: 'start_date' },
            { data: 'bolsa_horas', name: 'contracted_hours', orderable: false },
            { data: 'monto', name: 'total_amount' },
            { data: 'estado', name: 'status' },
            { data: 'acciones', name: 'acciones', orderable: false, searchable: false, className: 'text-end' }
        ],
        order: [[0, 'desc']],
        pageLength: 15,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
    });

    $('#filter_search, #filter_service, #filter_agreement, #filter_status').on('change keyup', function() {
        table.draw();
    });

    $('#btn-reset-filters').on('click', function() {
        $('#filter_search').val('');
        $('#filter_service').val('');
        $('#filter_agreement').val('');
        $('#filter_status').val('');
        table.draw();
    });
});
</script>
@endsection
