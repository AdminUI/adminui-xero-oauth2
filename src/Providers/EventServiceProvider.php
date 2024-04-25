<?php

namespace AdminUI\AdminUIXero\Providers;

use AdminUI\AdminUI\Events\Public\OrderPlaced;
use AdminUI\AdminUI\Events\Public\OrderCancelled;
use AdminUI\AdminUI\Events\Public\PaymentReceived;
use AdminUI\AdminUIXero\Listeners\SendOrderToXero;
use AdminUI\AdminUIXero\Listeners\CancelOrderToXero;
use AdminUI\AdminUIXero\Listeners\SendPaymentToXero;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class => [
            SendOrderToXero::class,
        ],
        PaymentReceived::class => [
            SendPaymentToXero::class,
        ],
        OrderCancelled::class => [
            CancelOrderToXero::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
