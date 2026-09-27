@extends('admin.layout')
@section('title', 'Detalle de Parcela: ' . $plot->name)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('agrolivestock.plots.index') }}">Parcelas</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $plot->name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-900 fw-bold">
                <i class="fas fa-mountain me-2 text-success"></i>{{ $plot->name }}
            </h1>
            <p class="text-muted small mb-0">Código: <span class="font-monospace fw-bold text-dark">{{ $plot->code }}</span> | Fundo: <span class="fw-semibold">{{ $plot->activity->name ?? 'General' }}</span></p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPlantation">
                <i class="fas fa-seedling me-1"></i> Instalar Cultivo / Plantación
            </button>
            <a href="{{ route('agrolivestock.plots.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver a Parcelas
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Cards Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Superficie Total</div>
                <div class="h3 mb-0 fw-bold text-dark mt-1">{{ number_format($plot->area_hectares, 2) }} ha</div>
                <small class="text-muted">Topografía: {{ $plot->topography ?? 'No especificada' }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Cultivos Instalados</div>
                <div class="h3 mb-0 fw-bold text-primary mt-1">{{ $plantationsCount }}</div>
                <small class="text-muted">Especies agrícolas o forestales</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Total Cosechado</div>
                <div class="h3 mb-0 fw-bold text-success mt-1">{{ number_format($totalHarvestedTon, 2) }} TM</div>
                <small class="text-muted">{{ $plot->harvests->count() }} eventos de cosecha</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Estado de la Parcela</div>
                <div class="mt-2">{!! $plot->status_badge !!}</div>
                <small class="text-muted d-block mt-1">Suelo: {{ $plot->soil_type ?? 'Aluvial' }}</small>
            </div>
        </div>
    </div>

    <!-- Cultivos y Plantaciones Instaladas -->
    <div class="card shadow-sm border-0 bg-white mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-tree me-1 text-success"></i> Plantaciones y Cultivos en la Parcela
            </h6>
            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalPlantation">
                <i class="fas fa-plus me-1"></i> Agregar Cultivo
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase text-muted">
                        <tr>
                            <th>Especie / Variedad</th>
                            <th>Tipo de Cultivo</th>
                            <th>Fecha Siembra</th>
                            <th>Cosecha Estimada / Frecuencia</th>
                            <th>Plantas Instaladas</th>
                            <th>Rend. Estimado (TM/ha)</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plot->plantations as $p)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $p->crop_species }}</div>
                                    <small class="text-muted">Variedad: {{ $p->variety ?? 'Común / Estándar' }}</small>
                                </td>
                                <td>{!! $p->crop_type_badge !!}</td>
                                <td><span class="font-monospace">{{ $p->planting_date->format('d/m/Y') }}</span></td>
                                <td>
                                    @if($p->crop_type === 'PERMANENT')
                                        <div class="fw-semibold text-primary">
                                            <i class="fas fa-sync-alt me-1"></i>Cada {{ $p->harvest_frequency_days ?? 15 }} días
                                        </div>
                                        <small class="text-muted">Cosecha recurrente</small>
                                    @else
                                        <div class="text-dark">{{ $p->estimated_harvest_date ? $p->estimated_harvest_date->format('d/m/Y') : 'Por definir' }}</div>
                                        <small class="text-muted">Cosecha única de ciclo</small>
                                    @endif
                                </td>
                                <td><strong class="text-dark">{{ number_format($p->plant_count) }} plantas</strong></td>
                                <td><span class="badge bg-light text-dark border">{{ number_format($p->expected_yield_per_ha, 1) }} TM/ha</span></td>
                                <td>{!! $p->status_badge !!}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-plantation" data-id="{{ $p->id }}" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No hay plantaciones registradas en esta parcela. Haga clic en <strong>"Instalar Cultivo / Plantación"</strong> para agregar una.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Historial de Cosechas en la Parcela -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-clipboard-check me-1 text-primary"></i> Historial de Cosechas Realizadas en esta Parcela
            </h6>
            <a href="{{ route('agrolivestock.harvests.quick_entry') }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus me-1"></i> Cargar Nueva Cosecha
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase text-muted">
                        <tr>
                            <th>Fecha</th>
                            <th>Ticket / Boleto Campo</th>
                            <th>Producto Cosechado</th>
                            <th>Cantidad</th>
                            <th>Calidad</th>
                            <th>Almacén Destino</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plot->harvests as $h)
                            <tr>
                                <td class="font-monospace fw-bold">{{ $h->harvest_date->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-light text-dark font-monospace border">
                                        {{ $h->field_ticket_code ?? 'S/N' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $h->producedItem->name ?? 'Producto' }}</div>
                                    <small class="text-muted">{{ $h->campaign->name ?? '' }}</small>
                                </td>
                                <td><strong class="text-success">{{ number_format($h->quantity, 2) }} {{ $h->producedItem->unit_of_measurement ?? '' }}</strong></td>
                                <td><span class="badge bg-secondary text-white">{{ $h->quality_grade }}</span></td>
                                <td>{{ $h->warehouse->descripcion ?? 'Sin Almacén' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Aún no se registran cosechas para esta parcela.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Plantación -->
<div class="modal fade" id="modalPlantation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('agrolivestock.plots.plantations.store') }}">
            @csrf
            <input type="hidden" name="agricultural_plot_id" value="{{ $plot->id }}">

            <div class="modal-content">
                <div class="modal-header bg-success text-white py-2">
                    <h5 class="modal-title fs-6 fw-bold">
                        <i class="fas fa-seedling me-2"></i>Instalar Cultivo / Plantación en {{ $plot->name }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Especie de Cultivo <span class="text-danger">*</span></label>
                            <input type="text" name="crop_species" class="form-control form-control-sm" placeholder="Ej. Palma Aceitera, Cacao, Café, Maíz, Teca" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tipo de Cultivo <span class="text-danger">*</span></label>
                            <select name="crop_type" id="select_crop_type" class="form-select form-select-sm" required>
                                <option value="PERMANENT">Permanente (Cosechas recurrentes continuas - ej. Palma Aceitera, Cacao)</option>
                                <option value="SHORT_CYCLE">Ciclo Corto (Hortalizas, Granos, Legumbres - Cosecha única)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Variedad o Híbrido</label>
                            <input type="text" name="variety" class="form-control form-control-sm" placeholder="Ej. Tenera, CCN-51, Catimor, Marginal 28">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fecha de Siembra / Instalación <span class="text-danger">*</span></label>
                            <input type="date" name="planting_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6" id="box_harvest_freq">
                            <label class="form-label small fw-bold">Frecuencia de Cosecha (Días) <span class="text-danger">*</span></label>
                            <input type="number" name="harvest_frequency_days" class="form-control form-control-sm" value="15" min="1">
                            <small class="text-muted">Por ejemplo, cada 15 días en zafra de palma aceitera</small>
                        </div>
                        <div class="col-md-6" id="box_estimated_harvest_date">
                            <label class="form-label small fw-bold">Fecha Estimada de Cosecha</label>
                            <input type="date" name="estimated_harvest_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Cantidad de Plantas / Densidad <span class="text-danger">*</span></label>
                            <input type="number" name="plant_count" class="form-control form-control-sm" placeholder="Ej. 143 (palma 9x9)" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Distanciamiento (Metros)</label>
                            <input type="text" name="spacing_meters" class="form-control form-control-sm" placeholder="Ej. 9x9 tresbolillo">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Rendimiento Estimado (TM/ha/año)</label>
                            <input type="number" step="0.1" name="expected_yield_per_ha" class="form-control form-control-sm" value="22.0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Estado del Cultivo <span class="text-danger">*</span></label>
                            <select name="status" class="form-select form-select-sm" required>
                                <option value="FULL_PRODUCTION">En Plena Producción</option>
                                <option value="VEGETATIVE_DEVELOPMENT">Desarrollo Vegetativo</option>
                                <option value="DECLINING">En Declinación</option>
                                <option value="RENOVATION">En Renovación</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Notas Técnicas del Cultivo</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Procedencia de semilla, fertilización base, observaciones..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success fw-semibold">
                        <i class="fas fa-save me-1"></i> Registrar Cultivo
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#select_crop_type').on('change', function() {
        if ($(this).val() === 'PERMANENT') {
            $('#box_harvest_freq').show();
        } else {
            $('#box_harvest_freq').hide();
        }
    }).trigger('change');

    $(document).on('click', '.btn-delete-plantation', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar Plantación?',
            text: 'Se eliminará el registro de este cultivo en la parcela.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('agrolivestock.plots.plantations.delete') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id
                    },
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire('Eliminado', resp.message, 'success').then(() => location.reload());
                        }
                    }
                });
            }
        });
    });
});
</script>
@endsection
