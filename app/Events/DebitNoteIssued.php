<?php

namespace App\Events;

use App\Models\Billing;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DebitNoteIssued
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Billing $debitNote The issued debit note (Type 08)
     * @param Billing|null $parentBilling The referenced original invoice/receipt
     * @param array $extra Additional context
     */
    public function __construct(
        public Billing $debitNote,
        public ?Billing $parentBilling = null,
        public array $extra = []
    ) {}
}
