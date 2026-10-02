<?php

namespace App\Http\Controllers\Treasury;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\JournalEntry;
use App\Models\RdrBankReconciliation;
use App\Services\Treasury\BankReconciliationService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BankReconciliationController extends Controller
{
    public function __construct(
        protected BankReconciliationService $reconciliationService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $reconciliations = RdrBankReconciliation::with(['bankAccount', 'fundSource', 'reconciledByUser', 'approvedByUser'])
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success'         => true,
                'reconciliations' => $reconciliations,
            ]);
        }

        $bankAccounts = BankAccount::where('is_active', true)->get();

        return view('admin.treasury.reconciliations.index', compact('reconciliations', 'bankAccounts'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_account_id'        => 'required|exists:bank_accounts,id',
            'period_year'            => 'required|integer|min:2020|max:2035',
            'period_month'           => 'required|integer|min:1|max:12',
            'bank_statement_balance' => 'required|numeric',
            'statement_closing_date' => 'nullable|date',
            'notes'                  => 'nullable|string',
        ]);

        $account = BankAccount::findOrFail($validated['bank_account_id']);

        $rec = $this->reconciliationService->initiateReconciliation(
            $account,
            (int) $validated['period_year'],
            (int) $validated['period_month'],
            (float) $validated['bank_statement_balance'],
            $validated['statement_closing_date'] ?? null,
            Auth::id() ?? 1,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success'        => true,
            'message'        => 'Conciliación bancaria iniciada con éxito.',
            'reconciliation' => $rec,
        ], 201);
    }

    public function show(int $id, Request $request): View|JsonResponse
    {
        $reconciliation = RdrBankReconciliation::with([
            'bankAccount.accountingAccount',
            'fundSource',
            'items.bankMovement',
            'items.journalEntry',
            'reconciledByUser',
            'approvedByUser',
        ])->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'reconciliation' => $reconciliation,
            ]);
        }

        return view('admin.treasury.reconciliations.show', compact('reconciliation'));
    }

    public function suggestions(int $id, Request $request): JsonResponse
    {
        $reconciliation = RdrBankReconciliation::with('bankAccount')->findOrFail($id);
        $dayTolerance = (int) $request->input('tolerance', 3);

        $suggestions = $this->reconciliationService->getMatchSuggestions($reconciliation, $dayTolerance);

        return response()->json([
            'success'     => true,
            'count'       => $suggestions->count(),
            'suggestions' => $suggestions,
        ]);
    }

    public function match(int $id, Request $request): JsonResponse
    {
        $reconciliation = RdrBankReconciliation::findOrFail($id);

        $validated = $request->validate([
            'bank_movement_id' => 'required|exists:bank_movements,id',
            'journal_entry_id' => 'required|exists:journal_entries,id',
        ]);

        $movement = BankMovement::findOrFail($validated['bank_movement_id']);
        $entry = JournalEntry::findOrFail($validated['journal_entry_id']);

        $item = $this->reconciliationService->matchMovement($reconciliation, $movement, $entry);

        return response()->json([
            'success'        => true,
            'message'        => 'Movimiento conciliado con éxito.',
            'item'           => $item,
            'reconciliation' => $reconciliation->fresh(),
        ]);
    }

    public function addItem(int $id, Request $request): JsonResponse
    {
        $reconciliation = RdrBankReconciliation::findOrFail($id);

        $validated = $request->validate([
            'item_type'        => 'required|string|in:MATCHED,DEPOSIT_IN_TRANSIT,OUTSTANDING_CHECK,BANK_CHARGE,BANK_CREDIT,ERROR',
            'amount'           => 'required|numeric|min:0.01',
            'reference'        => 'nullable|string|max:100',
            'concept'          => 'nullable|string|max:255',
            'bank_movement_id' => 'nullable|exists:bank_movements,id',
            'journal_entry_id' => 'nullable|exists:journal_entries,id',
            'notes'            => 'nullable|string',
        ]);

        $item = $this->reconciliationService->addReconcilingItem(
            $reconciliation,
            $validated['item_type'],
            (float) $validated['amount'],
            $validated['reference'] ?? null,
            $validated['concept'] ?? null,
            $validated['bank_movement_id'] ?? null,
            $validated['journal_entry_id'] ?? null,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success'        => true,
            'message'        => 'Partida conciliatoria registrada.',
            'item'           => $item,
            'reconciliation' => $reconciliation->fresh(),
        ]);
    }

    public function postAdjustment(int $id, Request $request): JsonResponse
    {
        $reconciliation = RdrBankReconciliation::findOrFail($id);

        $validated = $request->validate([
            'amount'               => 'required|numeric|min:0.01',
            'concept'              => 'required|string|max:255',
            'expense_account_code' => 'nullable|string',
            'reference'            => 'nullable|string|max:50',
            'item_id'              => 'nullable|exists:bank_reconciliation_items,id',
        ]);

        $reconcilingItem = null;
        if (!empty($validated['item_id'])) {
            $reconcilingItem = BankReconciliationItem::find($validated['item_id']);
        }

        try {
            $entry = $this->reconciliationService->postBankChargeAdjustment(
                $reconciliation,
                (float) $validated['amount'],
                $validated['concept'],
                $validated['expense_account_code'] ?? '6391',
                $validated['reference'] ?? null,
                $reconcilingItem
            );

            return response()->json([
                'success'        => true,
                'message'        => "Asiento de ajuste {$entry->entry_number} generado exitosamente.",
                'journal_entry'  => $entry,
                'reconciliation' => $reconciliation->fresh(),
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function close(int $id, Request $request): JsonResponse
    {
        $reconciliation = RdrBankReconciliation::findOrFail($id);

        try {
            $closed = $this->reconciliationService->closeReconciliation($reconciliation, Auth::id() ?? 1);

            return response()->json([
                'success'        => true,
                'message'        => 'Conciliación bancaria cerrada con éxito. Saldo extracto igual a saldo contable tras partidas conciliatorias.',
                'reconciliation' => $closed,
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
