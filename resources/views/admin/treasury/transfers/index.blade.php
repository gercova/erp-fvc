@extends('admin.layout')

@section('title', 'Movimientos y Transferencias Internas de Tesorería')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <h3 class="mb-0 text-gray-800"><i class="fas fa-exchange-alt text-primary me-2"></i>Movimientos entre Cuentas y Caja</h3>
            <p class="text-muted small mb-0">Transferencias interbancarias, depósitos de arqueos de caja y transferencias a la CUT con doble asiento.</p>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modal-bank-to-bank">
                <i class="fas fa-university me-1"></i> Entre Cuentas Bancarias
            </button>
            <button type="button" class="btn btn-success btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modal-cash-to-bank">
                <i class="fas fa-cash-register me-1"></i> Depósito de Caja a Banco
            </button>
            <button type="button" class="btn btn-warning btn-sm text-dark" data-bs-toggle="modal" data-bs-target="#modal-to-cut">
                <i class="fas fa-landmark me-1"></i> Transferencia a la CUT
            </button>
        </div>
    </div>

    <!-- Transfers Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-1"></i> Historial de Transferencias Contabilizadas</h6>
            <span class="badge bg-secondary">{{ $transfers->count() }} Operaciones</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>CÓDIGO / FECHA</th>
                            <th>TIPO DE MOVIMIENTO</th>
                            <th>ORIGEN</th>
                            <th>DESTINO</th>
                            <th class="text-end">MONTO</th>
                            <th>CONCEPTO / REF</th>
                            <th class="text-center">ASIENTO CONTABLE</th>
                            <th>RESPONSABLE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $trf)
                            <tr>
                                <td>
                                    <strong>{{ $trf->transfer_code }}</strong>
                                    <small class="d-block text-muted">{{ $trf->transfer_date->format('d/m/Y') }}</small>
                                </td>
                                <td>
                                    @if($trf->transfer_type === 'BANK_TO_BANK')
                                        <span class="badge bg-info text-dark">Interbancario</span>
                                    @elseif($trf->transfer_type === 'CASH_TO_BANK')
                                        <span class="badge bg-success">Depósito Caja -> Banco</span>
                                    @elseif($trf->transfer_type === 'TRANSFER_TO_CUT')
                                        <span class="badge bg-warning text-dark">Transferencia a CUT</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $trf->transfer_type }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($trf->source_type === 'CASH')
                                        <i class="fas fa-cash-register text-success me-1"></i> Arqueo Caja #{{ $trf->source_arching_cash_id }}
                                    @else
                                        <i class="fas fa-university text-primary me-1"></i> {{ $trf->sourceBankAccount?->bank_name }}
                                        <small class="d-block text-muted">{{ $trf->sourceBankAccount?->account_number }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($trf->destination_type === 'CUT')
                                        <i class="fas fa-landmark text-warning me-1"></i> CUT ({{ $trf->destinationFundSource?->name }})
                                    @else
                                        <i class="fas fa-university text-primary me-1"></i> {{ $trf->destinationBankAccount?->bank_name }}
                                        <small class="d-block text-muted">{{ $trf->destinationBankAccount?->account_number }}</small>
                                    @endif
                                </td>
                                <td class="text-end font-weight-bold">S/ {{ number_format($trf->amount, 2) }}</td>
                                <td>
                                    {{ $trf->concept }}
                                    @if($trf->reference_number)
                                        <small class="d-block text-muted">Ref: {{ $trf->reference_number }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($trf->journalEntry)
                                        <span class="badge bg-light text-dark border">
                                            Asiento #{{ $trf->journalEntry->entry_number }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">Sin Asiento</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $trf->createdByUser?->name ?? 'Usuario' }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-exchange-alt fa-2x mb-2 d-block text-gray-400"></i>
                                    No se registran movimientos internos. Utilice los botones superiores para registrar transferencias.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Bank to Bank -->
<div class="modal fade" id="modal-bank-to-bank" tabindex="-1">
    <div class="modal-dialog">
        <form id="form-bank-to-bank" class="modal-content">
            @csrf
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title font-weight-bold"><i class="fas fa-university me-1"></i> Transferencia entre Cuentas Bancarias</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Cuenta de Origen <span class="text-danger">*</span></label>
                    <select name="source_bank_account_id" class="form-select form-select-sm" required>
                        <option value="">-- Origen --</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Cuenta de Destino <span class="text-danger">*</span></label>
                    <select name="destination_bank_account_id" class="form-select form-select-sm" required>
                        <option value="">-- Destino --</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Monto (S/) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="0.00" min="0.01" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Fecha <span class="text-danger">*</span></label>
                    <input type="date" name="transfer_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label small font-weight-bold">N° Operación / Referencia</label>
                    <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="Ej. OP-449102">
                </div>
                <div class="col-md-12">
                    <label class="form-label small font-weight-bold">Concepto <span class="text-danger">*</span></label>
                    <input type="text" name="concept" class="form-control form-control-sm" placeholder="Ej. Transferencia de fondos operativos" required>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane me-1"></i> Procesar y Contabilizar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Cash to Bank -->
<div class="modal fade" id="modal-cash-to-bank" tabindex="-1">
    <div class="modal-dialog">
        <form id="form-cash-to-bank" class="modal-content">
            @csrf
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title font-weight-bold"><i class="fas fa-cash-register me-1"></i> Depósito de Caja / Arqueo a Banco</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Arqueo de Caja Origen <span class="text-danger">*</span></label>
                    <select name="source_arching_cash_id" class="form-select form-select-sm" required>
                        @foreach($archingCashes as $ac)
                            <option value="{{ $ac->id }}">Arqueo #{{ $ac->id }} (Caja {{ $ac->idcaja }}) - {{ $ac->fecha_inicio }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Cuenta Bancaria Destino <span class="text-danger">*</span></label>
                    <select name="destination_bank_account_id" class="form-select form-select-sm" required>
                        <option value="">-- Cuenta Bancaria --</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Monto Depositado (S/) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="0.00" min="0.01" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Fecha del Depósito <span class="text-danger">*</span></label>
                    <input type="date" name="transfer_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label small font-weight-bold">Voucher / N° Operación Bancaria</label>
                    <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="Ej. VOUCHER-9921">
                </div>
                <div class="col-md-12">
                    <label class="form-label small font-weight-bold">Concepto <span class="text-danger">*</span></label>
                    <input type="text" name="concept" class="form-control form-control-sm" placeholder="Ej. Depósito de recaudación diaria en ventanilla" required>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i> Registrar y Contabilizar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Transfer to CUT -->
<div class="modal fade" id="modal-to-cut" tabindex="-1">
    <div class="modal-dialog">
        <form id="form-to-cut" class="modal-content">
            @csrf
            <div class="modal-header bg-warning text-dark py-2">
                <h6 class="modal-title font-weight-bold"><i class="fas fa-landmark me-1"></i> Transferencia a la Cuenta Única del Tesoro (CUT)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Cuenta Bancaria de Origen <span class="text-danger">*</span></label>
                    <select name="source_bank_account_id" class="form-select form-select-sm" required>
                        <option value="">-- Cuenta Origen --</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Fondo CUT de Destino <span class="text-danger">*</span></label>
                    <select name="destination_fund_source_id" class="form-select form-select-sm" required>
                        <option value="">-- Fondo CUT --</option>
                        @foreach($cutFunds as $cf)
                            <option value="{{ $cf->id }}">{{ $cf->code }} - {{ $cf->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Monto a Transferir (S/) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="0.00" min="0.01" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Fecha <span class="text-danger">*</span></label>
                    <input type="date" name="transfer_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label small font-weight-bold">N° Operación / Papeleta de Depósito</label>
                    <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="Ej. CUT-PAPELETA-01">
                </div>
                <div class="col-md-12">
                    <label class="form-label small font-weight-bold">Concepto <span class="text-danger">*</span></label>
                    <input type="text" name="concept" class="form-control form-control-sm" placeholder="Ej. Reversión de recaudación a la CUT" required>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning text-dark btn-sm"><i class="fas fa-paper-plane me-1"></i> Registrar en CUT y Contabilizar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function handleFormSubmit(formId, routeUrl) {
    document.getElementById(formId)?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch(routeUrl, {
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
                alert('Error: ' + (data.message || 'Error en la operación.'));
            }
        })
        .catch(err => alert('Error: ' + err.message));
    });
}

handleFormSubmit('form-bank-to-bank', "{{ route('treasury.transfers.bank_to_bank') }}");
handleFormSubmit('form-cash-to-bank', "{{ route('treasury.transfers.cash_to_bank') }}");
handleFormSubmit('form-to-cut', "{{ route('treasury.transfers.to_cut') }}");
</script>
@endpush
