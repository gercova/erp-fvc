<?php

namespace App\Events;

use App\Models\RdrInternalLoan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InternalLoanDisbursed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param RdrInternalLoan $loan The disbursed internal loan
     * @param array $extra Additional context
     */
    public function __construct(
        public RdrInternalLoan $loan,
        public array $extra = []
    ) {}
}
