<?php

namespace AdminUI\AdminUIXero\Listeners;

use AdminUI\AdminUIXero\Facades\XeroPayment;
use AdminUI\AdminUI\Events\Public\NewPayment;

class SendPaymentToXero extends BaseXeroListener
{
    /**
     * Create a new job instance.
     */
    public function __construct(public NewPayment $event)
    {
    }

    /**
     * Handle the event to push a payment to xero
     */
    public function handle(): void
    {
        $xeroOrder = $this->event->payment->order->integrations()->type('xero')->first();

        $payment = XeroPayment::syncPayment($this->event->payment, $xeroOrder->process_id);
    }
}
