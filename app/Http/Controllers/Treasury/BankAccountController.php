<?php

namespace App\Http\Controllers\Treasury;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\FundSource;
use App\Services\Treasury\BankStatementImportService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function __construct(
        protected BankStatementImportService $importService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $accounts = BankAccount::with(['fundSource', 'accountingAccount'])
            ->withCount(['movements', 'reconciliations'])
            ->orderBy('id', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'accounts' => $accounts,
            ]);
        }

        $fundSources = FundSource::where('is_active', true)->get();
        // 104x accounts
        $accountingAccounts = ChartOfAccount::where('code', 'like', '104%')
            ->where('accepts_movements', true)
            ->get();

        return view('admin.treasury.bank_accounts.index', compact('accounts', 'fundSources', 'accountingAccounts'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fund_source_id'        => 'required|exists:fund_sources,id|unique:bank_accounts,fund_source_id',
            'account_number'        => 'required|string|max:50',
            'cci'                   => 'nullable|string|max:50',
            'bank_name'             => 'required|string|max:100',
            'account_type'          => 'required|string|in:CURRENT,SAVINGS,CUT,COLLECTION',
            'currency'              => 'nullable|string|size:3',
            'accounting_account_id' => 'required|exists:chart_of_accounts,id',
            'initial_balance'       => 'nullable|numeric|min:0',
        ]);

        $initial = (float) ($validated['initial_balance'] ?? 0.00);

        $account = BankAccount::create([
            'uuid'                  => (string) Str::uuid(),
            'fund_source_id'        => $validated['fund_source_id'],
            'account_number'        => $validated['account_number'],
            'cci'                   => $validated['cci'] ?? null,
            'bank_name'             => $validated['bank_name'],
            'account_type'          => $validated['account_type'],
            'currency'              => $validated['currency'] ?? 'PEN',
            'accounting_account_id' => $validated['accounting_account_id'],
            'initial_balance'       => $initial,
            'current_balance'       => $initial,
            'is_active'             => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta bancaria registrada con éxito.',
            'account' => $account->load(['fundSource', 'accountingAccount']),
        ], 201);
    }

    public function show(int $id, Request $request): View|JsonResponse
    {
        $account = BankAccount::with(['fundSource', 'accountingAccount', 'movements' => function ($q) {
            $q->orderBy('movement_date', 'desc')->limit(100);
        }])->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'account' => $account,
            ]);
        }

        return view('admin.treasury.bank_accounts.show', compact('account'));
    }

    public function importStatement(int $id, Request $request): JsonResponse
    {
        $account = BankAccount::findOrFail($id);

        $request->validate([
            'file' => 'nullable|file|mimes:csv,txt,xlsx,xls|max:5120',
            'rows' => 'nullable|array',
        ]);

        $data = $request->hasFile('file') ? $request->file('file') : $request->input('rows');
        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Debe adjuntar un archivo CSV/Excel o un array de filas para importar.',
            ], 422);
        }

        try {
            $result = $this->importService->import($account, $data);
            return response()->json([
                'success' => true,
                'message' => "Importación completada: {$result['imported_count']} movimientos registrados, {$result['duplicates_skipped']} duplicados omitidos.",
                'summary' => $result,
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function downloadTemplate(): Response
    {
        $csv = $this->importService->generateTemplateCsv();
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="plantilla_extracto_bancario.csv"',
        ]);
    }
}
