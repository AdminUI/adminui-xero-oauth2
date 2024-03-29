<?php

namespace AdminUI\AdminUIXero\Services;

use Carbon\Carbon;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUI\Helpers\Money;
use AdminUI\AdminUIXero\Facades\Xero;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use XeroAPI\XeroPHP\Models\Accounting\Invoice;
use XeroAPI\XeroPHP\Models\Accounting\LineItem;

class XeroInvoiceService
{
    /**
     * Create a payment invoice on Xero when given an Order model
     *
     * @param Order $order The order to create an invoice for
     * @param Contact $contact The Xero contact to attach the invoices to
     */
    public function syncOrder(Order $order, Contact $contact): Invoice|bool
    {
        // confirm the order is not empty
        if ($order->orderItems->count() <= 0) {
            return false;
        }

        $items = [];

        // Translate the order lines to an invoiceable data structure
        foreach ($order->orderItems as $item) {
            $items[] = new LineItem([
                'description' => $item->product_name . '(' . $item->sku_code . ')',
                'quantity' => $item->qty,
                'unit_amount' => Money::convertToBase($item->itemPrice['exc_tax']),
                'line_amount' => Money::convertToBase($item->linePrice['exc_tax']),
                'tax_amount' => Money::convertToBase($item->linePrice['tax']),
                'tax_type' => $item->tax_rate == 20 ? 'OUTPUT2' : 'NONE',
                'account_code' => 200,
            ]);
        }

        // Translate the postage to an invoiceable data structure
        $postage = $order->postageRate;
        if ($postage) {
            $items[] = new LineItem([
                'description' => $order->postage_description == '' ? $postage->postageType->name : $order->postage_description,
                'quantity' => 1,
                'unit_amount' => Money::convertToBase($order->postagePrice['exc_tax']),
                'line_amount' => Money::convertToBase($order->postagePrice['exc_tax']),
                'tax_amount' => Money::convertToBase($order->postagePrice['tax']),
                'account_code' => 200,
                'tax_type' => $order->postagePrice['tax_rate'] == 20 ? 'OUTPUT2' : 'NONE',
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
        return $invoices[0];
    }
}
