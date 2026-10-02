@extends('admin.layout')

@section('title', 'Cuentas Bancarias de Tesorería')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800"><i class="fas fa-university text-primary me-2"></i>Cuentas Bancarias de Tesorería</h3>
            <p class="text-muted small mb-0">Gestión de cuentas corrientes, cajas de ahorro y fondos vinculados al PCGE (cta 104x).</p>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('treasury.bank_accounts.template') }}" class="btn btn-outline-secondary btn-sm me-1">
                <i class="fas fa-download me-1"></i> Descargar Plantilla CSV
            </a>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-create-account">
                <i class="fas fa-plus-circle me-1"></i> Nueva Cuenta Bancaria
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Bank Accounts Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-1"></i> Catálogo de Cuentas</h6>
            <span class="badge bg-info text-dark">{{ $accounts->count() }} Registradas</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>BANCO / ENTIDAD</th>
                            <th>N° CUENTA / CCI</th>
                            <th>TIPO</th>
                            <th>MONEDA</th>
                            <th>FUENTE DE FINANC.</th>
                            <th>CUENTA CONTABLE PCGE</th>
                            <th class="text-end">SALDO INICIAL</th>
                            <th class="text-end">SALDO ACTUAL</th>
                            <th class="text-center">ESTADO</th>
                            <th class="text-center">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $acc)
                            <tr>
                                <td>
                                    <strong class="text-primary">{{ $acc->bank_name }}</strong>
                                </td>
                                <td>
                                    <div><code>{{ $acc->account_number }}</code></div>
                                    @if($acc->cci)
                                        <small class="text-muted">CCI: {{ $acc->cci }}</small>
                                    @endif
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $acc->account_type }}</span></td>
                                <td><strong>{{ $acc->currency }}</strong></td>
                                <td>
                                    <span class="badge bg-secondary">{{ $acc->fundSource?->code ?? 'N/A' }}</span>
                                    <small class="d-block text-muted">{{ Str::limit($acc->fundSource?->name ?? '', 20) }}</small>
                                </td>
                                <td>
                                    <code>{{ $acc->accountingAccount?->code ?? '104' }}</code>
                                    <small class="d-block text-muted">{{ Str::limit($acc->accountingAccount?->name ?? '', 25) }}</small>
                                </td>
                                <td class="text-end">S/ {{ number_format($acc->initial_balance, 2) }}</td>
                                <td class="text-end">
                                    <strong class="{{ $acc->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                        S/ {{ number_format($acc->current_balance, 2) }}
                                    </strong>
                                </td>
                                <td class="text-center">
                                    @if($acc->is_active)
                                        <span class="badge bg-success">Activa</span>
                                    @else
                                        <span class="badge bg-danger">Inactiva</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('treasury.bank_accounts.show', $acc->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2" title="Ver Movimientos y Extracto">
                                        <i class="fas fa-eye me-1"></i> Extracto
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fas fa-university fa-2x mb-2 d-block text-gray-400"></i>
                                    No hay cuentas bancarias registradas aún. Haga clic en <strong>Nueva Cuenta Bancaria</strong>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create Account -->
<div class="modal fade" id="modal-create-account" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="form-create-account" class="modal-content">
            @csrf
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title font-weight-bold"><i class="fas fa-plus-circle me-1"></i> Registrar Nueva Cuenta Bancaria</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Banco o Entidad Financiera <span class="text-danger">*</span></label>
                    <input type="text" name="bank_name" class="form-control form-control-sm" placeholder="Ej. Banco de la Nación" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Tipo de Cuenta <span class="text-danger">*</span></label>
                    <select name="account_type" class="form-select form-select-sm" required>
                        <option value="CURRENT">Cuenta Corriente Operativa</option>
                        <option value="SAVINGS">Cuenta de Ahorros</option>
                        <option value="CUT">Cuenta Única del Tesoro (CUT)</option>
                        <option value="COLLECTION">Cuenta Recaudadora RDR</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Número de Cuenta <span class="text-danger">*</span></label>
                    <input type="text" name="account_number" class="form-control form-control-sm" placeholder="Ej. 00-068-123456" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Código de Cuenta Interbancario (CCI)</label>
                    <input type="text" name="cci" class="form-control form-control-sm" placeholder="Ej. 018-068-000068123456-78">
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Fuente de Financiamiento Vinculada <span class="text-danger">*</span></label>
                    <select name="fund_source_id" class="form-select form-select-sm" required>
                        <option value="">-- Seleccionar Fuente --</option>
                        @foreach($fundSources as $fund)
                            <option value="{{ $fund->id }}">{{ $fund->code }} - {{ $fund->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Cuenta PCGE Vinculada (cta 104x) <span class="text-danger">*</span></label>
                    <select name="accounting_account_id" class="form-select form-select-sm" required>
                        <option value="">-- Seleccionar Cuenta PCGE --</option>
                        @foreach($accountingAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small font-weight-bold">Moneda</label>
                    <select name="currency" class="form-select form-select-sm">
                        <option value="PEN">PEN (Soles)</option>
                        <option value="USD">USD (Dólares)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small font-weight-bold">Saldo Inicial (S/)</label>
                    <input type="number" step="0.01" name="initial_balance" class="form-control form-control-sm" value="0.00" min="0">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Guardar Cuenta</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('form-create-account')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch("{{ route('treasury.bank_accounts.store') }}", {
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
            alert('Error: ' + (data.message || 'No se pudo registrar la cuenta.'));
        }
    })
    .catch(err => {
        alert('Error en la solicitud: ' + err.message);
    });
});
</script>
@endpush
