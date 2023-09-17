<?php

namespace AdminUI\AdminUIXero\Services;

use Carbon\Carbon;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUIXero\Facades\Xero;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use XeroAPI\XeroPHP\Models\Accounting\Invoices;

class XeroInvoiceService
{
    /**
     * Create a payment invoice on Xero when given an Order model
     *
     * @param Order $order The order to create an invoice for
     * @param Contact $contact The Xero contact to attach the invoices to
     */
    public function syncOrder(Order $order, Contact $contact): Invoices|bool
    {
        // confirm the order is not empty
        if ($order->lines->count() <= 0) {
            return false;
        }

        $items = [];

        // Translate the order lines to an invoiceable data structure
        foreach ($order->lines as $item) {
            $items[] = [
                'Description' => $item->product_name . '(' . $item->sku_code . ')',
                'Quantity' => $item->qty,
                'UnitAmount' => $item->item_exc_tax / 100,
                'LineAmount' => $item->line_exc_tax / 100,
                'TaxAmount' => $item->line_tax / 100,
                'AccountCode' => 200,
                'TaxType' => $item->tax_rate == 20 ? 'OUTPUT2' : 'NONE',
            ];
        }

        // Translate the postage to an invoiceable data structure
        $postage = $order->postageRate;
        if ($postage) {
            $items[] = [
                'Description' => $order->postage_description == '' ? $postage->postageType->name : $order->postage_description,
                'Quantity' => 1,
                'UnitAmount' => $order->postage_exc_tax / 100,
                'LineAmount' => $order->postage_exc_tax / 100,
                'TaxAmount' => $order->postage_tax / 100,
                'AccountCode' => 200,
                'TaxType' => $order->postage_exc_tax != $order->postage_inc_tax ? 'OUTPUT2' : 'NONE',
            ];
        }

        // Translate the address to an invoiceable data structure
        $address = $order->billing;
        if ($order->delivery_address_id != $order->billing_address_id) {
            $address = $order->delivery;
        }
        if ($address) {
            $items[] = [
                'Description' => 'Delivery Address: ' . $address->addressee . ', ' . $address->address . ', ' . $address->address_2 . ', ' . $address->town . ', ' . $address->county . ', ' . $address->postcode . '; Tel: ' . $address->phone,
            ];
        }

        $due = Carbon::now()->addDays($order->account->payment_terms ?? 0)->format('Y-m-d');
        $prefix = auiSetting('xero_reference_prefix', '');

        $data = [
            'Type' => 'ACCREC',
            'Contact' => [
                'ContactID' => $contact['ContactID'],
            ],
            'DueDate' => $due,
            'Reference' => $prefix . $order->id,
            'LineAmountTypes' => 'Exclusive',
            'LineItems' => $items,
            'Status' => 'AUTHORISED',
        ];
        return Xero::updateOrCreateInvoices($data);
    }
}
