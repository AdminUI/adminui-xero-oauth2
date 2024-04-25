<?php

namespace AdminUI\AdminUIXero\Listeners;

use Illuminate\Support\Carbon;
use AdminUI\AdminUI\Models\Order;
use Illuminate\Support\Facades\Log;
use AdminUI\AdminUI\Events\Public\OrderCancelled;
use AdminUI\AdminUIXero\Facades\XeroInvoice;

class CancelOrderToXero extends BaseXeroListener
{
    /**
     * Handle the event to push a payment to xero
     */
    public function handle(OrderCancelled $event): void
    {

        XeroInvoice::voidOrder($event->order);
        Log::debug("Order " . $event->order->id . " was voided on Xero");
    }
}
