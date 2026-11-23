<?php

namespace AdminUI\AdminUIXero\Services;

use Carbon\Carbon;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUI\Helpers\Money;
use Illuminate\Support\Facades\Log;
use AdminUI\AdminUIXero\Exceptions\SyncFailedException;
use AdminUI\AdminUIXero\Facades\Xero;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use XeroAPI\XeroPHP\Models\Accounting\Invoice;
use XeroAPI\XeroPHP\Models\Accounting\Invoices;
use XeroAPI\XeroPHP\Models\Accounting\LineItem;
use XeroAPI\XeroPHP\Models\Accounting\PaymentDelete;

class XeroInvoiceService
{
    /**
     * Create a payment invoice on Xero when given an Order model
     *
     * @param Order $order The order to create an invoice for
     * @param Contact $contact The Xero contact to attach the invoices to
     * @throws SyncFailedException When Xero rejects the invoice
     */
    public function syncOrder(Order $order, Contact $contact): Invoice|bool
    {
        // confirm the order is not empty
        if ($order->orderItems->count() <= 0) {
            return false;
        }

        $items = [];
        $accountCode = $this->getSalesAccountCode();

        // Translate the order lines to an invoiceable data structure
        foreach ($order->orderItems as $item) {
            // The product may have been deleted since the order was placed
            $sku = $item->orderable?->sku_code;
            $items[] = new LineItem([
                'description' => $item->product_name . (!empty($sku) ? ' (' . $sku . ')' : ''),
                'quantity' => $item->qty,
                'unit_amount' => Money::convertToBase($item->itemPrice['exc_tax']),
                'line_amount' => Money::convertToBase($item->linePrice['exc_tax']),
                'tax_amount' => Money::convertToBase($item->linePrice['tax']),
                'tax_type' => $this->getTaxType($item->tax_rate),
                'account_code' => $accountCode,
            ]);
        }

        // Translate the postage to an invoiceable data structure
        $postage = $order->postageRate;
        if ($postage) {
            $items[] = new LineItem([
                'description' => ($postage->postageType->name ?? 'Postage') . ' (WEBPOST-' . $order->postage_rate_id . ')',
                'quantity' => 1,
                'unit_amount' => Money::convertToBase($order->postagePrice['exc_tax']),
                'line_amount' => Money::convertToBase($order->postagePrice['exc_tax']),
                'tax_amount' => Money::convertToBase($order->postagePrice['tax']),
                'account_code' => $accountCode,
                'tax_type' => $this->getTaxType($order->postagePrice['tax_rate']),
            ]);
        }

        // Translate the address to an invoiceable data structure
        $address = $order->billing;
        if ($order->delivery_id != $order->billing_id) {
            $address = $order->delivery;
        }
        if ($address) {
            $items[] = new LineItem([
                'description' => 'Delivery Address: ' . $address->addressee . ', ' . $address->address . ', ' . $address->address_2 . ', ' . $address->town . ', ' . $address->county . ', ' . $address->postcode . '; Tel: ' . $address->phone,
            ]);
        }

        $due = Carbon::now()->addDays($order->account->payment_terms ?? 0)->format('Y-m-d');
        $prefix = config('settings.invoice_prefix', '');

        $data = new Invoice([
            'type' => 'ACCREC',
            'contact' => new Contact([
                'contact_id' => $contact['contact_id'],
            ]),
            'due_date' => $due,
            'reference' => $prefix . $order->invoice_id,
            'line_amount_types' => 'Exclusive',
            'line_items' => $items,
            'status' => 'AUTHORISED',
        ]);
        $invoices = Xero::updateOrCreateInvoices($data);
        $invoice = $invoices?->getInvoices()[0] ?? null;
        if (empty($invoice)) {
            throw new SyncFailedException(
                "Xero rejected the invoice for order {$order->id}: " . (Xero::getLastError() ?? 'no response')
            );
        }

        return $invoice;
    }

    /**
     * The Xero account code that sales are recorded against
     */
    protected function getSalesAccountCode(): string
    {
        return (string) (auiSetting('xero_sales_account_code') ?: '200');
    }

    /**
     * Map an AdminUI tax rate to a Xero tax type.
     * Standard-rated (20%) lines use the configured tax type; everything else is sent as having no tax.
     */
    protected function getTaxType($taxRate): string
    {
        return (float) $taxRate === 20.0
            ? (string) (auiSetting('xero_standard_tax_type') ?: 'OUTPUT2')
            : 'NONE';
    }

    /**
     * Void an order's invoice on Xero, removing any payments recorded against it first
     *
     * @throws SyncFailedException When the invoice can't be retrieved or voided
     */
    public function voidOrder(Order $order): void
    {
        $xeroOrder = $order->integrations()->type('xero')->first();
        if (empty($xeroOrder?->process_id)) {
            Log::debug("Order " . $order->id . " was never synced to Xero, so there is no invoice to void");
            return;
        }

        /** @var Invoices|null $apiResponse */
        $apiResponse = Xero::getInvoice($xeroOrder->process_id, ["payments"]);
        if (empty($apiResponse)) {
            throw new SyncFailedException(
                "Unable to retrieve Xero invoice {$xeroOrder->process_id} for order {$order->id}: " . (Xero::getLastError() ?? 'no response')
            );
        }

        $foundInvoices = $apiResponse->getInvoices();
        $invoicesModel = new Invoices();
        $processedInvoices = [];

        foreach ($foundInvoices as $foundInvoice) {
            if ($foundInvoice->getStatus() === Invoice::STATUS_VOIDED) {
                continue;
            }

            foreach ($foundInvoice->getPayments() ?? [] as $payment) {
                $paymentDelete = new PaymentDelete();
                $paymentDelete->setStatus('DELETED');

                if (Xero::deletePayment($payment->getPaymentId(), $paymentDelete) === null) {
                    throw new SyncFailedException(
                        "Unable to delete payment {$payment->getPaymentId()} from Xero invoice {$xeroOrder->process_id}: " . (Xero::getLastError() ?? 'no response')
                    );
                }
                Log::debug("Payment " . $payment->getPaymentId() . " was deleted from invoice.");
            }

            $foundInvoice->setStatus(Invoice::STATUS_VOIDED);
            $processedInvoices[] = $foundInvoice;
        }

        if (empty($processedInvoices)) {
            return;
        }

        $invoicesModel->setInvoices($processedInvoices);
        if (Xero::updateInvoice($xeroOrder->process_id, $invoicesModel) === null) {
            throw new SyncFailedException(
                "Unable to void Xero invoice {$xeroOrder->process_id} for order {$order->id}: " . (Xero::getLastError() ?? 'no response')
            );
        }
    }
}
