<?php

namespace AdminUI\AdminUIXero\Providers;

use AdminUI\AdminUI\Events\Public\OrderCompleted;
use AdminUI\AdminUI\Events\Public\PaymentReceived;
use AdminUI\AdminUIXero\Listeners\SendOrderToXero;
use AdminUI\AdminUIXero\Listeners\SendPaymentToXero;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCompleted::class => [
            SendOrderToXero::class,
        ],
        PaymentReceived::class => [
            SendPaymentToXero::class,
        ]
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
