@extends('admin.layout')

@section('title', 'Conciliación Bancaria Integral')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800"><i class="fas fa-tasks text-primary me-2"></i>Conciliación Bancaria Integral</h3>
            <p class="text-muted small mb-0">Conciliación mensual de extractos bancarios vs. saldo contable del Libro Mayor (PCGE 104x).</p>
        </div>
        <div class="col-md-5 text-end">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-initiate-rec">
                <i class="fas fa-plus-circle me-1"></i> Iniciar Conciliación
            </button>
        </div>
    </div>

    <!-- Reconciliations Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history me-1"></i> Períodos de Conciliación</h6>
            <span class="badge bg-secondary">{{ $reconciliations->count() }} Registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>PERÍODO</th>
                            <th>BANCO / CUENTA</th>
                            <th>FECHA CORTE</th>
                            <th class="text-end">SALDO EXTRACTO</th>
                            <th class="text-end">SALDO LIBRO MAYOR (104)</th>
                            <th class="text-end">DIFERENCIA</th>
                            <th class="text-center">ESTADO</th>
                            <th>CONCILIADO POR</th>
                            <th class="text-center">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reconciliations as $rec)
                            <tr>
                                <td>
                                    <strong>{{ $rec->period_year }}-{{ str_pad($rec->period_month, 2, '0', STR_PAD_LEFT) }}</strong>
                                </td>
                                <td>
                                    <strong>{{ $rec->bankAccount?->bank_name ?? $rec->fundSource?->bank_name ?? 'Banco' }}</strong>
                                    <small class="d-block text-muted">{{ $rec->bankAccount?->account_number ?? $rec->fundSource?->account_number }}</small>
                                </td>
                                <td>{{ $rec->statement_closing_date ? $rec->statement_closing_date->format('d/m/Y') : '-' }}</td>
                                <td class="text-end font-weight-bold">S/ {{ number_format($rec->bank_statement_balance, 2) }}</td>
                                <td class="text-end">S/ {{ number_format($rec->book_calculated_balance, 2) }}</td>
                                <td class="text-end">
                                    @if(abs((float)$rec->reconciled_difference) < 0.01)
                                        <span class="text-success font-weight-bold">S/ 0.00</span>
                                    @else
                                        <span class="text-danger font-weight-bold">S/ {{ number_format($rec->reconciled_difference, 2) }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($rec->status === 'BALANCED' || $rec->status === 'APPROVED')
                                        <span class="badge bg-success">Conciliado (Cero Dif.)</span>
                                    @elseif($rec->status === 'DISCREPANCY')
                                        <span class="badge bg-danger">Discrepancia</span>
                                    @else
                                        <span class="badge bg-warning text-dark">En Revisión</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $rec->reconciledByUser?->name ?? 'Usuario' }}</small>
                                    @if($rec->reconciled_at)
                                        <small class="d-block text-muted">{{ $rec->reconciled_at->format('d/m/Y H:i') }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('treasury.reconciliations.show', $rec->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2">
                                        <i class="fas fa-edit me-1"></i> Trabajar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-tasks fa-2x mb-2 d-block text-gray-400"></i>
                                    No hay conciliaciones bancarias registradas. Haga clic en <strong>Iniciar Conciliación</strong>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Initiate Reconciliation -->
<div class="modal fade" id="modal-initiate-rec" tabindex="-1">
    <div class="modal-dialog">
        <form id="form-initiate-rec" class="modal-content">
            @csrf
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title font-weight-bold"><i class="fas fa-plus-circle me-1"></i> Iniciar Conciliación Bancaria</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-12">
                    <label class="form-label small font-weight-bold">Cuenta Bancaria <span class="text-danger">*</span></label>
                    <select name="bank_account_id" class="form-select form-select-sm" required>
                        <option value="">-- Seleccionar Cuenta --</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">
                                {{ $acc->bank_name }} - {{ $acc->account_number }} (cta {{ $acc->accountingAccount?->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Año Fiscal <span class="text-danger">*</span></label>
                    <input type="number" name="period_year" class="form-control form-control-sm" value="{{ date('Y') }}" min="2020" max="2035" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Mes <span class="text-danger">*</span></label>
                    <select name="period_month" class="form-select form-select-sm" required>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                {{ str_pad($m, 2, '0', STR_PAD_LEFT) }} - {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Saldo según Extracto (S/) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="bank_statement_balance" class="form-control form-control-sm" placeholder="0.00" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Fecha de Corte del Extracto</label>
                    <input type="date" name="statement_closing_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label small font-weight-bold">Notas u Observaciones</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Observaciones preliminares..."></textarea>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-play me-1"></i> Abrir Conciliación</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('form-initiate-rec')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch("{{ route('treasury.reconciliations.store') }}", {
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
            window.location.href = "{{ url('accounting/treasury/reconciliations') }}/" + data.reconciliation.id;
        } else {
            alert('Error: ' + (data.message || 'Error al iniciar la conciliación.'));
        }
    })
    .catch(err => {
        alert('Error en la solicitud: ' + err.message);
    });
});
</script>
@endpush
