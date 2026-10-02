@extends('admin.layout')

@section('title', 'Extracto de Cuenta: ' . $account->bank_name)

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800">
                <i class="fas fa-university text-primary me-2"></i>{{ $account->bank_name }} - {{ $account->account_number }}
            </h3>
            <p class="text-muted small mb-0">Detalle de cuenta y movimientos de extracto bancario.</p>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('treasury.bank_accounts.index') }}" class="btn btn-outline-secondary btn-sm me-1">
                <i class="fas fa-arrow-left me-1"></i> Volver a Cuentas
            </a>
            <a href="{{ route('treasury.bank_accounts.template') }}" class="btn btn-outline-info btn-sm me-1">
                <i class="fas fa-file-csv me-1"></i> Plantilla CSV
            </a>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modal-import-statement">
                <i class="fas fa-file-upload me-1"></i> Importar Extracto
            </button>
        </div>
    </div>

    <!-- Account Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Saldo Actual</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($account->current_balance, 2) }}</div>
                    <small class="text-muted">Inicial: S/ {{ number_format($account->initial_balance, 2) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Fuente Financiamiento</div>
                    <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $account->fundSource?->code }}</div>
                    <small class="text-muted">{{ Str::limit($account->fundSource?->name, 25) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cuenta PCGE</div>
                    <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $account->accountingAccount?->code }}</div>
                    <small class="text-muted">{{ Str::limit($account->accountingAccount?->name, 25) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Movimientos Registrados</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $account->movements->count() }}</div>
                    <small class="text-muted">{{ $account->account_type }} ({{ $account->currency }})</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Movements Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list-alt me-1"></i> Movimientos del Extracto Bancario</h6>
            <span class="badge bg-secondary">Deduplicación activa por fecha + referencia + monto</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>FECHA</th>
                            <th>N° OPERACIÓN / VOUCHER</th>
                            <th>TIPO</th>
                            <th>CONCEPTO / GLOSA</th>
                            <th class="text-end">CARGO (-)</th>
                            <th class="text-end">ABONO (+)</th>
                            <th class="text-center">ESTADO CONCILIACIÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($account->movements as $mov)
                            <tr>
                                <td>{{ $mov->movement_date->format('d/m/Y') }}</td>
                                <td><code>{{ $mov->operation_number }}</code></td>
                                <td>
                                    @if($mov->movement_type === 'INFLOW')
                                        <span class="badge bg-success-subtle text-success border border-success">Abono</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger">Cargo</span>
                                    @endif
                                </td>
                                <td>{{ $mov->concept }}</td>
                                <td class="text-end text-danger">
                                    {{ $mov->movement_type === 'OUTFLOW' ? 'S/ ' . number_format($mov->amount, 2) : '-' }}
                                </td>
                                <td class="text-end text-success">
                                    {{ $mov->movement_type === 'INFLOW' ? 'S/ ' . number_format($mov->amount, 2) : '-' }}
                                </td>
                                <td class="text-center">
                                    @if($mov->reconciliation_status === 'RECONCILED' || $mov->reconciliation_status === 'MATCHED')
                                        <span class="badge bg-success">Conciliado</span>
                                    @elseif($mov->reconciliation_status === 'DISCREPANCY')
                                        <span class="badge bg-danger">Discrepancia</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-receipt fa-2x mb-2 d-block text-gray-400"></i>
                                    No hay movimientos registrados para esta cuenta. Use el botón <strong>Importar Extracto</strong>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Import Statement -->
<div class="modal fade" id="modal-import-statement" tabindex="-1">
    <div class="modal-dialog">
        <form id="form-import-statement" class="modal-content" enctype="multipart/form-data">
            @csrf
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title font-weight-bold"><i class="fas fa-file-upload me-1"></i> Importar Extracto Bancario</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Suba el extracto bancario en formato CSV o Excel. El sistema identificará duplicados automáticamente mediante la clave única <code>fecha + referencia + monto</code>.
                </p>
                <div class="mb-3">
                    <label class="form-label small font-weight-bold">Archivo de Extracto (.csv, .xlsx, .xls) <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.xlsx,.xls,.txt" required>
                </div>
                <div class="alert alert-info py-2 small mb-0">
                    <i class="fas fa-info-circle me-1"></i> Si no dispone del formato estándar, descargue primero la
                    <a href="{{ route('treasury.bank_accounts.template') }}" class="alert-link">Plantilla CSV</a>.
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-upload me-1"></i> Iniciar Importación</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('form-import-statement')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch("{{ route('treasury.bank_accounts.import', $account->id) }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Error en la importación.'));
        }
    })
    .catch(err => {
        alert('Error en la solicitud: ' + err.message);
    });
});
</script>
@endpush
