<?php

namespace App\Console\Commands;

use App\Enums\JournalStatus;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AccountingCheckIntegrityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:check-integrity
                            {--year= : Filtrar comprobación por ejercicio fiscal (ej. 2026)}
                            {--fix : Intentar corregir descuadres menores o regenerar totales de cabecera}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica la integridad de la base contable: balance general, asientos huérfanos y períodos contables.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = $this->option('year') ? (int) $this->option('year') : null;
        $fix = (bool) $this->option('fix');

        $this->info("===============================================================");
        $this->info("   ERP-FVC: AUDITORÍA DE INTEGRIDAD CONTABLE Y CIERRE (B7)    ");
        $this->info("===============================================================");
        if ($year) {
            $this->line("Filtro de ejercicio fiscal: {$year}");
        }

        $issuesFound = 0;
        $reportSummary = [];

        // -------------------------------------------------------------
        // CHECK 1: General Balancing of Journal Entries (Debit == Credit)
        // -------------------------------------------------------------
        $this->line("\n[1/3] Verificando balance de asientos contables individuales...");
        $entriesQuery = JournalEntry::query()
            ->when($year, function ($q) use ($year) {
                $q->whereHas('period', fn($pq) => $pq->where('fiscal_year', $year));
            });

        $totalEntriesChecked = $entriesQuery->count();
        $unbalancedEntries = [];

        // Query entries with discrepancy between total_debit and total_credit or line sums
        $entries = (clone $entriesQuery)->with('lines')->get();
        foreach ($entries as $entry) {
            $headerDiff = abs((float) $entry->total_debit - (float) $entry->total_credit);
            $linesDebit = (float) $entry->lines->sum('debit');
            $linesCredit = (float) $entry->lines->sum('credit');
            $linesDiff = abs($linesDebit - $linesCredit);

            if ($headerDiff > 0.001 || $linesDiff > 0.001) {
                $unbalancedEntries[] = [
                    'id'           => $entry->id,
                    'entry_number' => $entry->entry_number,
                    'status'       => $entry->status instanceof JournalStatus ? $entry->status->value : $entry->status,
                    'header_diff'  => number_format($headerDiff, 2),
                    'lines_diff'   => number_format($linesDiff, 2),
                ];

                if ($fix && $linesDiff <= 0.001) {
                    $entry->update([
                        'total_debit'  => $linesDebit,
                        'total_credit' => $linesCredit,
                    ]);
                }
            }
        }

        if (count($unbalancedEntries) === 0) {
            $this->info("  ✓ Todos los asientos ({$totalEntriesChecked}) tienen partida doble cuadrada (Débito == Crédito).");
            $reportSummary[] = ['Balance de Asientos', 'CORRECTO', "{$totalEntriesChecked} asientos verificados sin descuadre"];
        } else {
            $issuesFound += count($unbalancedEntries);
            $this->error("  ✗ Se encontraron " . count($unbalancedEntries) . " asientos con descuadre contable.");
            $this->table(['ID', 'Número Asiento', 'Estado', 'Dif. Cabecera', 'Dif. Líneas'], $unbalancedEntries);
            $reportSummary[] = ['Balance de Asientos', 'DESCUADRADO', count($unbalancedEntries) . " asientos con diferencias"];
        }

        // -------------------------------------------------------------
        // CHECK 2: Orphaned Entries & Referential Integrity
        // -------------------------------------------------------------
        $this->line("\n[2/3] Verificando registros huérfanos e integridad referencial...");

        $orphanedLinesCount = JournalEntryLine::query()
            ->whereDoesntHave('journalEntry')
            ->count();

        $linesMissingAccountCount = JournalEntryLine::query()
            ->whereDoesntHave('account')
            ->count();

        $entriesWithoutLinesCount = JournalEntry::query()
            ->doesntHave('lines')
            ->count();

        $entriesMissingPeriodCount = JournalEntry::query()
            ->whereDoesntHave('period')
            ->count();

        $totalOrphans = $orphanedLinesCount + $linesMissingAccountCount + $entriesWithoutLinesCount + $entriesMissingPeriodCount;

        if ($totalOrphans === 0) {
            $this->info("  ✓ Integridad referencial perfecta: 0 líneas o asientos huérfanos.");
            $reportSummary[] = ['Registros Huérfanos', 'CORRECTO', '0 huérfanos (Líneas, Asientos, Cuentas, Períodos)'];
        } else {
            $issuesFound += $totalOrphans;
            $this->error("  ✗ Se detectaron registros huérfanos o inconsistencias de relación:");
            if ($orphanedLinesCount > 0) {
                $this->warn("    - Líneas sin asiento padre: {$orphanedLinesCount}");
            }
            if ($linesMissingAccountCount > 0) {
                $this->warn("    - Líneas con cuenta contable inexistente: {$linesMissingAccountCount}");
            }
            if ($entriesWithoutLinesCount > 0) {
                $this->warn("    - Asientos sin ninguna línea de detalle: {$entriesWithoutLinesCount}");
            }
            if ($entriesMissingPeriodCount > 0) {
                $this->warn("    - Asientos sin período contable asociado: {$entriesMissingPeriodCount}");
            }
            $reportSummary[] = ['Registros Huérfanos', 'INCONSISTENTE', "{$totalOrphans} inconsistencias referenciales"];
        }

        // -------------------------------------------------------------
        // CHECK 3: Balance by Accounting Period
        // -------------------------------------------------------------
        $this->line("\n[3/3] Verificando balance consolidado por período contable...");

        $periodsQuery = AccountingPeriod::query()
            ->when($year, fn($q) => $q->where('fiscal_year', $year))
            ->orderBy('fiscal_year')
            ->orderBy('month');

        $periods = $periodsQuery->get();
        $unbalancedPeriods = [];

        foreach ($periods as $period) {
            $totals = JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->where('journal_entries.accounting_period_id', $period->id)
                ->where('journal_entries.status', JournalStatus::POSTED->value)
                ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit, COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
                ->first();

            $debit = (float) ($totals->total_debit ?? 0);
            $credit = (float) ($totals->total_credit ?? 0);
            $diff = abs($debit - $credit);

            if ($diff > 0.001) {
                $unbalancedPeriods[] = [
                    'period'     => $period->period_code,
                    'status'     => $period->status,
                    'debit'      => number_format($debit, 2),
                    'credit'     => number_format($credit, 2),
                    'difference' => number_format($diff, 2),
                ];
            }
        }

        if (count($unbalancedPeriods) === 0) {
            $this->info("  ✓ Todos los períodos ({$periods->count()}) están perfectamente balanceados.");
            $reportSummary[] = ['Balance por Período', 'CORRECTO', "{$periods->count()} períodos verificados y cuadrados"];
        } else {
            $issuesFound += count($unbalancedPeriods);
            $this->error("  ✗ Se encontraron " . count($unbalancedPeriods) . " períodos desbalanceados:");
            $this->table(['Período', 'Estado', 'Total Débito', 'Total Crédito', 'Diferencia'], $unbalancedPeriods);
            $reportSummary[] = ['Balance por Período', 'DESCUADRADO', count($unbalancedPeriods) . " períodos con diferencias"];
        }

        // -------------------------------------------------------------
        // FINAL SUMMARY & CONCLUSION
        // -------------------------------------------------------------
        $this->line("\n----------------- RESUMEN DE CONTROL DE INTEGRIDAD -----------------");
        $this->table(['Módulo / Verificación', 'Resultado', 'Detalle'], $reportSummary);

        if ($issuesFound === 0) {
            $this->info("\n[OK] INTEGRIDAD CONTABLE VERIFICADA CON ÉXITO.");
            $this->info("Estado: LIMPIO / CLEAN. Todos los asientos están cuadrados, no existen registros huérfanos y todos los períodos están balanceados.");
            return Command::SUCCESS;
        }

        $this->error("\n[FALLO] Se detectaron {$issuesFound} inconsistencias contables en la base de datos.");
        return Command::FAILURE;
    }
}
