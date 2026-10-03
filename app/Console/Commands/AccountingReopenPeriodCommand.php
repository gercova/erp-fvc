<?php

namespace App\Console\Commands;

use App\Models\AccountingPeriod;
use App\Models\User;
use App\Services\Accounting\PeriodClosingService;
use Illuminate\Console\Command;

class AccountingReopenPeriodCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'accounting:reopen-period
                            {period_code : Código del período contable (ej. 2026-10)}
                            {--reason= : Motivo o justificación obligatoria para la reapertura auditada}';

    /**
     * The console command description.
     */
    protected $description = 'Reabre un período contable cerrado con registro obligatorio de auditoría y motivo.';

    /**
     * Execute the console command.
     */
    public function handle(PeriodClosingService $closingService): int
    {
        $periodCode = $this->argument('period_code');
        $reason     = $this->option('reason');

        if (empty($reason)) {
            $this->error("Error: Debe proporcionar un motivo para la reapertura mediante la opción --reason=\"Justificación...\"");
            return Command::FAILURE;
        }

        $period = AccountingPeriod::where('period_code', $periodCode)->first();
        if (!$period) {
            $this->error("El período contable {$periodCode} no existe.");
            return Command::FAILURE;
        }

        if ($period->isOpen()) {
            $this->warn("El período {$periodCode} ya se encuentra abierto (OPEN).");
            return Command::SUCCESS;
        }

        $adminUser = User::first();
        $userId = $adminUser ? $adminUser->id : 1;

        $closingService->reopenPeriod($period, $userId, $reason);

        $this->info("✓ Período {$periodCode} reabierto con éxito (Estado: OPEN).");
        $this->line("  - Motivo registrado en auditoría: {$reason}");
        $this->line("  - Usuario responsable: #" . $userId);

        return Command::SUCCESS;
    }
}
