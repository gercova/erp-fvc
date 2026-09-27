<?php

namespace App\Http\Controllers;

use App\Http\Requests\RdrCutTransferValidate;
use App\Http\Requests\RdrInternalLoanValidate;
use App\Models\ActivityPeriodClosure;
use App\Models\ActivityTransaction;
use App\Models\FundSource;
use App\Models\ProductiveActivity;
use App\Models\RdrBankReconciliation;
use App\Models\RdrCutTransfer;
use App\Models\RdrInternalLoan;
use App\Services\DocumentApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RdrModuleController extends Controller
{
    protected DocumentApprovalService $approvalService;

    public function __construct(DocumentApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function index(Request $request): View
    {
        $activities = ProductiveActivity::orderBy('name')->get();
        $fundSources = FundSource::where('is_active', true)->orderBy('name')->get();

        $selectedYear = (int) $request->input('year', date('Y'));
        $selectedMonth = (int) $request->input('month', date('n'));

        // 1. Transferencias al CUT
        $cutTransfers = RdrCutTransfer::with(['activity', 'sourceFund', 'requestedByUser'])
            ->whereYear('transfer_date', $selectedYear)
            ->latest('transfer_date')
            ->get();

        // 2. Préstamos Internos / Habilitaciones
        $internalLoans = RdrInternalLoan::with(['activity', 'sourceFund', 'beneficiaryUser', 'authorizedByUser'])
            ->latest('issue_date')
            ->get();

        // 3. Saldos Bancarios y Conciliaciones
        $reconciliations = RdrBankReconciliation::with(['fundSource', 'reconciledByUser'])
            ->where('period_year', $selectedYear)
            ->get();

        // 4. Cierres de Período para Aprobación
        $periodClosures = ActivityPeriodClosure::with(['activity', 'closedByUser', 'approvals'])
            ->where('period_year', $selectedYear)
            ->orderByDesc('period_month')
            ->get();

        // Cálculos de conciliación por cada fuente de fondos
        $fundReconciliationReport = [];
        foreach ($fundSources as $fund) {
            $totalIn = ActivityTransaction::where('fund_source_id', $fund->id)
                ->where('period_year', $selectedYear)
                ->where('transaction_type', 'INCOME')
                ->where('status', '!=', 'ANULLED')
                ->sum('amount');

            $totalOut = ActivityTransaction::where('fund_source_id', $fund->id)
                ->where('period_year', $selectedYear)
                ->where('transaction_type', 'EXPENSE')
                ->where('status', '!=', 'ANULLED')
                ->sum('amount');

            $netCalculated = (float) ($fund->initial_balance + $totalIn - $totalOut);
            $latestRec = $reconciliations->where('fund_source_id', $fund->id)->sortByDesc('statement_closing_date')->first();

            $bankDeclared = $latestRec ? (float) $latestRec->bank_statement_balance : (float) $fund->current_balance;
            $diff = $bankDeclared - $netCalculated;

            $fundReconciliationReport[] = [
                'fund' => $fund,
                'initial_balance' => (float) $fund->initial_balance,
                'total_income' => (float) $totalIn,
                'total_expense' => (float) $totalOut,
                'system_calculated' => $netCalculated,
                'bank_declared' => $bankDeclared,
                'difference' => $diff,
                'status' => abs($diff) < 0.01 ? 'CONCILIADO' : 'CON_DIFERENCIA',
                'latest_reconciliation' => $latestRec,
            ];
        }

        return view('admin.productive_activities.rdr.index', compact(
            'activities',
            'fundSources',
            'selectedYear',
            'selectedMonth',
            'cutTransfers',
            'internalLoans',
            'reconciliations',
            'periodClosures',
            'fundReconciliationReport'
        ));
    }

    public function storeCutTransfer(RdrCutTransferValidate $request): JsonResponse
    {
        $data = $request->validated();
        $data['requested_by_user_id'] = Auth::id();
        $data['status'] = 'APPROVED'; // En entorno operativo se aprueba directamente o se envía a firma

        $cut = RdrCutTransfer::create($data);

        // Descontar del saldo del fondo origen
        $fund = FundSource::find($data['source_fund_id']);
        if ($fund) {
            $fund->decrement('current_balance', $data['amount']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transferencia a la CUT registrada exitosamente: ' . $cut->transfer_code,
            'transfer' => $cut->load(['activity', 'sourceFund']),
        ]);
    }

    public function storeInternalLoan(RdrInternalLoanValidate $request): JsonResponse
    {
        $data = $request->validated();
        $data['authorized_by_user_id'] = Auth::id();
        $data['status'] = 'PENDING';

        $loan = RdrInternalLoan::create($data);

        // Descontar del saldo temporal del fondo
        $fund = FundSource::find($data['source_fund_id']);
        if ($fund) {
            $fund->decrement('current_balance', $data['amount_lent']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Habilitación de fondos / Préstamo interno registrado: ' . $loan->loan_code,
            'loan' => $loan->load(['activity', 'sourceFund', 'beneficiaryUser']),
        ]);
    }

    public function repayInternalLoan(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'repay_amount' => 'required|numeric|min:0.01',
            'repayment_reference' => 'required|string|max:100',
        ]);

        $loan = RdrInternalLoan::findOrFail($id);
        $repay = (float) $request->repay_amount;

        $newRepaid = (float) $loan->amount_repaid + $repay;
        $status = $newRepaid >= (float) $loan->amount_lent ? 'FULLY_REPAID' : 'PARTIALLY_REPAID';

        $loan->update([
            'amount_repaid' => $newRepaid,
            'status' => $status,
            'repayment_reference' => $request->repayment_reference,
        ]);

        // Reingresar el dinero al fondo origen
        $fund = FundSource::find($loan->source_fund_id);
        if ($fund) {
            $fund->increment('current_balance', $repay);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rendición / Devolución registrada correctamente.',
            'loan' => $loan,
        ]);
    }

    public function storeReconciliation(Request $request): JsonResponse
    {
        $request->validate([
            'fund_source_id' => 'required|exists:fund_sources,id',
            'period_year' => 'required|integer',
            'period_month' => 'required|integer|min:1|max:12',
            'statement_closing_date' => 'required|date',
            'bank_statement_balance' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $fund = FundSource::findOrFail($request->fund_source_id);

        // Calcular saldo del sistema para el período
        $totalIn = ActivityTransaction::where('fund_source_id', $fund->id)
            ->where('period_year', $request->period_year)
            ->where('period_month', '<=', $request->period_month)
            ->where('transaction_type', 'INCOME')
            ->where('status', '!=', 'ANULLED')
            ->sum('amount');

        $totalOut = ActivityTransaction::where('fund_source_id', $fund->id)
            ->where('period_year', $request->period_year)
            ->where('period_month', '<=', $request->period_month)
            ->where('transaction_type', 'EXPENSE')
            ->where('status', '!=', 'ANULLED')
            ->sum('amount');

        $systemBalance = (float) ($fund->initial_balance + $totalIn - $totalOut);
        $bankBalance = (float) $request->bank_statement_balance;
        $diff = $bankBalance - $systemBalance;
        $status = abs($diff) < 0.01 ? 'BALANCED' : 'DISCREPANCY';

        $rec = RdrBankReconciliation::updateOrCreate(
            [
                'fund_source_id' => $fund->id,
                'period_year' => $request->period_year,
                'period_month' => $request->period_month,
            ],
            [
                'statement_closing_date' => $request->statement_closing_date,
                'bank_statement_balance' => $bankBalance,
                'system_calculated_balance' => $systemBalance,
                'reconciled_difference' => $diff,
                'status' => $status,
                'reconciled_by_user_id' => Auth::id(),
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Conciliación bancaria guardada con éxito.',
            'reconciliation' => $rec->load('fundSource'),
        ]);
    }

    public function storePeriodClosure(Request $request): JsonResponse
    {
        $request->validate([
            'productive_activity_id' => 'required|exists:productive_activities,id',
            'period_year' => 'required|integer',
            'period_month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string',
        ]);

        $actId = $request->productive_activity_id;
        $year = $request->period_year;
        $month = $request->period_month;

        $incomes = ActivityTransaction::where('productive_activity_id', $actId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('transaction_type', 'INCOME')
            ->where('status', '!=', 'ANULLED')
            ->sum('amount');

        $expenses = ActivityTransaction::where('productive_activity_id', $actId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('transaction_type', 'EXPENSE')
            ->where('status', '!=', 'ANULLED')
            ->sum('amount');

        $net = $incomes - $expenses;

        // Desglose por fuente
        $bnFund = FundSource::where('code', 'BN')->first();
        $coopFund = FundSource::where('code', 'COOP_TOCACHE')->first();
        $cashFund = FundSource::where('code', 'CAJA_CHICA_INST')->first();

        $bnIn = $bnFund ? ActivityTransaction::where('productive_activity_id', $actId)->where('period_year', $year)->where('period_month', $month)->where('fund_source_id', $bnFund->id)->where('transaction_type', 'INCOME')->sum('amount') : 0;
        $bnOut = $bnFund ? ActivityTransaction::where('productive_activity_id', $actId)->where('period_year', $year)->where('period_month', $month)->where('fund_source_id', $bnFund->id)->where('transaction_type', 'EXPENSE')->sum('amount') : 0;

        $coopIn = $coopFund ? ActivityTransaction::where('productive_activity_id', $actId)->where('period_year', $year)->where('period_month', $month)->where('fund_source_id', $coopFund->id)->where('transaction_type', 'INCOME')->sum('amount') : 0;
        $coopOut = $coopFund ? ActivityTransaction::where('productive_activity_id', $actId)->where('period_year', $year)->where('period_month', $month)->where('fund_source_id', $coopFund->id)->where('transaction_type', 'EXPENSE')->sum('amount') : 0;

        $cashIn = $cashFund ? ActivityTransaction::where('productive_activity_id', $actId)->where('period_year', $year)->where('period_month', $month)->where('fund_source_id', $cashFund->id)->where('transaction_type', 'INCOME')->sum('amount') : 0;
        $cashOut = $cashFund ? ActivityTransaction::where('productive_activity_id', $actId)->where('period_year', $year)->where('period_month', $month)->where('fund_source_id', $cashFund->id)->where('transaction_type', 'EXPENSE')->sum('amount') : 0;

        $closure = ActivityPeriodClosure::updateOrCreate(
            [
                'productive_activity_id' => $actId,
                'period_year' => $year,
                'period_month' => $month,
            ],
            [
                'total_income' => $incomes,
                'total_expense' => $expenses,
                'net_balance' => $net,
                'bn_balance' => $bnIn - $bnOut,
                'coop_balance' => $coopIn - $coopOut,
                'cash_balance' => $cashIn - $cashOut,
                'approval_status' => 'BORRADOR',
                'closed_by_user_id' => Auth::id(),
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Cierre de período generado correctamente.',
            'closure' => $closure->load(['activity', 'closedByUser']),
        ]);
    }

    public function submitClosureApproval(Request $request, int $id): JsonResponse
    {
        $closure = ActivityPeriodClosure::findOrFail($id);

        if ($closure->approvals()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Este cierre de período ya cuenta con una cadena de firmas en trámite.'
            ], 422);
        }

        $closure->update(['approval_status' => 'EN_REVISION']);
        $this->approvalService->generateWorkflow($closure, Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'El cierre de período ha sido remitido exitosamente a la cadena de aprobación institucional.'
        ]);
    }
}
