<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Model $sale The sales document model (Billing or SaleNote)
     * @param string $documentKind 'billing' or 'sale_note'
     * @param array $paymentBreakdown Payment breakdown with method IDs and amounts
     * @param int $warehouseId Warehouse where the sale took place
     * @param int|null $archingCashId Active cash register session ID (arching_cashes)
     * @param array $extra Additional context for accounting entries (Track B)
     */
    public function __construct(
        public Model $sale,
        public string $documentKind,
        public array $paymentBreakdown = [],
        public int $warehouseId = 0,
        public ?int $archingCashId = null,
        public array $extra = []
    ) {}
}
