<?php

namespace App\Notifications;

use App\Models\Budget;
use App\Models\BudgetLine;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BudgetThresholdExceededNotification extends Notification
{
    use Queueable;

    public Budget $budget;
    public BudgetLine $line;
    public float $accruedAmount;
    public float $percentage;
    public float $threshold;

    public function __construct(Budget $budget, BudgetLine $line, float $accruedAmount, float $percentage, float $threshold)
    {
        $this->budget = $budget;
        $this->line = $line;
        $this->accruedAmount = $accruedAmount;
        $this->percentage = $percentage;
        $this->threshold = $threshold;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $accountStr = $this->line->account?->code ?? $this->line->account_code ?? $this->line->category_name ?? 'Partida';
        $monthName = date('F', mktime(0, 0, 0, $this->line->period_month, 10));

        return [
            'type' => 'BUDGET_THRESHOLD_EXCEEDED',
            'budget_id' => $this->budget->id,
            'budget_code' => $this->budget->code,
            'budget_line_id' => $this->line->id,
            'period_month' => $this->line->period_month,
            'account_code' => $this->line->account?->code ?? $this->line->account_code,
            'activity_name' => $this->line->activity?->name ?? $this->line->cost_center_code,
            'current_amount' => (float) $this->line->current_amount,
            'accrued_amount' => $this->accruedAmount,
            'execution_percentage' => $this->percentage,
            'threshold_percentage' => $this->threshold,
            'message' => "Alerta Presupuestal: Partida {$accountStr} (Mes: {$this->line->period_month}) ha alcanzado el {$this->percentage}% de ejecución, superando el umbral configurado de {$this->threshold}%.",
            'url' => route('accounting.budgets.show', $this->budget->id),
        ];
    }
}
