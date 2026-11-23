<?php

namespace AdminUI\AdminUIXero\Listeners;

use Throwable;
use Illuminate\Support\Carbon;
use AdminUI\AdminUI\Models\Order;
use Illuminate\Support\Facades\Log;
use AdminUI\AdminUI\Enums\PaymentStatus;
use Illuminate\Queue\InteractsWithQueue;
use AdminUI\AdminUIXero\Facades\XeroPayment;
use AdminUI\AdminUI\Events\Public\PaymentReceived;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class SendPaymentToXero extends BaseXeroListener implements ShouldHandleEventsAfterCommit
{
    use InteractsWithQueue;

    public $delay = 5;

    /**
     * Exceptions (e.g. Xero errors) are retried with increasing delays
     */
    public $backoff = [30, 120, 600];

    /**
     * Waiting for the order's invoice releases the job back to the queue, which uses up attempts.
     * Allow a generous window instead of a fixed number of attempts so slow order syncs don't
     * cause payments to fail permanently.
     */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(6);
    }

    /**
     * Handle the event to push a payment to xero
     */
    public function handle(PaymentReceived $event): void
    {
        $paymentType = $event->payment->payment_type ?? null;

        if (in_array($paymentType, auiSetting('xero_sync_payment_methods') ?? []) === false) {
            Log::debug("Payment type not required to send: " . $paymentType);
            return;
        }

        if ($event->payment->status != PaymentStatus::PAID) {
            logger($event->payment->id . ' is not marked as paid');
            return;
        }

        $order = $event->payment->order_id ? Order::find($event->payment->order_id) : null;
        if (empty($order)) {
            Log::warning("Payment " . $event->payment->id . " has no order, so it can't be sent to Xero");
            return;
        }
        if (empty($order->account)) {
            // Orders without an account are never invoiced on Xero, so waiting would never succeed
            Log::warning("Payment " . $event->payment->id . " belongs to order " . $order->id . " which has no account, so it can't be sent to Xero");
            return;
        }

        // If the payment's order has not been turned into an invoice yet, wait and try again
        $xeroOrder = $order->integrations()->type('xero')->first();
        if (empty($order->invoice_id) || empty($xeroOrder?->process_id)) {
            $this->release(min(60 * $this->attempts(), 900));
            return;
        }

        logger('Sending payment to xero: ' . $event->payment->id . ' for order: ' . $order->invoice_id);
        $payment = XeroPayment::syncPayment($event->payment, $xeroOrder->process_id);

        /** @var \DateTime $processedAt */
        $processedAt = $payment->getDateAsDate();
        $event->payment->integrations()->create([
            'type' => 'xero',
            'notes' => 'Recorded Xero payment against invoice ' . $xeroOrder->process_id,
            'process_id' => $payment->getPaymentId(),
            'processed_at' => Carbon::parse($processedAt)
        ]);
        Log::debug("Payment sent to Xero");
    }

    public function failed(mixed $event, Throwable $exception): void
    {
        Log::error("Failed to send payment to Xero: " . $exception->getMessage());
    }
}
