<?php

namespace App\Events;

use App\Models\Billing;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillingVoided
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Billing $billing The voided billing document
     * @param string $reason Reason for voiding
     * @param array $extra Additional context (e.g. user_id, ticket_sunat)
     */
    public function __construct(
        public Billing $billing,
        public string $reason = '',
        public array $extra = []
    ) {}
}
