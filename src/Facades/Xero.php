<?php

namespace AdminUI\AdminUIXero\Facades;


use Illuminate\Support\Facades\Facade;


/**
 * @method static \XeroAPI\XeroPHP\Api\AccountingApi api()
 * @method static \Webfox\Xero\OauthCredentialManager credentials()
 * @method static array getUser()
 * @method static bool isConnected()
 * @method static ?string getTenantId()
 * @method static \XeroAPI\XeroPHP\Models\Accounting\Organisation getOrganisation() - Retrieves the currently connected organisation
 * @method static \XeroAPI\XeroPHP\Models\Accounting\Invoices updateOrCreateInvoices(array $data)
 * @method static \Illuminate\Support\Collection getSettings()
 *
 * @see \AdminUI\AdminUIXero\Services\XeroService
 */
class Xero extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'xero';
    }
}
