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
    /**
     * The message from the most recent failed API call, if any
     */
    protected ?string $lastError = null;

    public function api()
    {
        return resolve(AccountingApi::class);
    }

    public function where(array $constraints, string $condition = "AND")
    {
        return collect($constraints)->map(function ($value, $key) {
            return $key . '=="' . $this->escape($value) . '"';
        })->implode(' ' . $condition . ' ');
    }

    /**
     * Escape a value for use inside a double-quoted Xero `where` filter string
     */
    public function escape(mixed $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value);
    }

    /**
     * Get the error message from the most recent failed API call.
     * API calls return null on failure, so use this to report why.
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
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
            return !empty($organisationsRepo) ? ($organisationsRepo->getOrganisations()[0] ?? null) : null;
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
            $this->lastError = null;
            try {
                $response = $api->$method($this->getTenantId(), ...$parameters);
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
                Log::channel('xero')->info($method, [
                    'params' => $parameters,
                    'caller' => $trace[1] ?? null,
                ]);
                return $response;
            } catch (ApiException $err) {
                $this->lastError = $this->extractErrorMessage($err);
                Log::error("[AdminUI Xero]: Error making API call", ['message' => $err->getMessage(), 'trace' => $err->getTraceAsString(), 'data' => $err->getResponseObject()]);
                if ($err->getCode() === 429) {
                    $headers = $err->getResponseHeaders();
                    Log::error("[AdminUI Xero] Response header", ['headers' => $headers]);
                }
                return null;
            }
        }

        throw new \BadMethodCallException("Method $method does not exist");
    }

    /**
     * Pull the most useful message out of a Xero API exception: validation errors where present,
     * then the response detail, then the raw exception message.
     */
    protected function extractErrorMessage(ApiException $err): string
    {
        $response = $err->getResponseObject();

        try {
            $validationErrors = collect(data_get($response, 'elements', []))
                ->flatMap(fn($element) => data_get($element, 'validation_errors', []) ?? [])
                ->map(fn($error) => data_get($error, 'message'))
                ->filter();
            if ($validationErrors->isNotEmpty()) {
                return $validationErrors->implode('; ');
            }
            if ($detail = data_get($response, 'detail') ?? data_get($response, 'message')) {
                return $err->getCode() . ': ' . $detail;
            }
        } catch (\Throwable) {
            // Fall through to the generic message
        }

        return $err->getMessage();
    }
}
