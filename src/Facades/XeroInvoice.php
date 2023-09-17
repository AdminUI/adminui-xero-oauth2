<?php

namespace AdminUI\AdminUIXero\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AdminUI\AdminUIXero\Services\XeroInvoiceService
 */
class XeroInvoice extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'xero-invoice';
    }
}
