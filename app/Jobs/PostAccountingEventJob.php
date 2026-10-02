<?php

namespace App\Jobs;

use App\Services\Accounting\JournalPostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PostAccountingEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Backoff delays in seconds between retries.
     */
    public array $backoff = [5, 15, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public mixed $eventOrModel,
        public ?string $eventName = null
    ) {
        $this->eventName = $eventName ?: (is_object($eventOrModel) ? get_class($eventOrModel) : 'DirectPost');
    }

    /**
     * Execute the job.
     */
    public function handle(JournalPostingService $postingService): void
    {
        try {
            $postingService->post($this->eventOrModel);
        } catch (Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $postingService->recordFailure($this->eventName, $this->eventOrModel, $e);
            }
            throw $e;
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(Throwable $exception): void
    {
        app(JournalPostingService::class)->recordFailure($this->eventName, $this->eventOrModel, $exception);
    }
}
