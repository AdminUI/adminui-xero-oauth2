<?php

namespace AdminUI\AdminUIXero\Facades;

use Illuminate\Support\Facades\Facade;

/**
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
