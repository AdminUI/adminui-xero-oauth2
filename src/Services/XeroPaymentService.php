<?php

namespace AdminUI\AdminUIXero\Services;

use Illuminate\Support\Carbon;
use AdminUI\AdminUI\Helpers\Money;
use AdminUI\AdminUI\Models\OrderIntegration;
use AdminUI\AdminUI\Models\Payment;
use Illuminate\Support\Facades\Log;
use AdminUI\AdminUIXero\Exceptions\SyncFailedException;
use AdminUI\AdminUIXero\Facades\Xero;

class XeroPaymentService
{
    /**
     * @throws \Exception;
     */
    public function syncPayment(Payment $paymentModel, ?string $processId = null, ?int $integrationId = null): \XeroAPI\XeroPHP\Models\Accounting\Payment
    {
        if (!$processId) {
            throw new \Exception("AdminUI Xero: Can't sync this payment since the order hasn't been processed by Xero yet");
        } else if (empty(config('xero.linked_account'))) {
            throw new \Exception("AdminUI Xero: Can't sync this payment without a linked account selected");
        }

        $invoice = new \XeroAPI\XeroPHP\Models\Accounting\Invoice;
        $invoice->setInvoiceId($processId);

        $account = new \XeroAPI\XeroPHP\Models\Accounting\Account;
        $account->setAccountId(config('xero.linked_account'));

        $payment = new \XeroAPI\XeroPHP\Models\Accounting\Payment;
        $payment->setInvoice($invoice);
        $payment->setAccount($account);
        $payment->setAmount(Money::convertToBase($paymentModel->total));
        if (!empty($paymentModel->transaction_id)) {
            $payment->setReference($paymentModel->transaction_id);
        }
        $payment->setDate((new Carbon($paymentModel->created_at))->format("Y-m-d"));

        $idempotency = "PAYMENT_" . $paymentModel->transaction_id . "_" . $paymentModel->id;
        if (!empty($integrationId)) {
            $idempotency .= "_" . $integrationId;
        }

        $payments = Xero::createPayment($payment, $idempotency);
        $created = $payments?->getPayments()[0] ?? null;
        if (empty($created)) {
            throw new SyncFailedException(
                "Xero rejected payment {$paymentModel->id}: " . (Xero::getLastError() ?? 'no response')
            );
        }
        logger('Payment ID: ' . $paymentModel->id . '|' . $created->getPaymentId());

        return $created;
    }

    /**
     * @throws SyncFailedException When Xero refuses to delete the payment
     */
    public function deletePayment(OrderIntegration $integration): \XeroAPI\XeroPHP\Models\Accounting\Payments
    {
        $paymentDelete = new \XeroAPI\XeroPHP\Models\Accounting\PaymentDelete;
        $paymentDelete->setStatus('DELETED');

        $result = Xero::deletePayment($integration->process_id, $paymentDelete, "DELETE_" . $integration->id);
        if (empty($result)) {
            throw new SyncFailedException(
                "Unable to delete Xero payment {$integration->process_id}: " . (Xero::getLastError() ?? 'no response')
            );
        }

        return $result;
    }
}
