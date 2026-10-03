<?php

namespace App\Console\Commands;

use App\Enums\AgreementStatus;
use App\Enums\InstallmentStatus;
use App\Enums\ObligationStatus;
use App\Models\Agreement;
use App\Models\AgreementAlertLog;
use App\Models\AgreementInstallment;
use App\Models\AgreementObligation;
use App\Models\User;
use App\Notifications\AgreementDeadlineAlertNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckAgreementAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'agreements:alerts';

    /**
     * The console command description.
     */
    protected $description = 'Evalúa plazos de convenios, compromisos y cuotas, emitiendo alertas a 30, 15 y 7 días previos';

    /**
     * Configured alert thresholds in days.
     */
    protected array $thresholds = [30, 15, 7];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando evaluación de alertas para convenios, compromisos y cuotas...');

        $stats = [
            'agreements'   => 0,
            'obligations'  => 0,
            'installments' => 0,
            'skipped'      => 0,
        ];

        DB::transaction(function () use (&$stats) {
            $this->processAgreements($stats);
            $this->processObligations($stats);
            $this->processInstallments($stats);
        });

        $this->info("Evaluación finalizada exitosamente.");
        $this->line("- Alertas de convenios enviadas: {$stats['agreements']}");
        $this->line("- Alertas de compromisos enviadas: {$stats['obligations']}");
        $this->line("- Alertas de cuotas enviadas: {$stats['installments']}");
        $this->line("- Omitidas (ya alertadas previamente): {$stats['skipped']}");

        return Command::SUCCESS;
    }

    /**
     * Determine applicable threshold (30, 15, 7) based on days remaining.
     */
    public function resolveThreshold(int $daysRemaining): ?int
    {
        if ($daysRemaining >= 16 && $daysRemaining <= 30) {
            return 30;
        }
        if ($daysRemaining >= 8 && $daysRemaining <= 15) {
            return 15;
        }
        if ($daysRemaining >= 1 && $daysRemaining <= 7) {
            return 7;
        }
        return null;
    }

    /**
     * Process institutional agreements approaching expiration.
     */
    protected function processAgreements(array &$stats): void
    {
        $agreements = Agreement::whereIn('status', [AgreementStatus::ACTIVE->value, AgreementStatus::EXPIRING_SOON->value])
            ->whereNotNull('end_date')
            ->get();

        foreach ($agreements as $agreement) {
            $endDate = Carbon::parse($agreement->end_date)->startOfDay();
            $daysRemaining = (int) now()->startOfDay()->diffInDays($endDate, false);

            if ($daysRemaining <= 0) {
                if ($agreement->status !== AgreementStatus::EXPIRED && $agreement->status !== AgreementStatus::RESOLVED) {
                    $agreement->update(['status' => AgreementStatus::EXPIRING_SOON]);
                }
                continue;
            }

            $threshold = $this->resolveThreshold($daysRemaining);
            if (!$threshold) {
                continue;
            }

            if ($this->hasAlreadyAlerted(Agreement::class, $agreement->id, $threshold)) {
                $stats['skipped']++;
                continue;
            }

            $recipient = $agreement->coordinator ?? $agreement->creator ?? $this->findFallbackRecipient();
            $message = "Alerta de Convenio: El convenio {$agreement->code} - '{$agreement->title}' vencerá en {$daysRemaining} días (Fecha límite: {$endDate->format('d/m/Y')}).";

            $this->dispatchAlert(
                targetType: 'AGREEMENT',
                targetModel: $agreement,
                threshold: $threshold,
                targetDate: $endDate->toDateString(),
                daysRemaining: $daysRemaining,
                message: $message,
                recipient: $recipient,
                url: route('agreements.show', $agreement->id)
            );

            $stats['agreements']++;
        }
    }

    /**
     * Process obligations (institution and counterparty) approaching deadline.
     */
    protected function processObligations(array &$stats): void
    {
        $obligations = AgreementObligation::whereIn('status', [ObligationStatus::PENDING->value, ObligationStatus::IN_PROGRESS->value])
            ->whereNotNull('due_date')
            ->with(['agreement.coordinator', 'responsibleUser', 'verifier'])
            ->get();

        foreach ($obligations as $obligation) {
            $dueDate = Carbon::parse($obligation->due_date)->startOfDay();
            $daysRemaining = (int) now()->startOfDay()->diffInDays($dueDate, false);

            if ($daysRemaining < 0) {
                $obligation->update(['status' => ObligationStatus::OVERDUE]);
                continue;
            }

            $threshold = $this->resolveThreshold($daysRemaining);
            if (!$threshold) {
                continue;
            }

            if ($this->hasAlreadyAlerted(AgreementObligation::class, $obligation->id, $threshold)) {
                $stats['skipped']++;
                continue;
            }

            $agreement = $obligation->agreement;
            $partyLabel = $obligation->responsible_party === 'COUNTERPARTY' ? 'de la Contraparte' : 'Institucional';
            $recipient = $obligation->responsibleUser ?? $agreement?->coordinator ?? $this->findFallbackRecipient();
            $message = "Alerta de Compromiso {$partyLabel}: El compromiso '{$obligation->title}' del convenio {$agreement?->code} vence en {$daysRemaining} días (Vencimiento: {$dueDate->format('d/m/Y')}).";

            $this->dispatchAlert(
                targetType: 'OBLIGATION',
                targetModel: $obligation,
                threshold: $threshold,
                targetDate: $dueDate->toDateString(),
                daysRemaining: $daysRemaining,
                message: $message,
                recipient: $recipient,
                url: $agreement ? route('agreements.show', $agreement->id) : null
            );

            $stats['obligations']++;
        }
    }

    /**
     * Process agreement installments approaching scheduled due date.
     */
    protected function processInstallments(array &$stats): void
    {
        $installments = AgreementInstallment::whereIn('status', [
                InstallmentStatus::PENDING->value,
                InstallmentStatus::SCHEDULED->value,
            ])
            ->whereNotNull('due_date')
            ->whereNull('billing_id')
            ->whereNull('sale_note_id')
            ->with(['agreement.coordinator', 'agreement.creator'])
            ->get();

        foreach ($installments as $installment) {
            $dueDate = Carbon::parse($installment->due_date)->startOfDay();
            $daysRemaining = (int) now()->startOfDay()->diffInDays($dueDate, false);

            if ($daysRemaining < 0) {
                $installment->update(['status' => InstallmentStatus::OVERDUE]);
                continue;
            }

            $threshold = $this->resolveThreshold($daysRemaining);
            if (!$threshold) {
                continue;
            }

            if ($this->hasAlreadyAlerted(AgreementInstallment::class, $installment->id, $threshold)) {
                $stats['skipped']++;
                continue;
            }

            $agreement = $installment->agreement;
            $recipient = $agreement?->coordinator ?? $agreement?->creator ?? $this->findFallbackRecipient();
            $amountFormatted = number_format((float)$installment->amount, 2);
            $message = "Alerta de Cuota Financiera: La Cuota N° {$installment->installment_number} ({$installment->currency} {$amountFormatted}) del convenio {$agreement?->code} vence en {$daysRemaining} días (Vencimiento: {$dueDate->format('d/m/Y')}).";

            $this->dispatchAlert(
                targetType: 'INSTALLMENT',
                targetModel: $installment,
                threshold: $threshold,
                targetDate: $dueDate->toDateString(),
                daysRemaining: $daysRemaining,
                message: $message,
                recipient: $recipient,
                url: $agreement ? route('agreements.show', $agreement->id) : null
            );

            $stats['installments']++;
        }
    }

    /**
     * Verify whether an alert for a specific model and threshold has already been recorded.
     */
    protected function hasAlreadyAlerted(string $class, int $id, int $threshold): bool
    {
        return AgreementAlertLog::where('alertable_type', $class)
            ->where('alertable_id', $id)
            ->where('threshold_days', $threshold)
            ->exists();
    }

    /**
     * Dispatch database notification and log alert record for idempotency.
     */
    protected function dispatchAlert(
        string $targetType,
        $targetModel,
        int $threshold,
        string $targetDate,
        int $daysRemaining,
        string $message,
        ?User $recipient,
        ?string $url
    ): void {
        $notification = new AgreementDeadlineAlertNotification(
            targetType: $targetType,
            targetModel: $targetModel,
            thresholdDays: $threshold,
            targetDate: $targetDate,
            daysRemaining: $daysRemaining,
            alertMessage: $message,
            actionUrl: $url
        );

        $notificationId = null;
        if ($recipient) {
            $recipient->notify($notification);
            // Retrieve created notification UUID
            $notificationId = $recipient->notifications()->latest()->first()?->id;
        }

        // Record log to strictly guarantee "alerts are sent only once per threshold"
        AgreementAlertLog::create([
            'alertable_type'    => get_class($targetModel),
            'alertable_id'      => $targetModel->id,
            'threshold_days'    => $threshold,
            'target_date'       => $targetDate,
            'recipient_user_id' => $recipient?->id,
            'notification_id'   => $notificationId,
            'alert_type'        => 'DEADLINE_WARNING',
            'message'           => $message,
            'sent_at'           => now(),
        ]);

        // Update alerted_thresholds on model
        $thresholds = is_array($targetModel->alerted_thresholds)
            ? $targetModel->alerted_thresholds
            : json_decode($targetModel->alerted_thresholds ?? '[]', true);

        if (!in_array($threshold, $thresholds)) {
            $thresholds[] = $threshold;
            $targetModel->updateQuietly(['alerted_thresholds' => $thresholds]);
        }
    }

    /**
     * Find active admin user if no specific recipient is associated.
     */
    protected function findFallbackRecipient(): ?User
    {
        return User::where('estado', 1)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['SUPERADMIN', 'ADMIN', 'COORDINADOR']))
            ->first() ?? User::where('estado', 1)->first();
    }
}
