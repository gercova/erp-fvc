<?php

namespace App\Events;

use App\Models\RdrCutTransfer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CutTransferRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param RdrCutTransfer $cutTransfer The registered CUT transfer
     * @param array $extra Additional context
     */
    public function __construct(
        public RdrCutTransfer $cutTransfer,
        public array $extra = []
    ) {}
}
