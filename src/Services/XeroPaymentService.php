<?php

namespace AdminUI\AdminUIXero\Services;

use AdminUI\AdminUI\Helpers\Money;
use AdminUI\AdminUI\Models\Payment;
use AdminUI\AdminUIXero\Facades\Xero;

class XeroPaymentService
{
    public function syncPayment(Payment $payment, string $processId = null): \XeroAPI\XeroPHP\Models\Accounting\Payment
    {
        $payments = Xero::createPayment([
            'invoice' => [
                'invoice_id' => $processId
            ],
            'account' => [
                'code' => ''
            ],
            'date' => $payment->created_at->format('Y-m-d'),
            'amount' => Money::convertToBase($payment->total),
            'reference' => $payment->transaction_id
        ]);

        return $payments[0];
    }
}
