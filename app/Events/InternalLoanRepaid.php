<?php

namespace App\Events;

use App\Models\RdrInternalLoan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InternalLoanRepaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param RdrInternalLoan $loan The loan being repaid/liquidated
     * @param float $repaidAmount Amount repaid
     * @param array $extra Additional context (repayment_reference, method, etc.)
     */
    public function __construct(
        public RdrInternalLoan $loan,
        public float $repaidAmount = 0.0,
        public array $extra = []
    ) {}
}
