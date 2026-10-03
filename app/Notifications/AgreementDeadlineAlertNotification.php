<?php

namespace App\Notifications;

use App\Models\Agreement;
use App\Models\AgreementInstallment;
use App\Models\AgreementObligation;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class AgreementDeadlineAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $targetType, // 'AGREEMENT', 'OBLIGATION', 'INSTALLMENT'
        public Model $targetModel,
        public int $thresholdDays, // 30, 15, 7
        public string $targetDate,
        public int $daysRemaining,
        public string $alertMessage,
        public ?string $actionUrl = null
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toArray(mixed $notifiable): array
    {
        $agreementId = match (true) {
            $this->targetModel instanceof Agreement            => $this->targetModel->id,
            $this->targetModel instanceof AgreementObligation  => $this->targetModel->agreement_id,
            $this->targetModel instanceof AgreementInstallment => $this->targetModel->agreement_id,
            default                                            => null,
        };

        $url = $this->actionUrl ?? ($agreementId ? route('agreements.show', $agreementId) : '#');

        return [
            'type'           => "AGREEMENT_{$this->targetType}_DEADLINE_ALERT",
            'target_type'    => $this->targetType,
            'target_id'      => $this->targetModel->id,
            'agreement_id'   => $agreementId,
            'threshold_days' => $this->thresholdDays,
            'target_date'    => $this->targetDate,
            'days_remaining' => $this->daysRemaining,
            'message'        => $this->alertMessage,
            'url'            => $url,
            'created_at'     => now()->toIso8601String(),
        ];
    }
}
