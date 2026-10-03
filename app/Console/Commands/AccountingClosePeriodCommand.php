<?php

namespace App\Console\Commands;

use App\Models\AccountingPeriod;
use App\Models\User;
use App\Services\Accounting\PeriodClosingService;
use Illuminate\Console\Command;

class AccountingClosePeriodCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'accounting:close-period
                            {period_code : Código del período contable (ej. 2026-10)}
                            {--annual : Ejecutar cierre anual definitivo con asientos de cierre de gestión y apertura}
                            {--force : Ejecutar sin solicitar confirmación interactiva}
                            {--notes= : Notas adicionales para el registro de auditoría}';

    /**
     * The console command description.
     */
    protected $description = 'Cierra un período contable (mensual o anual) bloqueando nuevas transacciones y generando asientos de cierre.';

    /**
     * Execute the console command.
     */
    public function handle(PeriodClosingService $closingService): int
    {
        $periodCode = $this->argument('period_code');
        $isAnnual   = (bool) $this->option('annual');
        $force      = (bool) $this->option('force');
        $notes      = $this->option('notes');

        $period = AccountingPeriod::where('period_code', $periodCode)->first();
        if (!$period) {
            $this->error("El período contable {$periodCode} no existe.");
            return Command::FAILURE;
        }

        if ($period->isClosed() && !$force) {
            $this->error("El período {$periodCode} ya se encuentra cerrado o bloqueado (Estado: {$period->status}).");
            return Command::FAILURE;
        }

        $closureTypeLabel = $isAnnual ? 'CIERRE ANUAL DEFINITIVO' : 'CIERRE MENSUAL';
        $this->info("Iniciando {$closureTypeLabel} para el período: {$periodCode}...");

        $adminUser = User::first();
        $userId = $adminUser ? $adminUser->id : 1;

        if ($isAnnual) {
            $result = $closingService->closeAnnualPeriod($period, $userId, $notes);
            $this->info("✓ Período {$periodCode} cerrado y bloqueado con éxito (LOCKED).");
            $this->line("  - Resultado Neto del Ejercicio: S/ " . number_format($result['net_result'], 2));
            if ($result['closing_results_entry']) {
                $this->line("  - Asiento de Cierre de Gestión: " . $result['closing_results_entry']->entry_number);
            }
            if ($result['closing_balance_entry']) {
                $this->line("  - Asiento de Cierre Patrimonial: " . $result['closing_balance_entry']->entry_number);
            }
            if ($result['opening_entry']) {
                $this->line("  - Asiento de Apertura siguiente año: " . $result['opening_entry']->entry_number);
            }
        } else {
            $closingService->closeMonthlyPeriod($period, $userId, $notes);
            $this->info("✓ Período {$periodCode} cerrado con éxito (CLOSED). Operaciones congeladas.");
        }

        return Command::SUCCESS;
    }
}
