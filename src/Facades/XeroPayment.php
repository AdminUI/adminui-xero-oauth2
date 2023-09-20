<?php

namespace AdminUI\AdminUIXero\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \XeroAPI\XeroPHP\Models\Accounting\Payment syncPayment(\AdminUI\AdminUI\Models\Payment $payment, string $processId = null)
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
