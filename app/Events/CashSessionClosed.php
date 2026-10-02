<?php

namespace App\Events;

use App\Models\ArchingCash;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashSessionClosed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param ArchingCash $archingCash The closed cash register session
     * @param float $expectedAmount Expected total amount (float + collections + inflows - outflows)
     * @param float $countedAmount Actual counted amount
     * @param float $difference Surplus (positive) or shortage (negative)
     * @param array $paymentSummary Breakdown of collections by payment method
     * @param array $extra Additional metadata for Treasury and Accounting
     */
    public function __construct(
        public ArchingCash $archingCash,
        public float $expectedAmount,
        public float $countedAmount,
        public float $difference,
        public array $paymentSummary = [],
        public array $extra = []
    ) {}
}
