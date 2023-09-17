<?php

namespace AdminUI\AdminUIXero\Facades;

use Illuminate\Support\Facades\Facade;

/**
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
