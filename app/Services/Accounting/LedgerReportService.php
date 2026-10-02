<?php

namespace App\Services\Accounting;

use App\Enums\JournalStatus;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LedgerReportService
{
    /**
     * Get General Journal (Libro Diario / Formato SUNAT 5.1).
     *
     * @param array $filters [period_id, date_from, date_to, voucher_type, status, per_page, page]
     * @return array
     */
    public function getGeneralJournal(array $filters = []): array
    {
        $status = $filters['status'] ?? JournalStatus::POSTED->value;
        if ($status instanceof JournalStatus) {
            $status = $status->value;
        }

        // Base query with eager loading to prevent N+1 queries
        $query = JournalEntry::query()
            ->with([
                'period',
                'lines' => function ($q) {
                    $q->with(['account', 'costCenter', 'fundSource'])->orderBy('line_number', 'asc');
                },
                'creator',
            ])
            ->when($status, fn(Builder $q) => $q->where('status', $status))
            ->when(!empty($filters['period_id']), fn(Builder $q) => $q->where('accounting_period_id', $filters['period_id']))
            ->when(!empty($filters['date_from']), fn(Builder $q) => $q->whereDate('entry_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn(Builder $q) => $q->whereDate('entry_date', '<=', $filters['date_to']))
            ->when(!empty($filters['voucher_type']), function (Builder $q) use ($filters) {
                $type = $filters['voucher_type'] instanceof \BackedEnum ? $filters['voucher_type']->value : $filters['voucher_type'];
                $q->where('entry_type', $type);
            })
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc');

        // Grand totals using aggregated query on header
        $totals = (clone $query)
            ->setEagerLoads([])
            ->reorder()
            ->selectRaw('
                COALESCE(SUM(total_debit), 0) as grand_total_debit,
                COALESCE(SUM(total_credit), 0) as grand_total_credit,
                COUNT(*) as entries_count
            ')->first();

        $grandTotalDebit = round((float) ($totals->grand_total_debit ?? 0), 2);
        $grandTotalCredit = round((float) ($totals->grand_total_credit ?? 0), 2);
        $entriesCount = (int) ($totals->entries_count ?? 0);
        $isBalanced = abs($grandTotalDebit - $grandTotalCredit) < 0.01;

        // Pagination or full collection
        $perPage = isset($filters['per_page']) && (int) $filters['per_page'] > 0 ? (int) $filters['per_page'] : 0;
        if ($perPage > 0) {
            /** @var LengthAwarePaginator $paginator */
            $paginator = $query->paginate($perPage, ['*'], 'page', $filters['page'] ?? null);
            $entries = collect($paginator->items());
            $pageTotalDebit = round((float) $entries->sum('total_debit'), 2);
            $pageTotalCredit = round((float) $entries->sum('total_credit'), 2);
        } else {
            $paginator = null;
            $entries = $query->get();
            $pageTotalDebit = $grandTotalDebit;
            $pageTotalCredit = $grandTotalCredit;
        }

        // Format entries with sequential numbering (correlative in the report)
        $formattedEntries = [];
        $seqIndex = $paginator ? ($paginator->firstItem() ?? 1) : 1;

        foreach ($entries as $entry) {
            $lines = [];
            foreach ($entry->lines as $line) {
                $lines[] = [
                    'line_id'            => $line->id,
                    'line_number'        => $line->line_number,
                    'account_id'         => $line->account_id,
                    'account_code'       => $line->account?->code ?? '-',
                    'account_name'       => $line->account?->name ?? 'Cuenta Desconocida',
                    'debit'              => (float) $line->debit,
                    'credit'             => (float) $line->credit,
                    'glosa'              => $line->glosa ?: $entry->concept,
                    'document_reference' => $line->document_reference ?: '-',
                    'third_party'        => $line->third_party_document ?: ($line->third_party_type ? $line->third_party_type . ' #' . $line->third_party_id : '-'),
                    'third_party_document' => $line->third_party_document,
                    'cost_center'        => $line->costCenter?->name ?? ($line->cost_center_id ? 'CC #' . $line->cost_center_id : '-'),
                    'fund_source'        => $line->fundSource?->description ?? ($line->fund_source_id ? 'FF #' . $line->fund_source_id : '-'),
                ];
            }

            $formattedEntries[] = [
                'seq_number'    => $seqIndex++,
                'id'            => $entry->id,
                'uuid'          => $entry->uuid,
                'entry_number'  => $entry->entry_number,
                'entry_date'    => $entry->entry_date->format('Y-m-d'),
                'entry_type'    => $entry->entry_type instanceof \BackedEnum ? $entry->entry_type->value : (string) $entry->entry_type,
                'concept'       => $entry->concept,
                'currency'      => $entry->currency,
                'exchange_rate' => (float) $entry->exchange_rate,
                'total_debit'   => (float) $entry->total_debit,
                'total_credit'  => (float) $entry->total_credit,
                'status'        => $entry->status instanceof \BackedEnum ? $entry->status->value : (string) $entry->status,
                'period_code'   => $entry->period?->period_code ?? '-',
                'lines'         => $lines,
            ];
        }

        return [
            'entries'            => $formattedEntries,
            'paginator'          => $paginator,
            'page_total_debit'   => $pageTotalDebit,
            'page_total_credit'  => $pageTotalCredit,
            'grand_total_debit'  => $grandTotalDebit,
            'grand_total_credit' => $grandTotalCredit,
            'entries_count'      => $entriesCount,
            'is_balanced'        => $isBalanced,
            'filters'            => $filters,
        ];
    }

    /**
     * Get General Ledger (Libro Mayor / Formato SUNAT 6.1).
     *
     * @param array $filters [account_id, account_code, account_from, account_to, period_id, date_from, date_to, cost_center_id, third_party_document, third_party_id, status]
     * @return array
     */
    public function getGeneralLedger(array $filters = []): array
    {
        $status = $filters['status'] ?? JournalStatus::POSTED->value;
        if ($status instanceof JournalStatus) {
            $status = $status->value;
        }

        // Resolve date boundaries
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $periodId = $filters['period_id'] ?? null;

        if ($periodId && (!$dateFrom || !$dateTo)) {
            $period = AccountingPeriod::find($periodId);
            if ($period) {
                $dateFrom = $dateFrom ?: $period->start_date->format('Y-m-d');
                $dateTo = $dateTo ?: $period->end_date->format('Y-m-d');
            }
        }

        // Resolve target accounts
        $accountsQuery = ChartOfAccount::query()->orderBy('code', 'asc');

        if (!empty($filters['account_id'])) {
            $accountsQuery->where('id', $filters['account_id']);
        } elseif (!empty($filters['account_code'])) {
            $code = $filters['account_code'];
            $accountsQuery->where(function (Builder $q) use ($code) {
                $q->where('code', $code)
                  ->orWhere('code', 'like', $code . '%');
            });
        } elseif (!empty($filters['account_from']) && !empty($filters['account_to'])) {
            $accountsQuery->whereBetween('code', [$filters['account_from'], $filters['account_to']]);
        } elseif (!empty($filters['account_from'])) {
            $accountsQuery->where('code', '>=', $filters['account_from']);
        } elseif (!empty($filters['account_to'])) {
            $accountsQuery->where('code', '<=', $filters['account_to']);
        }

        // If no specific accounts were provided, limit to movement accounts with activity or all movement accounts
        $targetAccounts = $accountsQuery->get();

        $accountsReport = [];
        $grandOpening = 0.0;
        $grandPeriodDebit = 0.0;
        $grandPeriodCredit = 0.0;
        $grandFinalBalance = 0.0;

        foreach ($targetAccounts as $account) {
            // 1. Calculate Opening Balance (Saldo Inicial) strictly prior to dateFrom
            $priorQuery = DB::table('journal_entry_lines as jel')
                ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
                ->where('jel.account_id', $account->id)
                ->where('je.status', $status);

            if ($dateFrom) {
                $priorQuery->whereDate('je.entry_date', '<', $dateFrom);
            } else {
                // If no dateFrom, opening balance is 0 unless prior period is defined
                $priorQuery->whereRaw('1 = 0');
            }

            if (!empty($filters['cost_center_id'])) {
                $priorQuery->where('jel.cost_center_id', $filters['cost_center_id']);
            }
            if (!empty($filters['third_party_document'])) {
                $priorQuery->where('jel.third_party_document', $filters['third_party_document']);
            }
            if (!empty($filters['third_party_id'])) {
                $priorQuery->where('jel.third_party_id', $filters['third_party_id']);
            }

            $priorSums = $priorQuery->selectRaw('
                COALESCE(SUM(jel.debit), 0) as prior_debit,
                COALESCE(SUM(jel.credit), 0) as prior_credit
            ')->first();

            $openingDebit = round((float) ($priorSums->prior_debit ?? 0), 2);
            $openingCredit = round((float) ($priorSums->prior_credit ?? 0), 2);
            $openingBalance = round($openingDebit - $openingCredit, 2);

            // 2. Fetch Transactions within the period/dates
            $linesQuery = JournalEntryLine::query()
                ->with(['costCenter', 'fundSource'])
                ->join('journal_entries as je', 'journal_entry_lines.journal_entry_id', '=', 'je.id')
                ->where('journal_entry_lines.account_id', $account->id)
                ->where('je.status', $status)
                ->when($dateFrom, fn(Builder $q) => $q->whereDate('je.entry_date', '>=', $dateFrom))
                ->when($dateTo, fn(Builder $q) => $q->whereDate('je.entry_date', '<=', $dateTo))
                ->when($periodId, fn(Builder $q) => $q->where('je.accounting_period_id', $periodId))
                ->when(!empty($filters['cost_center_id']), fn(Builder $q) => $q->where('journal_entry_lines.cost_center_id', $filters['cost_center_id']))
                ->when(!empty($filters['third_party_document']), fn(Builder $q) => $q->where('journal_entry_lines.third_party_document', $filters['third_party_document']))
                ->when(!empty($filters['third_party_id']), fn(Builder $q) => $q->where('journal_entry_lines.third_party_id', $filters['third_party_id']))
                ->select([
                    'journal_entry_lines.*',
                    'je.entry_number',
                    'je.entry_date',
                    'je.entry_type',
                    'je.concept as entry_concept',
                ])
                ->orderBy('je.entry_date', 'asc')
                ->orderBy('je.id', 'asc')
                ->orderBy('journal_entry_lines.line_number', 'asc');

            $lines = $linesQuery->get();

            // Skip accounts with zero movements and zero opening balance if viewing multiple accounts
            // or if filtering by dimension (third party or cost center)
            $hasFilterDimension = !empty($filters['third_party_document']) || !empty($filters['third_party_id']) || !empty($filters['cost_center_id']);
            if ($lines->isEmpty() && abs($openingBalance) < 0.01 && ($hasFilterDimension || ($targetAccounts->count() > 1 && empty($filters['account_id'])))) {
                continue;
            }

            // 3. Compute running balance line-by-line
            $runningBalance = $openingBalance;
            $periodDebit = 0.0;
            $periodCredit = 0.0;
            $transactions = [];

            foreach ($lines as $line) {
                $debit = (float) $line->debit;
                $credit = (float) $line->credit;
                $periodDebit += $debit;
                $periodCredit += $credit;
                $runningBalance += ($debit - $credit);

                $transactions[] = [
                    'line_id'            => $line->id,
                    'entry_id'           => $line->journal_entry_id,
                    'entry_number'       => $line->entry_number,
                    'entry_date'         => is_string($line->entry_date) ? $line->entry_date : $line->entry_date?->format('Y-m-d'),
                    'entry_type'         => $line->entry_type instanceof \BackedEnum ? $line->entry_type->value : (string) $line->entry_type,
                    'concept'            => $line->glosa ?: $line->entry_concept,
                    'document_reference' => $line->document_reference ?: '-',
                    'third_party'        => $line->third_party_document ?: ($line->third_party_type ? $line->third_party_type . ' #' . $line->third_party_id : '-'),
                    'cost_center'        => $line->costCenter?->name ?? ($line->cost_center_id ? 'CC #' . $line->cost_center_id : '-'),
                    'debit'              => $debit,
                    'credit'             => $credit,
                    'running_balance'    => round($runningBalance, 2),
                ];
            }

            $finalBalance = round($runningBalance, 2);
            $periodDebit = round($periodDebit, 2);
            $periodCredit = round($periodCredit, 2);

            $accountsReport[$account->code] = [
                'account_id'       => $account->id,
                'account_code'     => $account->code,
                'account_name'     => $account->name,
                'nature'           => $account->nature instanceof \BackedEnum ? $account->nature->value : (string) $account->nature,
                'opening_debit'    => $openingDebit,
                'opening_credit'   => $openingCredit,
                'opening_balance'  => $openingBalance,
                'period_debit'     => $periodDebit,
                'period_credit'    => $periodCredit,
                'final_balance'    => $finalBalance,
                'saldo_deudor'     => $finalBalance > 0 ? $finalBalance : 0.00,
                'saldo_acreedor'   => $finalBalance < 0 ? abs($finalBalance) : 0.00,
                'transactions'     => $transactions,
            ];

            $grandOpening += $openingBalance;
            $grandPeriodDebit += $periodDebit;
            $grandPeriodCredit += $periodCredit;
            $grandFinalBalance += $finalBalance;
        }

        return [
            'accounts' => $accountsReport,
            'summary'  => [
                'opening_balance' => round($grandOpening, 2),
                'period_debit'    => round($grandPeriodDebit, 2),
                'period_credit'   => round($grandPeriodCredit, 2),
                'final_balance'   => round($grandFinalBalance, 2),
                'saldo_deudor'    => $grandFinalBalance > 0 ? round($grandFinalBalance, 2) : 0.00,
                'saldo_acreedor'  => $grandFinalBalance < 0 ? round(abs($grandFinalBalance), 2) : 0.00,
                'accounts_count'  => count($accountsReport),
            ],
            'filters'  => array_merge($filters, [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ]),
        ];
    }

    /**
     * Get Trial Balance (Balance de Comprobación / Hoja de Trabajo).
     *
     * @param array $filters [period_id, date_from, date_to, digits, cost_center_id, status, include_zero_balances]
     * @return array
     */
    public function getTrialBalance(array $filters = []): array
    {
        $status = $filters['status'] ?? JournalStatus::POSTED->value;
        if ($status instanceof JournalStatus) {
            $status = $status->value;
        }

        $periodId = $filters['period_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $digits = isset($filters['digits']) && (int) $filters['digits'] > 0 ? (int) $filters['digits'] : null;

        if ($periodId && (!$dateFrom || !$dateTo)) {
            $period = AccountingPeriod::find($periodId);
            if ($period) {
                $dateFrom = $dateFrom ?: $period->start_date->format('Y-m-d');
                $dateTo = $dateTo ?: $period->end_date->format('Y-m-d');
            }
        }

        // Single aggregated query across all movement lines avoiding N+1
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'jel.account_id', '=', 'coa.id')
            ->where('je.status', $status);

        if ($periodId) {
            $query->where('je.accounting_period_id', $periodId);
        }
        if (!empty($filters['cost_center_id'])) {
            $query->where('jel.cost_center_id', $filters['cost_center_id']);
        }
        if ($dateTo) {
            $query->whereDate('je.entry_date', '<=', $dateTo);
        }

        if ($dateFrom) {
            $query->selectRaw("
                coa.id as account_id,
                coa.code as account_code,
                coa.name as account_name,
                coa.nature as account_nature,
                COALESCE(SUM(CASE WHEN je.entry_date < ? THEN jel.debit ELSE 0 END), 0) as initial_debit,
                COALESCE(SUM(CASE WHEN je.entry_date < ? THEN jel.credit ELSE 0 END), 0) as initial_credit,
                COALESCE(SUM(CASE WHEN je.entry_date >= ? THEN jel.debit ELSE 0 END), 0) as period_debit,
                COALESCE(SUM(CASE WHEN je.entry_date >= ? THEN jel.credit ELSE 0 END), 0) as period_credit,
                COALESCE(SUM(jel.debit), 0) as total_debit,
                COALESCE(SUM(jel.credit), 0) as total_credit
            ", [$dateFrom, $dateFrom, $dateFrom, $dateFrom]);
        } else {
            $query->selectRaw("
                coa.id as account_id,
                coa.code as account_code,
                coa.name as account_name,
                coa.nature as account_nature,
                0 as initial_debit,
                0 as initial_credit,
                COALESCE(SUM(jel.debit), 0) as period_debit,
                COALESCE(SUM(jel.credit), 0) as period_credit,
                COALESCE(SUM(jel.debit), 0) as total_debit,
                COALESCE(SUM(jel.credit), 0) as total_credit
            ");
        }

        $rawRows = $query->groupBy('coa.id', 'coa.code', 'coa.name', 'coa.nature')
            ->orderBy('coa.code', 'asc')
            ->get();

        // Load all chart of accounts for nomenclature resolution by code prefix
        $allAccounts = ChartOfAccount::all()->keyBy('code');

        // Aggregation by digits (e.g. 2, 3, 4 digits) or full movement accounts
        $aggregated = [];

        foreach ($rawRows as $row) {
            $code = (string) $row->account_code;
            $groupKey = $digits ? substr($code, 0, $digits) : $code;

            if (!isset($aggregated[$groupKey])) {
                // Resolve name for the prefix
                $groupAccount = $allAccounts->get($groupKey);
                $groupName = $groupAccount ? $groupAccount->name : $row->account_name;
                $groupNature = $groupAccount?->nature ?? $row->account_nature;

                $aggregated[$groupKey] = [
                    'code'           => $groupKey,
                    'name'           => $groupName,
                    'nature'         => $groupNature instanceof \BackedEnum ? $groupNature->value : (string) $groupNature,
                    'initial_debit'  => 0.0,
                    'initial_credit' => 0.0,
                    'period_debit'   => 0.0,
                    'period_credit'  => 0.0,
                    'total_debit'    => 0.0,
                    'total_credit'   => 0.0,
                    'saldo_deudor'   => 0.0,
                    'saldo_acreedor' => 0.0,
                ];
            }

            $aggregated[$groupKey]['initial_debit'] += (float) $row->initial_debit;
            $aggregated[$groupKey]['initial_credit'] += (float) $row->initial_credit;
            $aggregated[$groupKey]['period_debit'] += (float) $row->period_debit;
            $aggregated[$groupKey]['period_credit'] += (float) $row->period_credit;
            $aggregated[$groupKey]['total_debit'] += (float) $row->total_debit;
            $aggregated[$groupKey]['total_credit'] += (float) $row->total_credit;
        }

        // Compute balances and grand totals
        ksort($aggregated);

        $items = [];
        $sumInitialDebit = 0.0;
        $sumInitialCredit = 0.0;
        $sumPeriodDebit = 0.0;
        $sumPeriodCredit = 0.0;
        $sumTotalDebit = 0.0;
        $sumTotalCredit = 0.0;
        $sumSaldoDeudor = 0.0;
        $sumSaldoAcreedor = 0.0;

        foreach ($aggregated as $item) {
            $totalDebit = round($item['total_debit'], 2);
            $totalCredit = round($item['total_credit'], 2);
            $net = round($totalDebit - $totalCredit, 2);

            $saldoDeudor = $net > 0 ? $net : 0.00;
            $saldoAcreedor = $net < 0 ? abs($net) : 0.00;

            $item['initial_debit'] = round($item['initial_debit'], 2);
            $item['initial_credit'] = round($item['initial_credit'], 2);
            $item['period_debit'] = round($item['period_debit'], 2);
            $item['period_credit'] = round($item['period_credit'], 2);
            $item['total_debit'] = $totalDebit;
            $item['total_credit'] = $totalCredit;
            $item['saldo_deudor'] = $saldoDeudor;
            $item['saldo_acreedor'] = $saldoAcreedor;

            $sumInitialDebit += $item['initial_debit'];
            $sumInitialCredit += $item['initial_credit'];
            $sumPeriodDebit += $item['period_debit'];
            $sumPeriodCredit += $item['period_credit'];
            $sumTotalDebit += $totalDebit;
            $sumTotalCredit += $totalCredit;
            $sumSaldoDeudor += $saldoDeudor;
            $sumSaldoAcreedor += $saldoAcreedor;

            $items[] = $item;
        }

        $sumTotalDebit = round($sumTotalDebit, 2);
        $sumTotalCredit = round($sumTotalCredit, 2);
        $sumSaldoDeudor = round($sumSaldoDeudor, 2);
        $sumSaldoAcreedor = round($sumSaldoAcreedor, 2);

        // Verification of double entry balancing
        $debitCreditDiff = round($sumTotalDebit - $sumTotalCredit, 2);
        $balancesDiff = round($sumSaldoDeudor - $sumSaldoAcreedor, 2);
        $isBalanced = (abs($debitCreditDiff) < 0.01) && (abs($balancesDiff) < 0.01);

        return [
            'accounts' => $items,
            'totals'   => [
                'initial_debit'  => round($sumInitialDebit, 2),
                'initial_credit' => round($sumInitialCredit, 2),
                'period_debit'   => round($sumPeriodDebit, 2),
                'period_credit'  => round($sumPeriodCredit, 2),
                'total_debit'    => $sumTotalDebit,
                'total_credit'   => $sumTotalCredit,
                'saldo_deudor'   => $sumSaldoDeudor,
                'saldo_acreedor' => $sumSaldoAcreedor,
            ],
            'verification' => [
                'is_balanced'        => $isBalanced,
                'debit_credit_diff'  => $debitCreditDiff,
                'balances_diff'      => $balancesDiff,
                'status'             => $isBalanced ? 'CUADRADO' : 'DESCUADRADO',
            ],
            'filters'  => array_merge($filters, [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'digits'    => $digits,
            ]),
        ];
    }

    /**
     * Programmatic reconciliation: verifies that General Ledger final balance
     * for an account strictly matches the Trial Balance balance.
     *
     * @param string $accountCode
     * @param array $filters
     * @return array
     */
    public function reconcileAccount(string $accountCode, array $filters = []): array
    {
        $ledger = $this->getGeneralLedger(array_merge($filters, ['account_code' => $accountCode]));
        $trialBalance = $this->getTrialBalance(array_merge($filters, ['digits' => strlen($accountCode)]));

        $ledgerAcc = $ledger['accounts'][$accountCode] ?? null;
        $ledgerBalance = $ledgerAcc ? (float) $ledgerAcc['final_balance'] : 0.00;

        $tbAccount = collect($trialBalance['accounts'])->firstWhere('code', $accountCode);
        $tbNet = $tbAccount ? round((float) $tbAccount['saldo_deudor'] - (float) $tbAccount['saldo_acreedor'], 2) : 0.00;

        $diff = round($ledgerBalance - $tbNet, 2);
        $reconciled = abs($diff) < 0.01;

        return [
            'account_code'         => $accountCode,
            'ledger_final_balance' => $ledgerBalance,
            'trial_balance_net'    => $tbNet,
            'difference'           => $diff,
            'is_reconciled'        => $reconciled,
        ];
    }
}
