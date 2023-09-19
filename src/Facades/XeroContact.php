<?php

namespace AdminUI\AdminUIXero\Facades;

use Illuminate\Support\Facades\Facade;

/**
 *  @method static \XeroAPI\XeroPHP\Models\Accounting\Contact getContact(\AdminUI\AdminUI\Models\Account $account)
 * 
 * @see \AdminUI\AdminUIXero\Services\XeroContactService
 */
class XeroContact extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'xero-contact';
    }
}
