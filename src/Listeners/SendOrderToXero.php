<?php

namespace AdminUI\AdminUIXero\Listeners;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use AdminUI\AdminUI\Mail\GenericEmail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use AdminUI\AdminUI\Events\Public\NewOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use AdminUI\AdminUIXero\Facades\XeroContact;
use AdminUI\AdminUIXero\Facades\XeroInvoice;
use AdminUI\AdminUIXero\Facades\XeroPayment;

class SendOrderToXero extends BaseXeroListener
{

    /**
     * Create a new job instance.
     */
    public function __construct(public NewOrder $event)
    {
    }

    /**
     * Handle the event to push an order to xero
     */
    public function handle(): void
    {
        // This will push the order to xero
        // Check that Xero Push is enabled in Settings
        $xeroSyncOrders = auiSetting('xero_sync_orders', false);

        // Run checks to see if Xero push is required for this order
        if (!$xeroSyncOrders) {
            return;
        }
        $order = $this->event->order;

        $xero = $order->integration()->type('xero')->firstOrNew();

        if (empty($order->account)) {
            Log::error('Order ' . $order->id . ' failed to sync to Xero because it\'s missing a linked account');
            return;
        }
        if (!empty($xero) && $xero->processed_at) {
            info("Order " . $order->id . " has already been sent to Xero. Skipping.");
            return;
        }

        // Find existing Xero contact or generate a new one
        $contact = XeroContact::getContact($order->account);

        // Generate an invoice
        $invoice = XeroInvoice::syncOrder($order, $contact);

        // Check for any validation errors processing the invoice before saving as processed
        if (!empty($invoice['validation_errors'])) {
            $error = $invoice['validation_errors'][0];
            throw new \Exception($error['message']);
        }

        // Store the invoice information
        $xero->process_id = $invoice['invoice_id'];
        $xero->processed_at = \Carbon\Carbon::now();
        $xero->notes = ($order->admin_notes != '' ? $order->admin_notes . '<br/>' : $order->admin_notes) . 'Xero Invoice Number: ' . $invoice['invoice_number'];
        $xero->save();

        info($order->id . ' was succesfully pushed to Xero with Xero invoice of ' . $invoice['invoice_number']);
    }
}
