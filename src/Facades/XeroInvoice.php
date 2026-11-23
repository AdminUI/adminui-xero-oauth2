<?php

namespace AdminUI\AdminUIXero\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \XeroAPI\XeroPHP\Models\Accounting\Invoice|bool syncOrder(\AdminUI\AdminUI\Models\Order $order, \XeroAPI\XeroPHP\Models\Accounting\Contact $contact)
 * @method static void voidOrder(\AdminUI\AdminUI\Models\Order $order)
 *
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
