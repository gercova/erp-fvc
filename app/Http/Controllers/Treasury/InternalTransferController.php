<?php

namespace App\Http\Controllers\Treasury;

use App\Http\Controllers\Controller;
use App\Models\ArchingCash;
use App\Models\BankAccount;
use App\Models\FundSource;
use App\Models\InternalTransfer;
use App\Services\Treasury\InternalTransferService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InternalTransferController extends Controller
{
    public function __construct(
        protected InternalTransferService $transferService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $transfers = InternalTransfer::with([
            'sourceBankAccount',
            'destinationBankAccount',
            'destinationFundSource',
            'sourceArchingCash',
            'journalEntry',
            'createdByUser',
        ])
        ->orderBy('transfer_date', 'desc')
        ->orderBy('id', 'desc')
        ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success'   => true,
                'transfers' => $transfers,
            ]);
        }

        $bankAccounts = BankAccount::where('is_active', true)->get();
        $cutFunds = FundSource::where('is_active', true)->where('is_cut', true)->get();
        $archingCashes = ArchingCash::orderBy('id', 'desc')->limit(20)->get();

        return view('admin.treasury.transfers.index', compact('transfers', 'bankAccounts', 'cutFunds', 'archingCashes'));
    }

    public function storeBankToBank(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_bank_account_id'      => 'required|exists:bank_accounts,id',
            'destination_bank_account_id' => 'required|exists:bank_accounts,id|different:source_bank_account_id',
            'amount'                      => 'required|numeric|min:0.01',
            'transfer_date'               => 'required|date',
            'concept'                     => 'required|string|max:255',
            'reference_number'            => 'nullable|string|max:50',
        ]);

        $source = BankAccount::findOrFail($validated['source_bank_account_id']);
        $destination = BankAccount::findOrFail($validated['destination_bank_account_id']);

        try {
            $transfer = $this->transferService->transferBetweenBankAccounts(
                $source,
                $destination,
                (float) $validated['amount'],
                $validated['transfer_date'],
                $validated['concept'],
                $validated['reference_number'] ?? null,
                Auth::id() ?? 1
            );

            return response()->json([
                'success'  => true,
                'message'  => 'Transferencia entre cuentas bancarias registrada y contabilizada con éxito.',
                'transfer' => $transfer,
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function storeCashToBank(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_arching_cash_id'      => 'required|exists:arching_cashes,id',
            'destination_bank_account_id' => 'required|exists:bank_accounts,id',
            'amount'                      => 'required|numeric|min:0.01',
            'transfer_date'               => 'required|date',
            'concept'                     => 'required|string|max:255',
            'reference_number'            => 'nullable|string|max:50',
        ]);

        $cash = ArchingCash::findOrFail($validated['source_arching_cash_id']);
        $bank = BankAccount::findOrFail($validated['destination_bank_account_id']);

        try {
            $transfer = $this->transferService->depositCashToBank(
                $cash,
                $bank,
                (float) $validated['amount'],
                $validated['transfer_date'],
                $validated['concept'],
                $validated['reference_number'] ?? null,
                Auth::id() ?? 1
            );

            return response()->json([
                'success'  => true,
                'message'  => 'Depósito de arqueo de caja a cuenta bancaria registrado y contabilizado con éxito.',
                'transfer' => $transfer,
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function storeTransferToCut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_bank_account_id'      => 'required|exists:bank_accounts,id',
            'destination_fund_source_id' => 'required|exists:fund_sources,id',
            'amount'                      => 'required|numeric|min:0.01',
            'transfer_date'               => 'required|date',
            'concept'                     => 'required|string|max:255',
            'reference_number'            => 'nullable|string|max:50',
        ]);

        $bank = BankAccount::findOrFail($validated['source_bank_account_id']);
        $cutFund = FundSource::findOrFail($validated['destination_fund_source_id']);

        try {
            $transfer = $this->transferService->transferToCut(
                $bank,
                $cutFund,
                (float) $validated['amount'],
                $validated['transfer_date'],
                $validated['concept'],
                $validated['reference_number'] ?? null,
                Auth::id() ?? 1
            );

            return response()->json([
                'success'  => true,
                'message'  => 'Transferencia a la Cuenta Única del Tesoro (CUT) registrada y contabilizada con éxito.',
                'transfer' => $transfer,
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
