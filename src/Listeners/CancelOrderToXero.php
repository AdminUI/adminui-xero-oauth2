<?php

namespace AdminUI\AdminUIXero\Listeners;

use Illuminate\Support\Facades\Log;
use AdminUI\AdminUIXero\Facades\Xero;
use AdminUI\AdminUIXero\Facades\XeroInvoice;
use AdminUI\AdminUI\Events\Public\OrderCancelled;

class CancelOrderToXero extends BaseXeroListener
{
    /**
     * Handle the event to push a payment to xero
     */
    public function handle(OrderCancelled $event): void
    {
        if (!Xero::isConnected()) {
            return;
        }

        XeroInvoice::voidOrder($event->order);
        Log::debug("Order " . $event->order->id . " was voided on Xero");
    }
}
