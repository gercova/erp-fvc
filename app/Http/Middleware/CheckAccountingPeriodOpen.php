<?php

namespace App\Http\Middleware;

use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountingPeriodOpen
{
    /**
     * Handle an incoming request.
     * Blocks writes dated within a closed accounting period unless 'accounting.reopen' permission is granted.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only inspect state-modifying requests (writes)
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Exempt routes that explicitly perform reopening or approval
        if ($request->routeIs('*.reopen*') || $request->routeIs('*.submit_approval*')) {
            return $next($request);
        }

        $period = $this->resolveTargetPeriod($request);

        if ($period && $period->isClosed()) {
            $user = $request->user();

            if ($user && ($user->hasRole(['SUPERADMIN']) || $user->can('accounting.reopen'))) {
                return $next($request);
            }

            $errorMessage = "Operación bloqueada: El período contable {$period->period_code} se encuentra cerrado. Se requiere el permiso 'accounting.reopen' para registrar o modificar operaciones.";

            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success'     => false,
                    'error'       => 'PERIOD_CLOSED',
                    'message'     => $errorMessage,
                    'period_code' => $period->period_code,
                ], 403);
            }

            abort(403, $errorMessage);
        }

        return $next($request);
    }

    /**
     * Resolve the target accounting period from request inputs or route parameters.
     */
    protected function resolveTargetPeriod(Request $request): ?AccountingPeriod
    {
        // 1. Direct period resolution via ID or route parameter
        $periodParam = $request->input('accounting_period_id')
            ?? $request->input('period_id')
            ?? $request->route('period')
            ?? $request->route('period_id');

        if ($periodParam instanceof AccountingPeriod) {
            return $periodParam;
        }

        if (is_numeric($periodParam)) {
            return AccountingPeriod::find((int) $periodParam);
        }

        if (is_string($periodParam) && preg_match('/^\d{4}-\d{2}$/', $periodParam)) {
            return AccountingPeriod::where('period_code', $periodParam)->first();
        }

        // 2. Date-based period resolution
        $dateParam = $request->input('entry_date')
            ?? $request->input('date')
            ?? $request->input('fecha')
            ?? $request->input('transaction_date')
            ?? $request->input('transfer_date')
            ?? $request->input('statement_date');

        if ($dateParam) {
            try {
                $carbonDate = Carbon::parse($dateParam);
                return AccountingPeriod::query()
                    ->where('fiscal_year', $carbonDate->year)
                    ->where('month', $carbonDate->month)
                    ->first();
            } catch (\Throwable) {
                // Invalid date format, let controller validation handle it
            }
        }

        // 3. Entity-based period resolution for routes with {id}
        if ($id = $request->route('id')) {
            if ($request->is('*journal*') || $request->is('*entry*')) {
                $entry = JournalEntry::find($id);
                if ($entry && $entry->accounting_period_id) {
                    return AccountingPeriod::find($entry->accounting_period_id);
                }
            }
        }

        return null;
    }
}
