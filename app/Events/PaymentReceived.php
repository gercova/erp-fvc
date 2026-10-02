<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Model $document The underlying document (Billing, SaleNote, Buy, AccountPayable, etc.)
     * @param float $amount The installment or payment amount
     * @param int|string $paymentMethodId Payment mode ID or code (1=Efectivo, 2=Yape, etc.)
     * @param int|null $installmentNumber Installment sequence (e.g. Cuota 1, 2)
     * @param int|null $userId User recording the payment
     * @param string|null $reference Reference code, operation number or voucher
     * @param array $extra Additional context
     */
    public function __construct(
        public Model $document,
        public float $amount,
        public int|string $paymentMethodId = 1,
        public ?int $installmentNumber = null,
        public ?int $userId = null,
        public ?string $reference = null,
        public array $extra = []
    ) {}
}
