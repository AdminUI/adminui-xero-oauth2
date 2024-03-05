<?php

namespace AdminUI\AdminUIXero\Listeners;

use Illuminate\Support\Carbon;
use AdminUI\AdminUI\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use AdminUI\AdminUIXero\Facades\XeroPayment;
use AdminUI\AdminUI\Events\Public\PaymentReceived;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class SendPaymentToXero extends BaseXeroListener implements ShouldHandleEventsAfterCommit
{
    use InteractsWithQueue;

    public $delay = 5;

    /**
     * Handle the event to push a payment to xero
     */
    public function handle(PaymentReceived $event): void
    {
        $paymentType = $event->payment->payment_type ?? null;

        if (in_array($paymentType, ['bacs', 'cash', 'cheque', 'credit'])) {
            Log::debug("Payment type not required to send: " . $paymentType);
            return;
        }


        // If the payment's order has not been turned into an invoice yet, delay this job
        $order = Order::find($event->payment->order->id);
        $xeroOrder = $order->integrations()->type('xero')->first();
        if (empty($order->invoice_id) || empty($xeroOrder)) {
            $this->release(10);
            return;
        }

        $payment = XeroPayment::syncPayment($event->payment, $xeroOrder->process_id);
        $event->payment->integrations()->create([
            'type' => 'xero',
            'notes' => 'Recorded Xero payment against invoice ' . $xeroOrder->process_id,
            'process_id' => $payment->getPaymentId(),
            'processed_at' => Carbon::parse($payment->getDateAsDate())
        ]);
        Log::debug("Payment sent to Xero");
    }
}
