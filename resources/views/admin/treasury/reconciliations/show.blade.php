@extends('admin.layout')

@section('title', 'Mesa de Conciliación Bancaria - ' . $reconciliation->period_year . '-' . str_pad($reconciliation->period_month, 2, '0', STR_PAD_LEFT))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-7">
            <h3 class="mb-0 text-gray-800">
                <i class="fas fa-balance-scale text-primary me-2"></i>Conciliación: {{ $reconciliation->bankAccount?->bank_name }}
                <span class="badge bg-secondary ms-2">{{ $reconciliation->period_year }}-{{ str_pad($reconciliation->period_month, 2, '0', STR_PAD_LEFT) }}</span>
            </h3>
            <p class="text-muted small mb-0">Cuenta: <code>{{ $reconciliation->bankAccount?->account_number }}</code> | PCGE: <code>{{ $reconciliation->bankAccount?->accountingAccount?->code }}</code></p>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('treasury.reconciliations.index') }}" class="btn btn-outline-secondary btn-sm me-1">
                <i class="fas fa-arrow-left me-1"></i> Volver a Lista
            </a>
            <button type="button" class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modal-add-item">
                <i class="fas fa-plus me-1"></i> Agregar Partida Conciliatoria
            </button>
            <button type="button" class="btn btn-success btn-sm" id="btn-close-reconciliation" {{ abs((float)$reconciliation->reconciled_difference) > 0.01 ? 'disabled' : '' }}>
                <i class="fas fa-lock me-1"></i> Cerrar con Cero Diferencia
            </button>
        </div>
    </div>

    <!-- Mathematical KPI Reconciliation Cards -->
    <div class="row g-3 mb-4">
        <!-- Saldo Extracto Bancario -->
        <div class="col-md-3">
            <div class="card shadow-sm border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">1. Saldo Extracto Bancario</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($reconciliation->bank_statement_balance, 2) }}</div>
                    <small class="text-muted">Al {{ $reconciliation->statement_closing_date ? $reconciliation->statement_closing_date->format('d/m/Y') : '-' }}</small>
                </div>
            </div>
        </div>

        <!-- Partidas Conciliatorias Banco -->
        <div class="col-md-3">
            <div class="card shadow-sm border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">2. Partidas sobre Banco</div>
                    <div class="small mb-1 text-success font-weight-bold">(+) Dep. en Tránsito: S/ {{ number_format($reconciliation->uncredited_deposits, 2) }}</div>
                    <div class="small text-danger font-weight-bold">(-) Cheques Girados: S/ {{ number_format($reconciliation->outstanding_checks, 2) }}</div>
                    <div class="h6 mt-1 mb-0 font-weight-bold text-dark">
                        Ajustado: S/ {{ number_format((float)$reconciliation->bank_statement_balance + (float)$reconciliation->uncredited_deposits - (float)$reconciliation->outstanding_checks, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Saldo Libro Mayor (PCGE 104) -->
        <div class="col-md-3">
            <div class="card shadow-sm border-left-secondary h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">3. Saldo Libro Mayor (104)</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($reconciliation->book_calculated_balance, 2) }}</div>
                    <div class="small text-danger font-weight-bold">(-) Cargos No Reg.: S/ {{ number_format($reconciliation->unrecorded_bank_charges, 2) }}</div>
                    <div class="h6 mt-1 mb-0 font-weight-bold text-dark">
                        Ajustado: S/ {{ number_format((float)$reconciliation->book_calculated_balance - (float)$reconciliation->unrecorded_bank_charges + (float)$reconciliation->unrecorded_bank_credits, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Diferencia Conciliatoria -->
        <div class="col-md-3">
            <div class="card shadow-sm {{ abs((float)$reconciliation->reconciled_difference) < 0.01 ? 'border-left-success bg-success-subtle' : 'border-left-danger bg-danger-subtle' }} h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold {{ abs((float)$reconciliation->reconciled_difference) < 0.01 ? 'text-success' : 'text-danger' }} text-uppercase mb-1">
                        4. Diferencia Conciliatoria
                    </div>
                    <div class="h4 mb-0 font-weight-bold {{ abs((float)$reconciliation->reconciled_difference) < 0.01 ? 'text-success' : 'text-danger' }}">
                        S/ {{ number_format($reconciliation->reconciled_difference, 2) }}
                    </div>
                    <small class="{{ abs((float)$reconciliation->reconciled_difference) < 0.01 ? 'text-success' : 'text-danger' }}">
                        {{ abs((float)$reconciliation->reconciled_difference) < 0.01 ? '✓ Conciliación Cuadrada (0.00)' : '⚠ Discrepancia pendiente de ajuste' }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Workspace -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-2">
            <ul class="nav nav-tabs card-header-tabs" id="rec-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active font-weight-bold" id="tab-suggestions-btn" data-bs-toggle="tab" data-bs-target="#tab-suggestions" type="button">
                        <i class="fas fa-magic me-1 text-primary"></i> Sugerencias de Coincidencia Automática
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link font-weight-bold" id="tab-items-btn" data-bs-toggle="tab" data-bs-target="#tab-items" type="button">
                        <i class="fas fa-clipboard-list me-1 text-info"></i> Partidas y Ajustes Conciliatorios ({{ $reconciliation->items->count() }})
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="rec-tab-content">
                <!-- Tab 1: Suggestions -->
                <div class="tab-pane fade show active" id="tab-suggestions" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="small text-muted mb-0">
                            Algoritmo de emparejamiento automático por <strong>Monto exacto + Fecha (±3 días) + N° de Operación</strong>.
                        </p>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-refresh-suggestions">
                            <i class="fas fa-sync-alt me-1"></i> Actualizar Sugerencias
                        </button>
                    </div>
                    <div id="suggestions-container">
                        <div class="text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                            <p class="mb-0">Buscando coincidencias entre extracto y asientos contables...</p>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Reconciling Items -->
                <div class="tab-pane fade" id="tab-items" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>TIPO DE PARTIDA</th>
                                    <th>REFERENCIA</th>
                                    <th>CONCEPTO</th>
                                    <th class="text-end">MONTO (S/)</th>
                                    <th>ASIENTO ASOCIADO</th>
                                    <th class="text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reconciliation->items as $item)
                                    <tr>
                                        <td>
                                            @if($item->item_type === 'MATCHED')
                                                <span class="badge bg-success">Conciliado</span>
                                            @elseif($item->item_type === 'DEPOSIT_IN_TRANSIT')
                                                <span class="badge bg-info text-dark">Depósito en Tránsito (+)</span>
                                            @elseif($item->item_type === 'OUTSTANDING_CHECK')
                                                <span class="badge bg-warning text-dark">Cheque en Circulación (-)</span>
                                            @elseif($item->item_type === 'BANK_CHARGE')
                                                <span class="badge bg-danger">Cargo Bancario / ITF (-)</span>
                                            @elseif($item->item_type === 'BANK_CREDIT')
                                                <span class="badge bg-primary">Abono Bancario (+)</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $item->item_type }}</span>
                                            @endif
                                        </td>
                                        <td><code>{{ $item->reference ?? '-' }}</code></td>
                                        <td>{{ $item->concept ?? '-' }}</td>
                                        <td class="text-end font-weight-bold">S/ {{ number_format($item->amount, 2) }}</td>
                                        <td>
                                            @if($item->journalEntry)
                                                <span class="badge bg-light text-dark border">
                                                    Asiento #{{ $item->journalEntry->entry_number }}
                                                </span>
                                            @else
                                                <span class="text-muted small">Sin asiento</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($item->item_type === 'BANK_CHARGE' && !$item->journal_entry_id)
                                                <button type="button" class="btn btn-warning btn-sm py-0 px-2 btn-adjust-charge"
                                                    data-id="{{ $item->id }}"
                                                    data-amount="{{ $item->amount }}"
                                                    data-concept="{{ $item->concept }}"
                                                    data-ref="{{ $item->reference }}">
                                                    <i class="fas fa-bolt me-1"></i> Generar Asiento de Ajuste
                                                </button>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No se han registrado partidas conciliatorias en este período.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Reconciling Item -->
<div class="modal fade" id="modal-add-item" tabindex="-1">
    <div class="modal-dialog">
        <form id="form-add-item" class="modal-content">
            @csrf
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title font-weight-bold"><i class="fas fa-plus-circle me-1"></i> Agregar Partida Conciliatoria</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-12">
                    <label class="form-label small font-weight-bold">Tipo de Partida <span class="text-danger">*</span></label>
                    <select name="item_type" class="form-select form-select-sm" required>
                        <option value="DEPOSIT_IN_TRANSIT">Depósito en tránsito (+ saldo extracto)</option>
                        <option value="OUTSTANDING_CHECK">Cheque en circulación / pendiente de cobro (- saldo extracto)</option>
                        <option value="BANK_CHARGE">Cargo bancario / ITF / Comisión (- saldo libro mayor)</option>
                        <option value="BANK_CREDIT">Abono bancario / Interés (+ saldo libro mayor)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">Monto (S/) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="0.00" min="0.01" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small font-weight-bold">N° Operación / Referencia</label>
                    <input type="text" name="reference" class="form-control form-control-sm" placeholder="Ej. CHQ-10492">
                </div>
                <div class="col-md-12">
                    <label class="form-label small font-weight-bold">Concepto o Glosa <span class="text-danger">*</span></label>
                    <input type="text" name="concept" class="form-control form-control-sm" placeholder="Ej. Comisión mantenimiento cuenta corriente" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label small font-weight-bold">Notas adicionales</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Guardar Partida</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const recId = {{ $reconciliation->id }};

function loadSuggestions() {
    const container = document.getElementById('suggestions-container');
    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><p class="text-muted small">Cargando sugerencias...</p></div>';

    fetch(`{{ url('accounting/treasury/reconciliations') }}/${recId}/suggestions`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.suggestions || data.suggestions.length === 0) {
            container.innerHTML = '<div class="text-center py-4 text-muted"><i class="fas fa-check-double fa-2x mb-2 text-success"></i><p class="mb-0">No hay movimientos pendientes de conciliar o sugerir.</p></div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light small"><tr><th>MOVIMIENTO EXTRACTO</th><th>MONTO</th><th>ASIENTO CONTABLE SUGERIDO</th><th>CONFIANZA</th><th class="text-center">ACCIÓN</th></tr></thead><tbody>';

        data.suggestions.forEach(s => {
            const badgeClass = s.confidence === 'HIGH' ? 'bg-success' : 'bg-warning text-dark';
            html += `<tr>
                <td>
                    <strong>${s.movement.movement_date}</strong> - <code>${s.movement.operation_number}</code>
                    <small class="d-block text-muted">${s.movement.concept}</small>
                </td>
                <td class="font-weight-bold">S/ ${parseFloat(s.movement.amount).toFixed(2)}</td>
                <td>
                    <strong>Asiento #${s.journal_entry.entry_number}</strong> (${s.journal_entry.entry_date})
                    <small class="d-block text-muted">${s.journal_entry.concept}</small>
                </td>
                <td>
                    <span class="badge ${badgeClass}">${s.confidence} (${s.days_difference}d dif.)</span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-primary btn-sm py-0 px-2 btn-confirm-match"
                        data-mov-id="${s.movement.id}"
                        data-entry-id="${s.journal_entry.id}">
                        <i class="fas fa-check me-1"></i> Conciliar
                    </button>
                </td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;

        document.querySelectorAll('.btn-confirm-match').forEach(btn => {
            btn.addEventListener('click', function() {
                confirmMatch(this.dataset.movId, this.dataset.entryId);
            });
        });
    })
    .catch(err => {
        container.innerHTML = `<div class="alert alert-danger py-2">Error al cargar sugerencias: ${err.message}</div>`;
    });
}

function confirmMatch(movId, entryId) {
    fetch(`{{ url('accounting/treasury/reconciliations') }}/${recId}/match`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ bank_movement_id: movId, journal_entry_id: entryId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

document.getElementById('btn-refresh-suggestions')?.addEventListener('click', loadSuggestions);
document.addEventListener('DOMContentLoaded', loadSuggestions);

// Add Reconciling Item
document.getElementById('form-add-item')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch(`{{ url('accounting/treasury/reconciliations') }}/${recId}/items`, {
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
            alert('Error: ' + data.message);
        }
    });
});

// Adjust Bank Charge
document.querySelectorAll('.btn-adjust-charge').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!confirm(`¿Generar asiento contable de ajuste por S/ ${this.dataset.amount} para ${this.dataset.concept}?`)) {
            return;
        }

        fetch(`{{ url('accounting/treasury/reconciliations') }}/${recId}/adjustment`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                amount: this.dataset.amount,
                concept: this.dataset.concept,
                reference: this.dataset.ref,
                item_id: this.dataset.id
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });
});

// Close Reconciliation
document.getElementById('btn-close-reconciliation')?.addEventListener('click', function() {
    if (!confirm('¿Está seguro de cerrar la conciliación bancaria con diferencia cero?')) {
        return;
    }

    fetch(`{{ url('accounting/treasury/reconciliations') }}/${recId}/close`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error al cerrar conciliación: ' + data.message);
        }
    });
});
</script>
@endpush
