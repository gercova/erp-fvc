<?php

namespace App\Services\Accounting;

use App\Enums\JournalStatus;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\FinancialStatementTemplate;
use App\Models\FinancialStatementTemplateLine;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialStatementService
{
    /**
     * Generate the Statement of Financial Position (Balance Sheet / Estado de Situación Financiera).
     *
     * Verification invariant: Assets = Liabilities + Equity
     * Net Result of the period is calculated and reflected under Equity.
     */
    public function getBalanceSheet(array $filters = [], ?array $comparisonFilters = null): array
    {
        $template = FinancialStatementTemplate::where('code', 'BALANCE_SHEET')
            ->where('is_active', true)
            ->with(['lines' => fn($q) => $q->orderBy('sort_order')])
            ->first();

        if (!$template) {
            throw new DomainException("Plantilla para Estado de Situación Financiera (BALANCE_SHEET) no encontrada.");
        }

        $currentData = $this->calculateStatementData($template, $filters, true);

        $comparisonData = null;
        if (!empty($comparisonFilters)) {
            $comparisonData = $this->calculateStatementData($template, $comparisonFilters, true);
        }

        return $this->formatStatementReport($template, $currentData, $comparisonData, 'BALANCE_SHEET');
    }

    /**
     * Generate the Income Statement by Nature (Estado de Resultados por Naturaleza).
     */
    public function getIncomeStatementByNature(array $filters = [], ?array $comparisonFilters = null): array
    {
        $template = FinancialStatementTemplate::where('code', 'INCOME_STATEMENT_NATURE')
            ->where('is_active', true)
            ->with(['lines' => fn($q) => $q->orderBy('sort_order')])
            ->first();

        if (!$template) {
            throw new DomainException("Plantilla de Estado de Resultados por Naturaleza no encontrada.");
        }

        $currentData = $this->calculateStatementData($template, $filters, false);

        $comparisonData = null;
        if (!empty($comparisonFilters)) {
            $comparisonData = $this->calculateStatementData($template, $comparisonFilters, false);
        }

        return $this->formatStatementReport($template, $currentData, $comparisonData, 'INCOME_STATEMENT_NATURE');
    }

    /**
     * Generate the Income Statement by Function (Estado de Resultados por Función).
     */
    public function getIncomeStatementByFunction(array $filters = [], ?array $comparisonFilters = null): array
    {
        $template = FinancialStatementTemplate::where('code', 'INCOME_STATEMENT_FUNCTION')
            ->where('is_active', true)
            ->with(['lines' => fn($q) => $q->orderBy('sort_order')])
            ->first();

        if (!$template) {
            throw new DomainException("Plantilla de Estado de Resultados por Función no encontrada.");
        }

        $currentData = $this->calculateStatementData($template, $filters, false);

        $comparisonData = null;
        if (!empty($comparisonFilters)) {
            $comparisonData = $this->calculateStatementData($template, $comparisonFilters, false);
        }

        return $this->formatStatementReport($template, $currentData, $comparisonData, 'INCOME_STATEMENT_FUNCTION');
    }

    /**
     * Calculate financial statement balances for a given template and filter scope.
     */
    protected function calculateStatementData(
        FinancialStatementTemplate $template,
        array $filters,
        bool $isBalanceSheet
    ): array {
        // Resolve date boundaries
        [$dateFrom, $dateTo] = $this->resolveDates($filters, $isBalanceSheet);

        // Fetch account balances from posted journal entry lines
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.status', JournalStatus::POSTED->value);

        if ($isBalanceSheet) {
            // Balance sheet evaluates cumulative balances up to cutoff date
            if ($dateTo) {
                $query->where('journal_entries.entry_date', '<=', $dateTo->format('Y-m-d'));
            }
        } else {
            // Income statements evaluate activity within the period [dateFrom, dateTo]
            if ($dateFrom) {
                $query->where('journal_entries.entry_date', '>=', $dateFrom->format('Y-m-d'));
            }
            if ($dateTo) {
                $query->where('journal_entries.entry_date', '<=', $dateTo->format('Y-m-d'));
            }
        }

        if (!empty($filters['cost_center_id'])) {
            $query->where('journal_entry_lines.cost_center_id', $filters['cost_center_id']);
        }

        $accountSums = $query->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.element')
            ->select(
                'chart_of_accounts.id',
                'chart_of_accounts.code',
                'chart_of_accounts.name',
                'chart_of_accounts.element',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit')
            )
            ->get();

        // Calculate Net Result for the period
        $netResult = $this->calculatePeriodNetResult($dateFrom, $dateTo, $filters['cost_center_id'] ?? null);

        // Evaluate each template line item
        $evaluatedLines = [];
        $lineValuesByCode = [];

        foreach ($template->lines as $templateLine) {
            $lineAmount = 0.00;
            $matchedAccounts = [];

            if ($templateLine->isItem()) {
                if ($templateLine->line_code === 'RESULTADO_EJERCICIO') {
                    // Reflected Net Result from income statement
                    $lineAmount = $netResult;
                } elseif (!empty($templateLine->account_range)) {
                    $matched = $this->matchAccountsToRange($accountSums, $templateLine->account_range);
                    foreach ($matched as $acc) {
                        $debit = (float) $acc->total_debit;
                        $credit = (float) $acc->total_credit;

                        $balance = $this->calculateAccountBalanceForStatement($acc->code, $acc->element, $debit, $credit, $template->statement_type);
                        $lineAmount += $balance;

                        $matchedAccounts[] = [
                            'code'    => $acc->code,
                            'name'    => $acc->name,
                            'balance' => round($balance, 2),
                        ];
                    }
                }
            }

            $lineValuesByCode[$templateLine->line_code] = round($lineAmount, 2);

            $evaluatedLines[] = [
                'id'            => $templateLine->id,
                'line_code'     => $templateLine->line_code,
                'parent_id'     => $templateLine->parent_line_id,
                'line_name'     => $templateLine->line_name,
                'line_type'     => $templateLine->line_type,
                'polarity'      => $templateLine->polarity,
                'level'         => $templateLine->level,
                'is_bold'       => $templateLine->is_bold,
                'is_italic'     => false,
                'amount'        => round($lineAmount, 2),
                'accounts'      => $matchedAccounts,
            ];

        }

        // Compute subtotals and totals based on the statement type structure
        $finalLines = $this->computeSubtotalsAndTotals($template->statement_type, $evaluatedLines, $netResult);

        return [
            'period'     => [
                'from' => $dateFrom ? $dateFrom->format('Y-m-d') : null,
                'to'   => $dateTo ? $dateTo->format('Y-m-d') : null,
            ],
            'filters'    => $filters,
            'lines'      => $finalLines['lines'],
            'totals'     => $finalLines['totals'],
            'net_result' => $netResult,
        ];
    }

    /**
     * Compute subtotals, totals, and verify the Balance Sheet equation:
     * Assets = Liabilities + Equity
     */
    protected function computeSubtotalsAndTotals(
        string $statementType,
        array $lines,
        float $netResult
    ): array {
        $linesByCode = [];
        foreach ($lines as &$l) {
            $linesByCode[$l['line_code']] = &$l;
        }
        unset($l);

        $totals = [];

        if ($statementType === 'BALANCE_SHEET') {
            // 1. Total Activo Corriente
            $totActCorr = 0.00;
            $actCorrCodes = ['ACT_DISP', 'ACT_COB_COM', 'ACT_OTR_COB', 'ACT_INV', 'ACT_ANT'];
            foreach ($actCorrCodes as $c) {
                if (isset($linesByCode[$c])) {
                    $totActCorr += $linesByCode[$c]['amount'];
                }
            }
            if (isset($linesByCode['ACT_INV_DESV'])) {
                $totActCorr -= abs($linesByCode['ACT_INV_DESV']['amount']);
            }
            if (isset($linesByCode['TOTAL_ACT_CORR'])) {
                $linesByCode['TOTAL_ACT_CORR']['amount'] = round($totActCorr, 2);
            }

            // 2. Total Activo No Corriente
            $totActNoCorr = 0.00;
            $actNoCorrCodes = ['ACT_PPE', 'ACT_BIO', 'ACT_INT', 'ACT_OTR_NO_CORR'];
            foreach ($actNoCorrCodes as $c) {
                if (isset($linesByCode[$c])) {
                    $totActNoCorr += $linesByCode[$c]['amount'];
                }
            }
            if (isset($linesByCode['ACT_DEP_ACUM'])) {
                $totActNoCorr -= abs($linesByCode['ACT_DEP_ACUM']['amount']);
            }
            if (isset($linesByCode['TOTAL_ACT_NO_CORR'])) {
                $linesByCode['TOTAL_ACT_NO_CORR']['amount'] = round($totActNoCorr, 2);
            }

            // 3. Total Activo
            $totalAssets = round($totActCorr + $totActNoCorr, 2);
            if (isset($linesByCode['TOTAL_ACTIVO'])) {
                $linesByCode['TOTAL_ACTIVO']['amount'] = $totalAssets;
            }

            // 4. Total Pasivo Corriente
            $totPasCorr = 0.00;
            $pasCorrCodes = ['PAS_TRIB', 'PAS_REM', 'PAS_PAG_COM', 'PAS_OBL_FIN', 'PAS_OTR_DIV'];
            foreach ($pasCorrCodes as $c) {
                if (isset($linesByCode[$c])) {
                    $totPasCorr += $linesByCode[$c]['amount'];
                }
            }
            if (isset($linesByCode['TOTAL_PAS_CORR'])) {
                $linesByCode['TOTAL_PAS_CORR']['amount'] = round($totPasCorr, 2);
            }

            // 5. Total Pasivo No Corriente
            $totPasNoCorr = 0.00;
            if (isset($linesByCode['PAS_DEUDA_LP'])) {
                $totPasNoCorr += $linesByCode['PAS_DEUDA_LP']['amount'];
            }
            if (isset($linesByCode['TOTAL_PAS_NO_CORR'])) {
                $linesByCode['TOTAL_PAS_NO_CORR']['amount'] = round($totPasNoCorr, 2);
            }

            // 6. Total Pasivo
            $totalLiabilities = round($totPasCorr + $totPasNoCorr, 2);
            if (isset($linesByCode['TOTAL_PASIVO'])) {
                $linesByCode['TOTAL_PASIVO']['amount'] = $totalLiabilities;
            }

            // 7. Total Patrimonio Neto
            $totPatrimonio = 0.00;
            $patCodes = ['PAT_CAPITAL', 'PAT_RESERVAS', 'PAT_RES_ACUM', 'RESULTADO_EJERCICIO'];
            foreach ($patCodes as $c) {
                if (isset($linesByCode[$c])) {
                    $totPatrimonio += $linesByCode[$c]['amount'];
                }
            }
            if (isset($linesByCode['TOTAL_PATRIMONIO'])) {
                $linesByCode['TOTAL_PATRIMONIO']['amount'] = round($totPatrimonio, 2);
            }

            // 8. Total Pasivo y Patrimonio Neto
            $totalLiabilitiesAndEquity = round($totalLiabilities + $totPatrimonio, 2);
            if (isset($linesByCode['TOTAL_PASIVO_PATRIMONIO'])) {
                $linesByCode['TOTAL_PASIVO_PATRIMONIO']['amount'] = $totalLiabilitiesAndEquity;
            }

            // VERIFICATION INVARIANT: Assets = Liabilities + Equity
            $imbalance = round($totalAssets - $totalLiabilitiesAndEquity, 2);
            $isBalanced = abs($imbalance) < 0.01;

            $totals = [
                'total_current_assets'          => round($totActCorr, 2),
                'total_non_current_assets'      => round($totActNoCorr, 2),
                'total_assets'                  => $totalAssets,
                'total_current_liabilities'     => round($totPasCorr, 2),
                'total_non_current_liabilities' => round($totPasNoCorr, 2),
                'total_liabilities'             => $totalLiabilities,
                'total_equity'                  => round($totPatrimonio, 2),
                'total_liabilities_and_equity'  => $totalLiabilitiesAndEquity,
                'is_balanced'                   => $isBalanced,
                'imbalance'                     => $imbalance,
                'alert'                         => $isBalanced ? null : "ALERTA: Descuadre contable detectado. Activo (S/ {$totalAssets}) ≠ Pasivo + Patrimonio (S/ {$totalLiabilitiesAndEquity}). Diferencia: S/ {$imbalance}.",
            ];
        } elseif ($statementType === 'INCOME_STATEMENT_NATURE') {
            $ventas   = $linesByCode['NAT_VENTAS']['amount'] ?? 0.00;
            $varProd  = $linesByCode['NAT_VAR_PROD']['amount'] ?? 0.00;
            $prodAct  = $linesByCode['NAT_PROD_ACTIVO']['amount'] ?? 0.00;
            $compras  = $linesByCode['NAT_COMPRAS']['amount'] ?? 0.00;
            $varExist = $linesByCode['NAT_VAR_EXIST']['amount'] ?? 0.00;

            $margenComercial = round($ventas + $varProd + $prodAct - $compras + $varExist, 2);
            if (isset($linesByCode['NAT_MARGEN_COMERCIAL'])) {
                $linesByCode['NAT_MARGEN_COMERCIAL']['amount'] = $margenComercial;
            }

            $servicios = $linesByCode['NAT_SERVICIOS']['amount'] ?? 0.00;
            $excedenteBruto = round($margenComercial - $servicios, 2);
            if (isset($linesByCode['NAT_EXCEDENTE_BRUTO'])) {
                $linesByCode['NAT_EXCEDENTE_BRUTO']['amount'] = $excedenteBruto;
            }

            $personal = $linesByCode['NAT_PERSONAL']['amount'] ?? 0.00;
            $tributos = $linesByCode['NAT_TRIBUTOS']['amount'] ?? 0.00;
            $resExplotacion = round($excedenteBruto - $personal - $tributos, 2);
            if (isset($linesByCode['NAT_RES_EXPLOTACION'])) {
                $linesByCode['NAT_RES_EXPLOTACION']['amount'] = $resExplotacion;
            }

            $deprec     = $linesByCode['NAT_DEPREC']['amount'] ?? 0.00;
            $otrIngreso = $linesByCode['NAT_OTR_INGRESOS']['amount'] ?? 0.00;
            $otrGasto   = $linesByCode['NAT_OTR_GASTOS']['amount'] ?? 0.00;
            $resOperativo = round($resExplotacion - $deprec + $otrIngreso - $otrGasto, 2);
            if (isset($linesByCode['NAT_RES_OPERATIVO'])) {
                $linesByCode['NAT_RES_OPERATIVO']['amount'] = $resOperativo;
            }

            $ingFin = $linesByCode['NAT_ING_FIN']['amount'] ?? 0.00;
            $gasFin = $linesByCode['NAT_GAS_FIN']['amount'] ?? 0.00;
            $resAntesImp = round($resOperativo + $ingFin - $gasFin, 2);
            if (isset($linesByCode['NAT_RES_ANTES_IMP'])) {
                $linesByCode['NAT_RES_ANTES_IMP']['amount'] = $resAntesImp;
            }

            $impRenta = $linesByCode['NAT_IMP_RENTA']['amount'] ?? 0.00;
            $resNeto = round($resAntesImp - $impRenta, 2);
            if (isset($linesByCode['NAT_RES_NETO'])) {
                $linesByCode['NAT_RES_NETO']['amount'] = $resNeto;
            }

            $totals = [
                'margen_comercial'       => $margenComercial,
                'excedente_bruto'        => $excedenteBruto,
                'resultado_explotacion'  => $resExplotacion,
                'resultado_operativo'    => $resOperativo,
                'resultado_antes_imp'    => $resAntesImp,
                'impuesto_renta'         => $impRenta,
                'resultado_neto'         => $resNeto,
            ];
        } elseif ($statementType === 'INCOME_STATEMENT_FUNCTION') {
            $ventas = $linesByCode['FUNC_VENTAS']['amount'] ?? 0.00;
            $costoVentas = $linesByCode['FUNC_COSTO_VENTAS']['amount'] ?? 0.00;

            $utilidadBruta = round($ventas - $costoVentas, 2);
            if (isset($linesByCode['FUNC_UTILIDAD_BRUTA'])) {
                $linesByCode['FUNC_UTILIDAD_BRUTA']['amount'] = $utilidadBruta;
            }

            $gastosAdm    = $linesByCode['FUNC_GASTOS_ADM']['amount'] ?? 0.00;
            $gastosVentas = $linesByCode['FUNC_GASTOS_VENTAS']['amount'] ?? 0.00;

            $utilidadOperativa = round($utilidadBruta - $gastosAdm - $gastosVentas, 2);
            if (isset($linesByCode['FUNC_UTILIDAD_OPERATIVA'])) {
                $linesByCode['FUNC_UTILIDAD_OPERATIVA']['amount'] = $utilidadOperativa;
            }

            $otrIngresos = $linesByCode['FUNC_OTR_INGRESOS']['amount'] ?? 0.00;
            $otrGastos   = $linesByCode['FUNC_OTR_GASTOS']['amount'] ?? 0.00;
            $ingFin      = $linesByCode['FUNC_ING_FIN']['amount'] ?? 0.00;
            $gasFin      = $linesByCode['FUNC_GAS_FIN']['amount'] ?? 0.00;

            $resAntesImp = round($utilidadOperativa + $otrIngresos - $otrGastos + $ingFin - $gasFin, 2);
            if (isset($linesByCode['FUNC_RES_ANTES_IMP'])) {
                $linesByCode['FUNC_RES_ANTES_IMP']['amount'] = $resAntesImp;
            }

            $impRenta = $linesByCode['FUNC_IMP_RENTA']['amount'] ?? 0.00;
            $resNeto = round($resAntesImp - $impRenta, 2);
            if (isset($linesByCode['FUNC_RES_NETO'])) {
                $linesByCode['FUNC_RES_NETO']['amount'] = $resNeto;
            }

            $totals = [
                'utilidad_bruta'     => $utilidadBruta,
                'utilidad_operativa' => $utilidadOperativa,
                'resultado_antes_imp'=> $resAntesImp,
                'impuesto_renta'     => $impRenta,
                'resultado_neto'     => $resNeto,
            ];
        }

        return [
            'lines'  => array_values($linesByCode),
            'totals' => $totals,
        ];
    }

    /**
     * Format final statement report with optional comparison variances (absolute and percentage).
     */
    protected function formatStatementReport(
        FinancialStatementTemplate $template,
        array $currentData,
        ?array $comparisonData,
        string $statementType
    ): array {
        $hasComparison = $comparisonData !== null;
        $compLinesByCode = [];
        if ($hasComparison) {
            foreach ($comparisonData['lines'] as $cl) {
                $compLinesByCode[$cl['line_code']] = $cl['amount'];
            }
        }

        $formattedLines = [];
        foreach ($currentData['lines'] as $line) {
            $currentAmount = $line['amount'];
            $compAmount = $hasComparison ? ($compLinesByCode[$line['line_code']] ?? 0.00) : null;

            $varianceAbs = null;
            $variancePct = null;

            if ($hasComparison) {
                $varianceAbs = round($currentAmount - $compAmount, 2);
                if (abs($compAmount) > 0.0001) {
                    $variancePct = round(($varianceAbs / abs($compAmount)) * 100, 2);
                } else {
                    $variancePct = $currentAmount != 0 ? 100.00 : 0.00;
                }
            }

            $formattedLines[] = array_merge($line, [
                'current_amount'    => $currentAmount,
                'comparison_amount' => $compAmount,
                'variance_abs'      => $varianceAbs,
                'variance_pct'      => $variancePct,
            ]);
        }

        // Format totals comparison
        $formattedTotals = $currentData['totals'];
        if ($hasComparison) {
            foreach ($currentData['totals'] as $key => $val) {
                if (is_numeric($val) && isset($comparisonData['totals'][$key]) && is_numeric($comparisonData['totals'][$key])) {
                    $compVal = $comparisonData['totals'][$key];
                    $diffAbs = round($val - $compVal, 2);
                    $diffPct = abs($compVal) > 0.0001 ? round(($diffAbs / abs($compVal)) * 100, 2) : 0.00;

                    $formattedTotals["{$key}_comp"] = $compVal;
                    $formattedTotals["{$key}_diff_abs"] = $diffAbs;
                    $formattedTotals["{$key}_diff_pct"] = $diffPct;
                }
            }
        }

        return [
            'template'       => [
                'code' => $template->code,
                'name' => $template->name,
                'type' => $template->statement_type,
            ],
            'period'         => $currentData['period'],
            'filters'        => $currentData['filters'],
            'has_comparison' => $hasComparison,
            'comparison'     => $comparisonData ? [
                'period'  => $comparisonData['period'],
                'filters' => $comparisonData['filters'],
            ] : null,
            'lines'          => $formattedLines,
            'totals'         => $formattedTotals,
            'net_result'     => $currentData['net_result'],
        ];
    }

    /**
     * Calculate Account Balance depending on PCGE Element and statement type.
     */
    protected function calculateAccountBalanceForStatement(
        string $code,
        int $element,
        float $debit,
        float $credit,
        string $statementType
    ): float {
        // Elements 1, 2, 3: Assets (Normal Debit). Except contra-accounts 29, 39 which are credit.
        if (in_array($element, [1, 2, 3])) {
            if (str_starts_with($code, '29') || str_starts_with($code, '39') || str_starts_with($code, '19')) {
                return round($credit - $debit, 2);
            }
            return round($debit - $credit, 2);
        }

        // Element 4: Liabilities (Normal Credit)
        if ($element === 4) {
            return round($credit - $debit, 2);
        }

        // Element 5: Equity (Normal Credit)
        if ($element === 5) {
            return round($credit - $debit, 2);
        }

        // Element 6: Expenses (Normal Debit)
        if ($element === 6) {
            // Purchases and operating costs
            if (str_starts_with($code, '61')) {
                // Variación de existencias: Debit is expense (+), Credit is reduction (-)
                return round($debit - $credit, 2);
            }
            return round($debit - $credit, 2);
        }

        // Element 7: Revenues (Normal Credit)
        if ($element === 7) {
            return round($credit - $debit, 2);
        }

        // Element 9: Cost Distribution (94, 95 Normal Debit)
        if ($element === 9) {
            return round($debit - $credit, 2);
        }

        // Element 8: Intermediary Balances
        if ($element === 8) {
            return round($credit - $debit, 2);
        }

        return round($debit - $credit, 2);
    }

    /**
     * Reusable Net Profit / Loss calculation for any period.
     * Net Result = Revenues (Element 7) - Expenses (Element 6, 88, 9, 69).
     */
    public function calculatePeriodNetResult(?Carbon $dateFrom, ?Carbon $dateTo, ?int $costCenterId = null): float
    {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.status', JournalStatus::POSTED->value);

        if ($dateFrom) {
            $query->where('journal_entries.entry_date', '>=', $dateFrom->format('Y-m-d'));
        }
        if ($dateTo) {
            $query->where('journal_entries.entry_date', '<=', $dateTo->format('Y-m-d'));
        }
        if ($costCenterId) {
            $query->where('journal_entry_lines.cost_center_id', $costCenterId);
        }

        // Sum Revenues (Class 7, excluding 79 which is transfer)
        $revenuesQuery = clone $query;
        $revenueSums = $revenuesQuery->where('chart_of_accounts.element', 7)
            ->where('chart_of_accounts.code', 'not like', '79%')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.credit), 0) - COALESCE(SUM(journal_entry_lines.debit), 0) as net_revenue')
            ->first();

        $totalRevenue = (float) ($revenueSums->net_revenue ?? 0.0);

        // Sum Expenses (Class 6 & 88)
        $expensesQuery = clone $query;
        $expenseSums = $expensesQuery->where(function ($q) {
            $q->where('chart_of_accounts.element', 6)
              ->orWhere('chart_of_accounts.code', 'like', '88%');
        })
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) - COALESCE(SUM(journal_entry_lines.credit), 0) as net_expense')
            ->first();

        $totalExpense = (float) ($expenseSums->net_expense ?? 0.0);

        // Also check if any element 9 expenses were posted directly without Element 6 / 79
        $element9Query = clone $query;
        $element9Sums = $element9Query->where('chart_of_accounts.element', 9)
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) - COALESCE(SUM(journal_entry_lines.credit), 0) as net_e9')
            ->first();

        $element79Query = clone $query;
        $element79Sums = $element79Query->where('chart_of_accounts.code', 'like', '79%')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.credit), 0) - COALESCE(SUM(journal_entry_lines.debit), 0) as net_e79')
            ->first();

        $netE9 = (float) ($element9Sums->net_e9 ?? 0.0);
        $netE79 = (float) ($element79Sums->net_e79 ?? 0.0);
        $directE9 = max(0.0, $netE9 - $netE79);

        $totalExpense += $directE9;

        return round($totalRevenue - $totalExpense, 2);

    }

    /**
     * Match account balance rows against comma-separated or range expression.
     * E.g. "10", "12, 13", "20, 21, 23, 24, 25, 26, 28", "30-38".
     */
    protected function matchAccountsToRange(Collection $accounts, string $rangeExpr): Collection
    {
        $parts = array_map('trim', explode(',', $rangeExpr));

        return $accounts->filter(function ($acc) use ($parts) {
            $code = $acc->code;
            foreach ($parts as $part) {
                if (str_contains($part, '-')) {
                    [$start, $end] = explode('-', $part, 2);
                    $startPrefix = trim($start);
                    $endPrefix = trim($end);
                    $accPrefix = substr($code, 0, strlen($startPrefix));
                    if ($accPrefix >= $startPrefix && $accPrefix <= $endPrefix) {
                        return true;
                    }
                } elseif (str_contains($part, '..')) {
                    [$start, $end] = explode('..', $part, 2);
                    $startPrefix = trim($start);
                    $endPrefix = trim($end);
                    $accPrefix = substr($code, 0, strlen($startPrefix));
                    if ($accPrefix >= $startPrefix && $accPrefix <= $endPrefix) {
                        return true;
                    }
                } else {
                    $prefix = rtrim(trim($part), '*');
                    if (str_starts_with($code, $prefix)) {
                        return true;
                    }
                }
            }
            return false;
        });
    }

    /**
     * Resolve date boundaries from filters.
     */
    protected function resolveDates(array $filters, bool $isBalanceSheet): array
    {
        $dateFrom = null;
        $dateTo = null;

        if (!empty($filters['period_id'])) {
            $period = AccountingPeriod::find($filters['period_id']);
            if ($period) {
                $dateFrom = Carbon::parse($period->start_date)->startOfDay();
                $dateTo = Carbon::parse($period->end_date)->endOfDay();
            }
        }

        if (!empty($filters['as_of_date'])) {
            $dateTo = Carbon::parse($filters['as_of_date'])->endOfDay();
        }

        if (!empty($filters['date_from'])) {
            $dateFrom = Carbon::parse($filters['date_from'])->startOfDay();
        }

        if (!empty($filters['date_to'])) {
            $dateTo = Carbon::parse($filters['date_to'])->endOfDay();
        }

        if (!$dateTo) {
            $dateTo = Carbon::now()->endOfDay();
        }

        if (!$dateFrom && !$isBalanceSheet) {
            $dateFrom = $dateTo->copy()->startOfMonth();
        }

        return [$dateFrom, $dateTo];
    }
}
