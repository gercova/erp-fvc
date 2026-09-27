<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityTransactionValidate;
use App\Models\ActivityTransaction;
use App\Models\ActivityTransactionCategory;
use App\Models\Billing;
use App\Models\Buy;
use App\Models\FundSource;
use App\Models\ProductiveActivity;
use App\Models\ProductiveUnit;
use App\Models\SaleNote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ActivityTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $activities = ProductiveActivity::orderBy('name')->get();
        $categories = ActivityTransactionCategory::with('parent')->where('is_active', true)->orderBy('code')->get();
        $fundSources = FundSource::where('is_active', true)->orderBy('name')->get();

        $selectedActivityId = $request->input('activity_id');
        $selectedType = $request->input('type'); // INCOME, EXPENSE, or null
        $selectedYear = $request->input('year', date('Y'));

        // Métricas de transacciones para el filtro
        $baseQuery = ActivityTransaction::where('period_year', $selectedYear)->where('status', '!=', 'ANULLED');
        if ($selectedActivityId) {
            $baseQuery->where('productive_activity_id', $selectedActivityId);
        }

        $totalIncome = (clone $baseQuery)->where('transaction_type', 'INCOME')->sum('amount');
        $totalExpense = (clone $baseQuery)->where('transaction_type', 'EXPENSE')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        return view('admin.productive_activities.transactions.index', compact(
            'activities',
            'categories',
            'fundSources',
            'selectedActivityId',
            'selectedType',
            'selectedYear',
            'totalIncome',
            'totalExpense',
            'netBalance'
        ));
    }

    public function get(Request $request): JsonResponse
    {
        $query = ActivityTransaction::with([
            'activity',
            'productiveUnit',
            'category',
            'fundSource',
            'registeredByUser',
            'saleNote',
            'billing',
            'buy'
        ]);

        if ($request->filled('productive_activity_id')) {
            $query->where('productive_activity_id', $request->productive_activity_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('fund_source_id')) {
            $query->where('fund_source_id', $request->fund_source_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('year')) {
            $query->where('period_year', $request->year);
        }

        if ($request->filled('month')) {
            $query->where('period_month', $request->month);
        }

        return DataTables::of($query)
            ->addColumn('type_badge', function (ActivityTransaction $trx) {
                return $trx->transaction_type === 'INCOME'
                    ? '<span class="badge bg-success text-white"><i class="fas fa-arrow-down me-1"></i> Ingreso</span>'
                    : '<span class="badge bg-danger text-white"><i class="fas fa-arrow-up me-1"></i> Egreso</span>';
            })
            ->addColumn('formatted_amount', function (ActivityTransaction $trx) {
                $color = $trx->transaction_type === 'INCOME' ? 'text-success' : 'text-danger';
                $prefix = $trx->transaction_type === 'INCOME' ? '+' : '-';
                return '<span class="fw-bold ' . $color . '">' . $prefix . ' S/ ' . number_format($trx->amount, 2) . '</span>';
            })
            ->addColumn('period_display', function (ActivityTransaction $trx) {
                $months = [
                    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
                    7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
                ];
                return ($months[$trx->period_month] ?? $trx->period_month) . ' ' . $trx->period_year;
            })
            ->addColumn('reference_badge', function (ActivityTransaction $trx) {
                if ($trx->billing_id && $trx->billing) {
                    return '<span class="badge bg-light text-primary border" title="Factura/Boleta vinculada"><i class="fas fa-file-invoice me-1"></i> ' . e($trx->billing->serie . '-' . $trx->billing->correlativo) . '</span>';
                }
                if ($trx->sale_note_id && $trx->saleNote) {
                    return '<span class="badge bg-light text-info border" title="Nota de Venta vinculada"><i class="fas fa-receipt me-1"></i> NV-' . e($trx->saleNote->id) . '</span>';
                }
                if ($trx->buy_id && $trx->buy) {
                    return '<span class="badge bg-light text-warning border" title="Compra vinculada"><i class="fas fa-shopping-cart me-1"></i> OC-' . e($trx->buy->id) . '</span>';
                }
                if ($trx->voucher_type || $trx->voucher_number) {
                    return '<span class="badge bg-light text-dark border">' . e($trx->voucher_type ?? 'DOC') . ': ' . e($trx->voucher_number ?? 'S/N') . '</span>';
                }
                return '<span class="text-muted small">Sin doc.</span>';
            })
            ->addColumn('action', function (ActivityTransaction $trx) {
                return '
                    <div class="btn-group gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-trx" data-trx="' . htmlspecialchars(json_encode($trx), ENT_QUOTES, 'UTF-8') . '" title="Editar"><i class="fas fa-edit"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-trx" data-id="' . $trx->id . '" data-code="' . e($trx->transaction_code) . '" title="Anular"><i class="fas fa-trash"></i></button>
                    </div>';
            })
            ->rawColumns(['type_badge', 'formatted_amount', 'reference_badge', 'action'])
            ->make(true);
    }

    public function store(ActivityTransactionValidate $request): JsonResponse
    {
        $data = $request->validated();
        $data['registered_by_user_id'] = Auth::id();

        $trx = ActivityTransaction::create($data);

        // Si se vincula a una fuente de fondos con saldo, actualizar balance
        $fund = FundSource::find($data['fund_source_id']);
        if ($fund) {
            if ($data['transaction_type'] === 'INCOME') {
                $fund->increment('current_balance', $data['amount']);
            } else {
                $fund->decrement('current_balance', $data['amount']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Movimiento ' . $trx->transaction_code . ' registrado exitosamente.',
            'trx' => $trx->load(['activity', 'category', 'fundSource'])
        ]);
    }

    public function update(ActivityTransactionValidate $request, int $id): JsonResponse
    {
        $trx = ActivityTransaction::findOrFail($id);
        $data = $request->validated();

        // Revertir impacto previo en fondo si cambió monto o fuente
        $oldFund = FundSource::find($trx->fund_source_id);
        if ($oldFund) {
            if ($trx->transaction_type === 'INCOME') {
                $oldFund->decrement('current_balance', $trx->amount);
            } else {
                $oldFund->increment('current_balance', $trx->amount);
            }
        }

        $trx->update($data);

        // Aplicar nuevo impacto
        $newFund = FundSource::find($trx->fund_source_id);
        if ($newFund) {
            if ($trx->transaction_type === 'INCOME') {
                $newFund->increment('current_balance', $trx->amount);
            } else {
                $newFund->decrement('current_balance', $trx->amount);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Movimiento actualizado correctamente.'
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|exists:activity_transactions,id']);
        $trx = ActivityTransaction::findOrFail($request->id);

        // Revertir fondo
        $fund = FundSource::find($trx->fund_source_id);
        if ($fund && $trx->status !== 'ANULLED') {
            if ($trx->transaction_type === 'INCOME') {
                $fund->decrement('current_balance', $trx->amount);
            } else {
                $fund->increment('current_balance', $trx->amount);
            }
        }

        $trx->update(['status' => 'ANULLED']);
        $trx->delete();

        return response()->json([
            'success' => true,
            'message' => 'Movimiento anulado correctamente.'
        ]);
    }

    /**
     * Búsqueda rápida para autocompletar comprobantes existentes del sistema core
     */
    public function searchCoreDocuments(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $type = $request->input('type'); // 'billings', 'sale_notes', 'buys'

        $results = [];

        if ($type === 'billings') {
            $items = Billing::where('correlativo', 'like', "%{$query}%")
                ->orWhere('serie', 'like', "%{$query}%")
                ->latest()->limit(15)->get();
            foreach ($items as $item) {
                $results[] = [
                    'id' => $item->id,
                    'text' => "{$item->serie}-{$item->correlativo} (Total: S/ {$item->total}) - {$item->cliente_nombre}",
                    'amount' => $item->total,
                    'voucher_type' => 'COMPROBANTE_SUNAT',
                    'voucher_number' => "{$item->serie}-{$item->correlativo}",
                ];
            }
        } elseif ($type === 'sale_notes') {
            $items = SaleNote::where('id', 'like', "%{$query}%")
                ->latest()->limit(15)->get();
            foreach ($items as $item) {
                $results[] = [
                    'id' => $item->id,
                    'text' => "Nota de Venta #{$item->id} (Total: S/ {$item->total})",
                    'amount' => $item->total,
                    'voucher_type' => 'NOTA_VENTA',
                    'voucher_number' => "NV-{$item->id}",
                ];
            }
        } elseif ($type === 'buys') {
            $items = Buy::where('numero_documento', 'like', "%{$query}%")
                ->latest()->limit(15)->get();
            foreach ($items as $item) {
                $results[] = [
                    'id' => $item->id,
                    'text' => "Compra #{$item->id} ({$item->tipo_comprobante} {$item->numero_documento}) - Total: S/ {$item->total}",
                    'amount' => $item->total,
                    'voucher_type' => $item->tipo_comprobante ?? 'COMPRA',
                    'voucher_number' => $item->numero_documento ?? "OC-{$item->id}",
                ];
            }
        }

        return response()->json($results);
    }
}
