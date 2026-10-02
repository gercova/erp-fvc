<?php

namespace App\Events;

use App\Models\Buy;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Buy $buy The recorded supplier purchase
     * @param array $extra Additional context (e.g. user_id, payment_condition)
     */
    public function __construct(
        public Buy $buy,
        public array $extra = []
    ) {}
}
