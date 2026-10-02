<?php

namespace App\Services\Treasury;

use App\Enums\JournalStatus;
use App\Models\AccountingPeriod;
use App\Models\ActivityOrder;
use App\Models\Billing;
use App\Models\CashFlowMapping;
use App\Models\ChartOfAccount;
use App\Models\FundSource;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ProductiveActivity;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CashFlowReportService
{
    /**
     * Generate Direct Method Cash Flow (Flujo de Caja - Método Directo).
     * Compares Projected vs Actual and reconciles with change in cash and bank balances.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|null $fundSourceId Filter by funding source
     * @param int|null $activityId Filter by productive activity (cost center)
     * @return array
     */
    public function generateDirectCashFlow(
        string $dateFrom,
        string $dateTo,
        ?int $fundSourceId = null,
        ?int $activityId = null
    ): array {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        // 1. Resolve all Cash & Bank accounts (PCGE 10: 101, 102, 103, 104, 107...)
        $cashAndBankAccounts = ChartOfAccount::query()
            ->where(function ($q) {
                $q->where('code', 'like', '10%');
            })
            ->pluck('id', 'code')
            ->toArray();

        $cashAccountIds = array_values($cashAndBankAccounts);

        // 2. Initial Balances (Opening cash & bank balance before dateFrom)
        $initialBalance = $this->calculateBalancesForPeriod($cashAccountIds, null, $from->copy()->subDay()->endOfDay(), $fundSourceId, $activityId);

        // 3. Final Balances (Closing cash & bank balance at dateTo)
        $finalBalance = $this->calculateBalancesForPeriod($cashAccountIds, null, $to, $fundSourceId, $activityId);

        // Expected change in Cash & Bank balances
        $balanceChange = round($finalBalance - $initialBalance, 2);

        // 4. Actual Cash Flow Movements during period [dateFrom, dateTo]
        $actualFlows = $this->calculateActualFlows($cashAccountIds, $from, $to, $fundSourceId, $activityId);

        // 5. Projected Cash Flow Movements for the same period
        $projectedFlows = $this->calculateProjectedFlows($from, $to, $fundSourceId, $activityId);

        // 6. Aggregate by Categories: OPERATING, INVESTING, FINANCING
        $categories = ['OPERATING', 'INVESTING', 'FINANCING'];
        $groupedReport = [];

        $totalActualInflows = 0.00;
        $totalActualOutflows = 0.00;
        $totalProjectedInflows = 0.00;
        $totalProjectedOutflows = 0.00;

        foreach ($categories as $cat) {
            $catActual = $actualFlows[$cat] ?? ['inflows' => [], 'outflows' => [], 'net' => 0.00];
            $catProjected = $projectedFlows[$cat] ?? ['inflows' => [], 'outflows' => [], 'net' => 0.00];

            $catActualIn = array_sum(array_column($catActual['inflows'], 'amount'));
            $catActualOut = array_sum(array_column($catActual['outflows'], 'amount'));
            $catActualNet = round($catActualIn - $catActualOut, 2);

            $catProjIn = array_sum(array_column($catProjected['inflows'], 'amount'));
            $catProjOut = array_sum(array_column($catProjected['outflows'], 'amount'));
            $catProjNet = round($catProjIn - $catProjOut, 2);

            $totalActualInflows += $catActualIn;
            $totalActualOutflows += $catActualOut;
            $totalProjectedInflows += $catProjIn;
            $totalProjectedOutflows += $catProjOut;

            $groupedReport[$cat] = [
                'name' => match ($cat) {
                    'OPERATING' => 'ACTIVIDADES DE OPERACIÓN',
                    'INVESTING' => 'ACTIVIDADES DE INVERSIÓN',
                    'FINANCING' => 'ACTIVIDADES DE FINANCIAMIENTO',
                },
                'actual' => [
                    'inflows'  => $catActual['inflows'],
                    'outflows' => $catActual['outflows'],
                    'total_inflows'  => round($catActualIn, 2),
                    'total_outflows' => round($catActualOut, 2),
                    'net'            => $catActualNet,
                ],
                'projected' => [
                    'inflows'  => $catProjected['inflows'],
                    'outflows' => $catProjected['outflows'],
                    'total_inflows'  => round($catProjIn, 2),
                    'total_outflows' => round($catProjOut, 2),
                    'net'            => $catProjNet,
                ],
                'variance' => [
                    'inflows'  => round($catActualIn - $catProjIn, 2),
                    'outflows' => round($catActualOut - $catProjOut, 2),
                    'net'      => round($catActualNet - $catProjNet, 2),
                ],
            ];
        }

        $actualNetCashFlow = round($totalActualInflows - $totalActualOutflows, 2);
        $projectedNetCashFlow = round($totalProjectedInflows - $totalProjectedOutflows, 2);

        // 7. ACCEPTANCE CRITERIA RECONCILIATION:
        // actual cash flow reconciles with the change in cash and bank balances for the period
        $reconciliationDifference = round($actualNetCashFlow - $balanceChange, 2);
        $isReconciled = abs($reconciliationDifference) <= 0.01;

        // 8. Chart data structures
        $chartData = [
            'categories' => ['Operación', 'Inversión', 'Financiamiento', 'Flujo Neto'],
            'actual'     => [
                $groupedReport['OPERATING']['actual']['net'],
                $groupedReport['INVESTING']['actual']['net'],
                $groupedReport['FINANCING']['actual']['net'],
                $actualNetCashFlow,
            ],
            'projected'  => [
                $groupedReport['OPERATING']['projected']['net'],
                $groupedReport['INVESTING']['projected']['net'],
                $groupedReport['FINANCING']['projected']['net'],
                $projectedNetCashFlow,
            ],
        ];

        return [
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to'   => $to->format('Y-m-d'),
            ],
            'filters' => [
                'fund_source_id' => $fundSourceId,
                'activity_id'    => $activityId,
            ],
            'initial_balance' => round($initialBalance, 2),
            'final_balance'   => round($finalBalance, 2),
            'balance_change'  => $balanceChange,
            'summary' => [
                'actual_inflows'       => round($totalActualInflows, 2),
                'actual_outflows'      => round($totalActualOutflows, 2),
                'actual_net_flow'      => $actualNetCashFlow,
                'projected_inflows'    => round($totalProjectedInflows, 2),
                'projected_outflows'   => round($totalProjectedOutflows, 2),
                'projected_net_flow'   => $projectedNetCashFlow,
                'variance_net'         => round($actualNetCashFlow - $projectedNetCashFlow, 2),
            ],
            'reconciliation' => [
                'initial_cash_bank'   => round($initialBalance, 2),
                'net_cash_flow'       => $actualNetCashFlow,
                'calculated_final'    => round($initialBalance + $actualNetCashFlow, 2),
                'actual_final'        => round($finalBalance, 2),
                'difference'          => $reconciliationDifference,
                'is_reconciled'       => $isReconciled,
            ],
            'categories' => $groupedReport,
            'chart_data' => $chartData,
        ];
    }

    /**
     * Calculate cumulative posted Cash & Bank balance for a given date cutoff.
     */
    protected function calculateBalancesForPeriod(
        array $cashAccountIds,
        ?Carbon $from,
        Carbon $to,
        ?int $fundSourceId,
        ?int $activityId
    ): float {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', JournalStatus::POSTED->value)
            ->whereIn('journal_entry_lines.account_id', $cashAccountIds);

        if ($from) {
            $query->where('journal_entries.entry_date', '>=', $from->format('Y-m-d'));
        }
        $query->where('journal_entries.entry_date', '<=', $to->format('Y-m-d'));

        if ($fundSourceId) {
            $query->where('journal_entry_lines.fund_source_id', $fundSourceId);
        }
        if ($activityId) {
            $query->where('journal_entry_lines.cost_center_id', $activityId);
        }

        $sums = $query->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        // Also add initial balances of bank_accounts and fund_sources if measuring from inception
        $initialSeeds = 0.00;
        if (!$from) {
            $fsQuery = FundSource::query();
            if ($fundSourceId) {
                $fsQuery->where('id', $fundSourceId);
            }
            $initialSeeds = (float) $fsQuery->sum('initial_balance');
        }

        $net = ((float) $sums->total_debit) - ((float) $sums->total_credit);

        return round($initialSeeds + $net, 2);
    }

    /**
     * Calculate actual inflows and outflows from posted journal entries touching account 10.
     */
    protected function calculateActualFlows(
        array $cashAccountIds,
        Carbon $from,
        Carbon $to,
        ?int $fundSourceId,
        ?int $activityId
    ): array {
        $mappings = CashFlowMapping::active()->get();

        // Query all posted lines affecting cash & bank
        $query = JournalEntryLine::query()
            ->with(['journalEntry.lines.account'])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', JournalStatus::POSTED->value)
            ->whereIn('journal_entry_lines.account_id', $cashAccountIds)
            ->whereBetween('journal_entries.entry_date', [$from->format('Y-m-d'), $to->format('Y-m-d')]);

        if ($fundSourceId) {
            $query->where('journal_entry_lines.fund_source_id', $fundSourceId);
        }
        if ($activityId) {
            $query->where('journal_entry_lines.cost_center_id', $activityId);
        }

        $cashLines = $query->select('journal_entry_lines.*')->get();

        $result = [
            'OPERATING' => ['inflows' => [], 'outflows' => []],
            'INVESTING' => ['inflows' => [], 'outflows' => []],
            'FINANCING' => ['inflows' => [], 'outflows' => []],
        ];

        // Track internal transfers between 10x and 10x accounts to avoid false double counting
        foreach ($cashLines as $line) {
            $isDebit = (float) $line->debit > 0;
            $amount = $isDebit ? (float) $line->debit : (float) $line->credit;
            if ($amount <= 0) {
                continue;
            }

            // Find contra-account on the entry
            $entry = $line->journalEntry;
            $contraAccount = null;
            $isInternalTransfer = false;

            if ($entry) {
                foreach ($entry->lines as $otherLine) {
                    if ($otherLine->id !== $line->id) {
                        if (in_array($otherLine->account_id, $cashAccountIds)) {
                            $isInternalTransfer = true;
                        } else {
                            $contraAccount = $otherLine->account;
                        }
                    }
                }
            }

            // If internal cash-to-bank transfer without fundSource filter, it nets out to 0 on overall entity
            // but if filtering by fundSource, it's a real inflow/outflow for that specific fund
            if ($isInternalTransfer && !$fundSourceId) {
                // Internal transfer nets out on global cash flow
                continue;
            }

            // Map to Category and Concept
            $flowType = $isDebit ? 'INFLOW' : 'OUTFLOW';
            $contraCode = $contraAccount?->code ?? '';

            $matchedMapping = $this->resolveMapping($mappings, $flowType, $contraCode, $entry?->concept);

            $category = $matchedMapping ? $matchedMapping->category : 'OPERATING';
            $conceptName = $matchedMapping ? $matchedMapping->concept_name : ($line->glosa ?? $entry?->concept ?? 'Movimiento operativo');

            $key = $flowType === 'INFLOW' ? 'inflows' : 'outflows';

            if (!isset($result[$category][$key][$conceptName])) {
                $result[$category][$key][$conceptName] = [
                    'concept' => $conceptName,
                    'amount'  => 0.00,
                ];
            }

            $result[$category][$key][$conceptName]['amount'] += $amount;
        }

        // Format into indexed arrays
        foreach ($result as $cat => &$flows) {
            $flows['inflows'] = array_values($flows['inflows']);
            $flows['outflows'] = array_values($flows['outflows']);
            foreach ($flows['inflows'] as &$inf) {
                $inf['amount'] = round($inf['amount'], 2);
            }
            foreach ($flows['outflows'] as &$outf) {
                $outf['amount'] = round($outf['amount'], 2);
            }
        }

        return $result;
    }

    /**
     * Resolve matching CashFlowMapping based on contra-account code prefix or concept.
     */
    protected function resolveMapping(
        Collection $mappings,
        string $flowType,
        string $contraCode,
        ?string $concept
    ): ?CashFlowMapping {
        // 1. Prefix match on account code
        if (!empty($contraCode)) {
            foreach ($mappings as $m) {
                if ($m->flow_type === $flowType && !empty($m->account_prefix)) {
                    if (str_starts_with($contraCode, $m->account_prefix)) {
                        return $m;
                    }
                }
            }
        }

        // 2. Default fallback mapping for the flow type
        return $mappings->where('flow_type', $flowType)->first();
    }

    /**
     * Calculate projected inflows and outflows (pending receivables due, payables due, and planned budget lines).
     */
    protected function calculateProjectedFlows(
        Carbon $from,
        Carbon $to,
        ?int $fundSourceId,
        ?int $activityId
    ): array {
        $result = [
            'OPERATING' => ['inflows' => [], 'outflows' => []],
            'INVESTING' => ['inflows' => [], 'outflows' => []],
            'FINANCING' => ['inflows' => [], 'outflows' => []],
        ];

        // 1. Projected Collections from Credit Billings due in period
        $billingsDue = Billing::query()
            ->where('anulado', false)
            ->where(function ($q) {
                $q->where('sunat_forma_pago', 'Credito')
                  ->orWhere('modo_pago', 2)
                  ->orWhere('monto_credito', '>', 0);
            })
            ->whereBetween('fecha_vencimiento', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->sum(DB::raw('CASE WHEN monto_credito > 0 THEN monto_credito ELSE total END'));

        if ($billingsDue > 0) {
            $result['OPERATING']['inflows'][] = [
                'concept' => 'Cobranza proyectada de comprobantes por cobrar a clientes',
                'amount'  => round((float) $billingsDue, 2),
            ];
        }

        // 2. Projected Collections from Activity Orders due in period
        if (Schema::hasTable('activity_orders')) {
            $ordersQuery = ActivityOrder::query()
                ->where('balance_pending', '>', 0)
                ->whereNotIn('status', ['cancelled'])
                ->whereBetween('expected_delivery_date', [$from->format('Y-m-d'), $to->format('Y-m-d')]);

            if ($activityId) {
                $ordersQuery->where('productive_activity_id', $activityId);
            }

            $ordersDue = $ordersQuery->sum('balance_pending');
            if ($ordersDue > 0) {
                $result['OPERATING']['inflows'][] = [
                    'concept' => 'Cobranza proyectada de pedidos de actividades APE',
                    'amount'  => round((float) $ordersDue, 2),
                ];
            }
        }

        // 3. Projected Payments for Credit Buys due in period
        if (Schema::hasTable('accounts_payable')) {
            $apDue = DB::table('accounts_payable')
                ->where('saldo', '>', 0)
                ->where('estado', '!=', 'PAGADO')
                ->whereBetween('fecha_vencimiento', [$from->format('Y-m-d'), $to->format('Y-m-d')])
                ->sum('saldo');

            if ($apDue > 0) {
                $result['OPERATING']['outflows'][] = [
                    'concept' => 'Pagos proyectados a proveedores comerciales por compras',
                    'amount'  => round((float) $apDue, 2),
                ];
            }
        }

        return $result;
    }

    /**
     * Export cash flow report to CSV / Excel readable string.
     */
    public function exportToCsv(array $report): string
    {
        $lines = [];
        $lines[] = "FLUJO DE CAJA - METODO DIRECTO";
        $lines[] = "Periodo:," . $report['period']['from'] . " al " . $report['period']['to'];
        $lines[] = "Saldo Inicial Caja y Bancos:," . number_format($report['initial_balance'], 2);
        $lines[] = "";
        $lines[] = "CATEGORIA / CONCEPTO,REAL (S/),PROYECTADO (S/),VARIACION (S/)";

        foreach ($report['categories'] as $catKey => $cat) {
            $lines[] = "\"" . $cat['name'] . "\",,,";
            $lines[] = "INGRESOS,,,";
            foreach ($cat['actual']['inflows'] as $inflow) {
                $concept = $inflow['concept'];
                $actual = $inflow['amount'];
                $proj = 0.00;
                foreach ($cat['projected']['inflows'] as $p) {
                    if ($p['concept'] === $concept) {
                        $proj = $p['amount'];
                        break;
                    }
                }
                $var = $actual - $proj;
                $lines[] = "\"  " . $concept . "\"," . $actual . "," . $proj . "," . $var;
            }
            $lines[] = "EGRESOS,,,";
            foreach ($cat['actual']['outflows'] as $outflow) {
                $concept = $outflow['concept'];
                $actual = $outflow['amount'];
                $proj = 0.00;
                foreach ($cat['projected']['outflows'] as $p) {
                    if ($p['concept'] === $concept) {
                        $proj = $p['amount'];
                        break;
                    }
                }
                $var = $actual - $proj;
                $lines[] = "\"  " . $concept . "\"," . $actual . "," . $proj . "," . $var;
            }
            $lines[] = "\"Subtotal " . $cat['name'] . "\"," . $cat['actual']['net'] . "," . $cat['projected']['net'] . "," . $cat['variance']['net'];
            $lines[] = "";
        }

        $lines[] = "RESUMEN EJECUTIVO,,,";
        $lines[] = "Total Ingresos," . $report['summary']['actual_inflows'] . "," . $report['summary']['projected_inflows'] . ",";
        $lines[] = "Total Egresos," . $report['summary']['actual_outflows'] . "," . $report['summary']['projected_outflows'] . ",";
        $lines[] = "Flujo Neto del Periodo," . $report['summary']['actual_net_flow'] . "," . $report['summary']['projected_net_flow'] . "," . $report['summary']['variance_net'];
        $lines[] = "Saldo Final Caja y Bancos:," . number_format($report['final_balance'], 2);
        $lines[] = "Conciliacion con Variacion de Saldos:," . ($report['reconciliation']['is_reconciled'] ? "CONCILIADO EXACTO (Diferencia 0.00)" : "DIFERENCIA " . $report['reconciliation']['difference']);

        return implode("\n", $lines);
    }
}
