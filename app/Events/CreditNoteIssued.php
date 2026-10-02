<?php

namespace App\Events;

use App\Models\Billing;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreditNoteIssued
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Billing $creditNote The issued credit note (Type 07)
     * @param Billing|null $parentBilling The referenced original invoice/receipt
     * @param array $extra Additional context (e.g. user_id, reason)
     */
    public function __construct(
        public Billing $creditNote,
        public ?Billing $parentBilling = null,
        public array $extra = []
    ) {}
}
