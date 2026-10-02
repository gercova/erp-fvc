<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\SaleCompleted::class => [
            \App\Listeners\AccountingEventListener::class,
        ],
        \App\Events\CreditNoteIssued::class => [
            \App\Listeners\AccountingEventListener::class,
        ],
        \App\Events\DebitNoteIssued::class => [
            \App\Listeners\AccountingEventListener::class,
        ],
        \App\Events\BillingVoided::class => [
            \App\Listeners\AccountingEventListener::class,
        ],
        \App\Events\PurchaseRecorded::class => [
            \App\Listeners\AccountingEventListener::class,
        ],
        \App\Events\PaymentReceived::class => [
            \App\Listeners\AccountingEventListener::class,
        ],
        \App\Events\CashSessionClosed::class => [
            \App\Listeners\AccountingEventListener::class,
        ],
        \App\Events\CutTransferRegistered::class => [
            \App\Listeners\AccountingEventListener::class,
        ],
        \App\Events\InternalLoanDisbursed::class => [
            \App\Listeners\AccountingEventListener::class,
        ],
        \App\Events\InternalLoanRepaid::class => [
            \App\Listeners\AccountingEventListener::class,
        ],
        \App\Events\ActivityTransactionRecorded::class => [
            \App\Listeners\AccountingEventListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
