<?php

namespace App\Listeners;

use App\Services\Accounting\JournalPostingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class AccountingEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Retries count.
     */
    public int $tries = 3;

    /**
     * Backoff in seconds.
     */
    public array $backoff = [5, 15, 60];

    public function __construct(
        protected JournalPostingService $postingService
    ) {}

    /**
     * Handle incoming domain event.
     */
    public function handle(mixed $event): void
    {
        try {
            $this->postingService->post($event);
        } catch (Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $this->postingService->recordFailure(get_class($event), $event, $e);
            }
            throw $e;
        }
    }

    /**
     * Handle failure when retries exhausted.
     */
    public function failed(mixed $event, Throwable $exception): void
    {
        $this->postingService->recordFailure(get_class($event), $event, $exception);
    }
}
