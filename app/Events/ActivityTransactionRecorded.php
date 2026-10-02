<?php

namespace App\Events;

use App\Models\ActivityTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityTransactionRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param ActivityTransaction $transaction The recorded activity transaction
     * @param array $extra Additional context
     */
    public function __construct(
        public ActivityTransaction $transaction,
        public array $extra = []
    ) {}
}
