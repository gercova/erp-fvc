@extends('admin.layout')
@section('title', 'Planes y Campañas de Producción')
@section('styles')
    <style>
        .role-segmented-control {
            background-color: #f1f3f5;
            padding: 4px;
            border-radius: 8px;
            display: inline-flex;
            gap: 4px;
        }

        .role-segmented-control .btn-check + .btn {
            border: 0;
            border-radius: 6px;
            color: #495057;
            font-weight: 500;
            padding: 6px 14px;
            background: transparent;
            transition: all 0.15s ease;
        }

        .role-segmented-control .btn-check:checked + .btn {
            background-color: #ffffff;
            color: #0d6efd;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .card-summary {
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 8px;
            transition: transform 0.15s ease;
        }

        .card-summary:hover {
            transform: translateY(-2px);
        }
    </style>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Producción</li>
                    <li class="breadcrumb-item active" aria-current="page">Planes y Campañas</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-clipboard-list me-2 text-primary"></i>Planes y Campañas de Producción
            </h1>
            <p class="text-muted small mb-0">Planificación por actividad productiva, temporada, metas y control de fases</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.profitability.index') }}" class="btn btn-outline-success">
                <i class="fas fa-chart-line me-1"></i> Ver Rentabilidad
            </a>
            <a href="{{ route('production.plans.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Nueva Campaña
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card card-summary bg-white shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Campañas</div>
                        <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($totalPlans) }}</div>
                        <small class="text-muted">Histórico institucional</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(0, 97, 242, 0.1); color: var(--bs-primary);">
                        <i class="fas fa-layer-group fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card card-summary bg-white shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">En Ejecución</div>
                        <div class="h3 mb-0 fw-bold text-primary mt-1">{{ number_format($inProgressCount) }}</div>
                        <small class="text-primary">Fases activas en campo</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                        <i class="fas fa-play-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card card-summary bg-white shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Planificadas</div>
                        <div class="h3 mb-0 fw-bold text-secondary mt-1">{{ number_format($plannedCount) }}</div>
                        <small class="text-muted">Por iniciar labores</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(108, 117, 125, 0.1); color: #6c757d;">
                        <i class="fas fa-clock fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card card-summary bg-white shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Cerradas / Liquidadas</div>
                        <div class="h3 mb-0 fw-bold text-success mt-1">{{ number_format($closedCount) }}</div>
                        <small class="text-success">Cosechas concluidas</small>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="fas fa-check-double fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Main Table Card -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label small fw-bold text-muted mb-0 me-1">Actividad:</label>
                <select id="filter_activity" class="form-select form-select-sm" style="min-width: 220px;">
                    <option value="">-- Todas las Actividades --</option>
                    @foreach($activities as $act)
                        <option value="{{ $act->id }}" {{ $selectedActivityId == $act->id ? 'selected' : '' }}>
                            {{ $act->name }} ({{ $act->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Segmented control for status -->
            <div class="role-segmented-control">
                <input type="radio" class="btn-check filter-status" name="filter_status" id="st_all" value="" checked>
                <label class="btn" for="st_all">Todos</label>

                <input type="radio" class="btn-check filter-status" name="filter_status" id="st_progress" value="in_progress">
                <label class="btn" for="st_progress">En Ejecución</label>

                <input type="radio" class="btn-check filter-status" name="filter_status" id="st_planned" value="planned">
                <label class="btn" for="st_planned">Planificadas</label>

                <input type="radio" class="btn-check filter-status" name="filter_status" id="st_closed" value="closed">
                <label class="btn" for="st_closed">Cerradas</label>
            </div>

            <div class="input-group input-group-sm" style="width: 240px;">
                <input type="text" id="filter_search" class="form-control" placeholder="Buscar campaña...">
                <button class="btn btn-outline-secondary" type="button" id="btn_search"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="plans_table" style="width: 100%;">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th>Código</th>
                            <th>Actividad</th>
                            <th>Campaña / Meta</th>
                            <th>Período</th>
                            <th>Meta Produc.</th>
                            <th>Fases</th>
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
    var table = $('#plans_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('production.plans.get') }}",
            data: function(d) {
                d.activity_id = $('#filter_activity').val();
                d.status = $('input[name="filter_status"]:checked').val();
                d.search_term = $('#filter_search').val();
            }
        },
        columns: [
            { data: 'code_col', name: 'campaign_code' },
            { data: 'activity_col', name: 'activity.name' },
            { data: 'name_col', name: 'name' },
            { data: 'period_col', name: 'start_date' },
            { data: 'target_col', name: 'target_quantity' },
            { data: 'batches_count', name: 'batches_count', orderable: false, searchable: false },
            { data: 'status_col', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        },
        order: [[0, 'desc']],
        pageLength: 15
    });

    $('#filter_activity').on('change', function() {
        table.ajax.reload();
    });

    $('.filter-status').on('change', function() {
        table.ajax.reload();
    });

    $('#filter_search').on('keyup', function(e) {
        if (e.keyCode === 13) {
            table.ajax.reload();
        }
    });

    $('#btn_search').on('click', function() {
        table.ajax.reload();
    });

    // Delete Plan
    $(document).on('click', '.btn-delete-plan', function() {
        var id = $(this).data('id');
        if (!confirm('¿Está seguro de eliminar este plan de producción y sus fases asociadas?')) {
            return;
        }

        $.ajax({
            url: "{{ route('production.plans.delete') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: id
            },
            success: function(res) {
                table.ajax.reload();
                alert(res.message);
            },
            error: function(err) {
                alert('No se pudo eliminar el plan.');
            }
        });
    });
});
</script>
@endsection
