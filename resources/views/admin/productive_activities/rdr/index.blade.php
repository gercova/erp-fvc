@extends('admin.layout')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Módulo RDR y Gestión de Tesorería (APE)</h1>
            <p class="text-muted small mb-0">Control de transferencias CUT, habilitaciones internas, saldos bancarios y cierres contables periódicos.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newCutModal">
                <i class="fas fa-university me-1"></i> Transferencia a la CUT
            </button>
            <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#newLoanModal">
                <i class="fas fa-hand-holding-usd me-1"></i> Habilitación / Préstamo
            </button>
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#newPeriodClosureModal">
                <i class="fas fa-lock me-1"></i> Generar Cierre de Período
            </button>
        </div>
    </div>

    <!-- 1. Cuadro de Conciliación Bancaria y Saldos Declarados -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary">
                <i class="fas fa-scale-balanced me-2"></i> Conciliación de Saldos Declarados por Fuente de Financiamiento (Año {{ $selectedYear }})
            </h6>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#reconciliationModal">
                <i class="fas fa-plus me-1"></i> Registrar Saldo Extracto Bancario
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.9rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Fuente de Fondos / Cuenta</th>
                            <th class="text-end">Saldo Inicial</th>
                            <th class="text-end text-success">Total Ingresos (+)</th>
                            <th class="text-end text-danger">Total Egresos (-)</th>
                            <th class="text-end fw-bold">Saldo Sistema</th>
                            <th class="text-end fw-bold">Saldo Extracto Declarado</th>
                            <th class="text-end">Diferencia</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fundReconciliationReport as $report)
                            <tr>
                                <td class="fw-bold">
                                    {{ $report['fund']->name }}
                                    <div class="small text-muted font-monospace">{{ $report['fund']->code }} &bull; Cta: {{ $report['fund']->account_number ?? 'Efectivo' }}</div>
                                </td>
                                <td class="text-end font-monospace">S/ {{ number_format($report['initial_balance'], 2) }}</td>
                                <td class="text-end text-success font-monospace">+ S/ {{ number_format($report['total_income'], 2) }}</td>
                                <td class="text-end text-danger font-monospace">- S/ {{ number_format($report['total_expense'], 2) }}</td>
                                <td class="text-end fw-bold font-monospace bg-light">S/ {{ number_format($report['system_calculated'], 2) }}</td>
                                <td class="text-end fw-bold font-monospace bg-light">S/ {{ number_format($report['bank_declared'], 2) }}</td>
                                <td class="text-end font-monospace fw-bold {{ abs($report['difference']) < 0.01 ? 'text-success' : 'text-danger' }}">
                                    S/ {{ number_format($report['difference'], 2) }}
                                </td>
                                <td class="text-center">
                                    @if(abs($report['difference']) < 0.01)
                                        <span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i> Conciliado</span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i> Discrepancia</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- 2. Transferencias a la Cuenta Única del Tesoro (CUT) -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-exchange-alt me-1"></i> Transferencias a la CUT (RDR)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 380px;">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Código</th>
                                    <th>Fecha</th>
                                    <th>Actividad</th>
                                    <th>N° Operación</th>
                                    <th class="text-end">Monto</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cutTransfers as $cut)
                                    <tr>
                                        <td class="font-monospace fw-bold text-primary">{{ $cut->transfer_code }}</td>
                                        <td class="small">{{ $cut->transfer_date->format('d/m/Y') }}</td>
                                        <td class="small">{{ $cut->activity?->code }}</td>
                                        <td class="font-monospace small">{{ $cut->bank_operation_number }}</td>
                                        <td class="text-end font-monospace fw-bold text-primary">S/ {{ number_format($cut->amount, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-success-soft text-success border border-success">{{ $cut->status }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted small">No hay transferencias a la CUT registradas en este período.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Habilitaciones y Préstamos Internos -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-hand-holding-usd me-1"></i> Préstamos Internos / Habilitaciones</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 380px;">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Código</th>
                                    <th>Beneficiario</th>
                                    <th>Motivo</th>
                                    <th class="text-end">Prestado</th>
                                    <th class="text-end">Pendiente</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($internalLoans as $loan)
                                    <tr>
                                        <td class="font-monospace fw-bold">{{ $loan->loan_code }}</td>
                                        <td class="small">{{ $loan->beneficiaryUser?->nombres }}</td>
                                        <td class="small text-truncate" style="max-width: 130px;" title="{{ $loan->loan_reason }}">{{ $loan->loan_reason }}</td>
                                        <td class="text-end font-monospace">S/ {{ number_format($loan->amount_lent, 2) }}</td>
                                        <td class="text-end font-monospace fw-bold {{ $loan->pending_balance > 0 ? 'text-danger' : 'text-success' }}">
                                            S/ {{ number_format($loan->pending_balance, 2) }}
                                        </td>
                                        <td class="text-center">
                                            @if($loan->status !== 'FULLY_REPAID')
                                                <button type="button" class="btn btn-xs btn-outline-success btn-repay-loan"
                                                        data-id="{{ $loan->id }}"
                                                        data-code="{{ $loan->loan_code }}"
                                                        data-pending="{{ $loan->pending_balance }}">
                                                    Rendir
                                                </button>
                                            @else
                                                <span class="badge bg-success text-white">Liquidado</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted small">No hay préstamos o habilitaciones pendientes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Cierres de Período y Aprobación Institucional -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary">
                <i class="fas fa-lock me-2"></i> Rendiciones y Cierres Mensuales de Actividad (Sujetos a Cadena de Firmas)
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Código Cierre</th>
                            <th>Actividad Productiva</th>
                            <th class="text-center">Mes / Año</th>
                            <th class="text-end">Total Ingresos</th>
                            <th class="text-end">Total Egresos</th>
                            <th class="text-end fw-bold">Saldo Neto</th>
                            <th class="text-center">Estado Firma</th>
                            <th class="text-center" style="width: 140px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($periodClosures as $closure)
                            <tr>
                                <td class="font-monospace fw-bold">{{ $closure->closure_code }}</td>
                                <td>{{ $closure->activity?->name }} ({{ $closure->activity?->code }})</td>
                                <td class="text-center font-monospace">{{ sprintf('%02d', $closure->period_month) }}/{{ $closure->period_year }}</td>
                                <td class="text-end text-success font-monospace">S/ {{ number_format($closure->total_income, 2) }}</td>
                                <td class="text-end text-danger font-monospace">S/ {{ number_format($closure->total_expense, 2) }}</td>
                                <td class="text-end font-monospace fw-bold {{ $closure->net_balance >= 0 ? 'text-primary' : 'text-danger' }}">
                                    S/ {{ number_format($closure->net_balance, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($closure->isFullyApproved())
                                        <span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i> Aprobado</span>
                                    @elseif($closure->approvals()->exists())
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> En Revisión</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Borrador</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(!$closure->approvals()->exists())
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-submit-closure" data-id="{{ $closure->id }}">
                                            <i class="fas fa-file-signature me-1"></i> Enviar a Firma
                                        </button>
                                    @else
                                        <span class="text-muted small">En proceso</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No se han generado cierres de período para el año {{ $selectedYear }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Transferencia a la CUT -->
<div class="modal fade" id="newCutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title h6 mb-0"><i class="fas fa-university me-2"></i> Registrar Transferencia a la CUT</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cutTransferForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Actividad Productiva <span class="text-danger">*</span></label>
                        <select class="form-select" name="productive_activity_id" required>
                            <option value="">-- Seleccionar Actividad --</option>
                            @foreach($activities as $act)
                                <option value="{{ $act->id }}">{{ $act->code }} - {{ $act->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Cuenta de Origen de Fondos <span class="text-danger">*</span></label>
                        <select class="form-select" name="source_fund_id" required>
                            <option value="">-- Seleccionar Fuente --</option>
                            @foreach($fundSources as $fund)
                                <option value="{{ $fund->id }}">{{ $fund->name }} (Saldo: S/ {{ number_format($fund->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Cuenta Única del Tesoro (CUT) Destino <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="cut_account_number" value="CUT-BN-0000-5412984-RDR" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Monto (S/) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control font-monospace" name="amount" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Fecha de Depósito <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="transfer_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">N° de Operación Bancaria / Depósito <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-monospace" name="bank_operation_number" placeholder="p. ej. OP-982341" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold">Notas / Justificación</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Observaciones de la transferencia..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3" id="btnSaveCut">
                        <i class="fas fa-save me-1"></i> Guardar Transferencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Préstamo / Habilitación Interna -->
<div class="modal fade" id="newLoanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title h6 mb-0"><i class="fas fa-hand-holding-usd me-2"></i> Registrar Habilitación / Préstamo Interno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="internalLoanForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Actividad Productiva <span class="text-danger">*</span></label>
                        <select class="form-select" name="productive_activity_id" required>
                            <option value="">-- Seleccionar Actividad --</option>
                            @foreach($activities as $act)
                                <option value="{{ $act->id }}">{{ $act->code }} - {{ $act->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Fuente de Fondos <span class="text-danger">*</span></label>
                        <select class="form-select" name="source_fund_id" required>
                            <option value="">-- Seleccionar Fuente --</option>
                            @foreach($fundSources as $fund)
                                <option value="{{ $fund->id }}">{{ $fund->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Beneficiario / Servidor <span class="text-danger">*</span></label>
                        <select class="form-select" name="beneficiary_user_id" required>
                            <option value="">-- Seleccionar Usuario --</option>
                            @foreach(\App\Models\User::orderBy('nombres')->get() as $u)
                                <option value="{{ $u->id }}">{{ $u->nombres }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Motivo del Préstamo / Habilitación <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="loan_reason" placeholder="p. ej. Viáticos comisión Fundo Gavilán" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label fw-bold">Monto (S/) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control font-monospace" name="amount_lent" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold">Fecha Entrega <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="issue_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold">Límite Rendición <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-sm px-3" id="btnSaveLoan">
                        <i class="fas fa-save me-1"></i> Registrar Préstamo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Generar Cierre de Período -->
<div class="modal fade" id="newPeriodClosureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title h6 mb-0"><i class="fas fa-lock me-2"></i> Generar Cierre Contable de Período</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="periodClosureForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Actividad Productiva <span class="text-danger">*</span></label>
                        <select class="form-select" name="productive_activity_id" required>
                            <option value="">-- Seleccionar Actividad --</option>
                            @foreach($activities as $act)
                                <option value="{{ $act->id }}">{{ $act->code }} - {{ $act->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Año <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="period_year" value="{{ $selectedYear }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Mes <span class="text-danger">*</span></label>
                            <select class="form-select" name="period_month" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold">Notas de Cierre</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Observaciones o informe del cierre..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark btn-sm px-3" id="btnSaveClosure">
                        <i class="fas fa-calculator me-1"></i> Consolidar y Cerrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Saldo de Extracto Bancario -->
<div class="modal fade" id="reconciliationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title h6 mb-0"><i class="fas fa-file-invoice-dollar me-2"></i> Registrar Saldo de Extracto Bancario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reconciliationForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Fuente de Fondos / Cuenta <span class="text-danger">*</span></label>
                        <select class="form-select" name="fund_source_id" required>
                            <option value="">-- Seleccionar Fuente --</option>
                            @foreach($fundSources as $fund)
                                <option value="{{ $fund->id }}">{{ $fund->name }} ({{ $fund->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Año</label>
                            <input type="number" class="form-control" name="period_year" value="{{ $selectedYear }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Mes</label>
                            <select class="form-select" name="period_month" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>{{ $m }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Saldo Declarado (S/) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control font-monospace fw-bold" name="bank_statement_balance" placeholder="0.00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Fecha Corte Extracto <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="statement_closing_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold">Notas de Conciliación</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Anotaciones sobre cheques en tránsito o depósitos pendientes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-secondary btn-sm px-3" id="btnSaveRec">
                        <i class="fas fa-save me-1"></i> Guardar Conciliación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Guardar CUT
    $('#cutTransferForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSaveCut');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

        $.ajax({
            url: '{{ route("productive_activities.rdr.cut_transfers.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                Swal.fire('¡Éxito!', resp.message, 'success').then(() => location.reload());
            },
            error: function(err) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Transferencia');
                let msg = err.responseJSON ? err.responseJSON.message : 'Error al registrar la transferencia.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // Guardar Préstamo
    $('#internalLoanForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSaveLoan');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

        $.ajax({
            url: '{{ route("productive_activities.rdr.internal_loans.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                Swal.fire('¡Éxito!', resp.message, 'success').then(() => location.reload());
            },
            error: function(err) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Registrar Préstamo');
                let msg = err.responseJSON ? err.responseJSON.message : 'Error al registrar el préstamo.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // Rendir Préstamo
    $(document).on('click', '.btn-repay-loan', function() {
        let id = $(this).data('id');
        let code = $(this).data('code');
        let pending = $(this).data('pending');

        Swal.fire({
            title: 'Rendir Préstamo ' + code,
            html: `
                <div class="mb-3 text-start">
                    <label class="form-label small fw-bold">Monto a Devolver / Rendir (Saldo: S/ ${pending}):</label>
                    <input type="number" step="0.01" id="swalRepayAmount" class="form-control" value="${pending}">
                </div>
                <div class="mb-2 text-start">
                    <label class="form-label small fw-bold">Referencia / N° Recibo:</label>
                    <input type="text" id="swalRepayRef" class="form-control" placeholder="p. ej. DJ-004 o Recibo de Caja">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Registrar Rendición',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                let amount = $('#swalRepayAmount').val();
                let ref = $('#swalRepayRef').val();
                if (!amount || amount <= 0) {
                    Swal.showValidationMessage('Ingrese un monto válido');
                    return false;
                }
                if (!ref) {
                    Swal.showValidationMessage('Ingrese una referencia');
                    return false;
                }
                return { amount: amount, ref: ref };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("productive-activities/rdr/internal-loans") }}/' + id + '/repay',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        repay_amount: result.value.amount,
                        repayment_reference: result.value.ref
                    },
                    success: function(resp) {
                        Swal.fire('¡Registrado!', resp.message, 'success').then(() => location.reload());
                    },
                    error: function(err) {
                        Swal.fire('Error', 'No se pudo registrar la rendición.', 'error');
                    }
                });
            }
        });
    });

    // Guardar Cierre
    $('#periodClosureForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSaveClosure');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Cerrando...');

        $.ajax({
            url: '{{ route("productive_activities.rdr.period_closures.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                Swal.fire('¡Cierre Consolidado!', resp.message, 'success').then(() => location.reload());
            },
            error: function(err) {
                btn.prop('disabled', false).html('<i class="fas fa-calculator me-1"></i> Consolidar y Cerrar');
                let msg = err.responseJSON ? err.responseJSON.message : 'Error al generar el cierre.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // Enviar Cierre a Firma
    $(document).on('click', '.btn-submit-closure', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: '¿Enviar Cierre a Cadena de Firmas?',
            text: 'Se iniciará el flujo con Contabilidad, Administración y Dirección General.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0061f2',
            confirmButtonText: 'Sí, enviar a firma',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("productive-activities/rdr/period-closures") }}/' + id + '/submit-approval',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(resp) {
                        Swal.fire('¡Remitido!', resp.message, 'success').then(() => location.reload());
                    },
                    error: function(err) {
                        let msg = err.responseJSON ? err.responseJSON.message : 'Error al enviar a aprobación.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    });

    // Guardar Conciliación
    $('#reconciliationForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSaveRec');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

        $.ajax({
            url: '{{ route("productive_activities.rdr.reconciliations.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                Swal.fire('¡Guardado!', resp.message, 'success').then(() => location.reload());
            },
            error: function(err) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar Conciliación');
                let msg = err.responseJSON ? err.responseJSON.message : 'Error al guardar la conciliación.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });
});
</script>
@endpush
