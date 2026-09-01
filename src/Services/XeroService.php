<?php

namespace AdminUI\AdminUIXero\Services;

use AdminUI\AdminUI\Models\Configuration;
use Carbon\CarbonInterval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Webfox\Xero\OauthCredentialManager;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\ApiException;

class XeroService
{
    public function api()
    {
        return resolve(AccountingApi::class);
    }

    public function where(array $constraints, string $condition = "AND")
    {
        return collect($constraints)->map(function ($value, $key) {
            return $key . '=="' . $value . '"';
        })->implode(' ' . $condition . ' ');
    }

    public function credentials(): OauthCredentialManager
    {
        return resolve(OauthCredentialManager::class);
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
        try {
            return $this->credentials()->exists();
        } catch (\Exception) {
            return false;
        }
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
        return Cache::remember('xero_organisations', CarbonInterval::day(), function () {
            $organisationsRepo = $this->getOrganisations();
            return !empty($organisationsRepo) ? $this->getOrganisations()->getOrganisations()[0] : null;
        });
    }

    /**
     * This utilises magic methods to proxy calls to methods that exist on the AccountingApi class and automatically inject the
     * tenant id.
     */
    public function __call($method, $parameters)
    {
        $api = $this->api();

        if (method_exists($api, $method)) {
            try {
                $response = $api->$method($this->getTenantId(), ...$parameters);
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
                Log::channel('xero')->info($method, [
                    'params' => $parameters,
                    'caller' => $trace[1] ?? null,
                ]);
                return $response;
            } catch (ApiException $err) {
                Log::error("[AdminUI Xero]: Error making API call", ['message' => $err->getMessage(), 'trace' => $err->getTraceAsString(), 'data' => $err->getResponseObject()]);
                if ($err->getCode() === 429) {
                    $headers = $err->getResponseHeaders();
                    Log::error("[AdminUI Xero] Response header", ['headers' => $headers]);
                }
                return;
                return;
            }
        }

        throw new \BadMethodCallException("Method $method does not exist");
    }
}
