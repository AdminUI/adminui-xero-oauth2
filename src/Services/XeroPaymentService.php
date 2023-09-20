<?php

namespace AdminUI\AdminUIXero\Services;

use AdminUI\AdminUI\Helpers\Money;
use AdminUI\AdminUI\Models\Payment;
use AdminUI\AdminUIXero\Facades\Xero;

class XeroPaymentService
{
    /**
     * @throws \Exception;
     */
    public function syncPayment(Payment $payment, string $processId = null): \XeroAPI\XeroPHP\Models\Accounting\Payment
    {
        if (!$processId) {
            throw new \Exception("AdminUI Xero: Can't sync this payment since the order hasn't been processed by Xero yet");
        }

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
