<?php

namespace AdminUI\AdminUIXero\Services;

use XeroAPI\XeroPHP\Api\AccountingApi;
use Webfox\Xero\OauthCredentialManager;
use AdminUI\AdminUI\Models\Configuration;
use Illuminate\Support\Collection;

class XeroService
{
    protected $apiInstance;
    protected $xeroCredentials;

    public function __construct()
    {
        $this->apiInstance = resolve(AccountingApi::class);
        $this->xeroCredentials = resolve(OauthCredentialManager::class);
    }

    public function api()
    {
        return $this->apiInstance;
    }

    public function credentials(): OauthCredentialManager
    {
        return $this->xeroCredentials;
    }

    public function getTenantId(): ?string
    {
        return $this->credentials()->getTenantId();
    }

    public function getSettings(): Collection
    {
        return Configuration::where('section', 'xero')->get();
    }

    public function isConnected(): bool
    {
        return $this->credentials()->exists();
    }

    public function getUser(): array
    {
        return $this->credentials()->getUser();
    }

    /**
     * Retrieves the currently connected organisation
     */
    public function getOrganisation()
    {
        return $this->getOrganisations()->getOrganisations()[0];
    }

    /**
     * This utilises magic methods to proxy calls to methods that exist on the AccountingApi class and automatically inject the
     * tenant id.
     */
    public function __call($method, $parameters)
    {
        $api = $this->api();

        if (method_exists($api, $method)) {
            return $api->$method($this->getTenantId(), ...$parameters);
        }

        throw new \BadMethodCallException("Method $method does not exist");
    }
}
