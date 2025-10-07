<?php

namespace AdminUI\AdminUIXero\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \XeroAPI\XeroPHP\Models\Accounting\Payment syncPayment(\AdminUI\AdminUI\Models\Payment $payment, string $processId = null)
 * @method static \XeroAPI\XeroPHP\Models\Accounting\Payments|\XeroAPI\XeroPHP\Models\Accounting\Error deletePayment(\AdminUI\AdminUI\Models\OrderIntegration $integration)
 * 
 * @see \AdminUI\AdminUIXero\Services\XeroPaymentService
 */
class XeroPayment extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'xero-payment';
    }
}
