<?php


namespace AdminUI\AdminUIXero\JobMiddleware;

use Closure;
use AdminUI\AdminUI\Models\Option;
use Illuminate\Support\Facades\Log;
use Webfox\Xero\Xero as WebfoxXero;


class RefreshXeroToken
{
    /**
     * Process the queued job.
     *
     * @param  \Closure(object): void  $next
     */
    public function handle(object $job, Closure $next): void
    {
        Log::debug("Running job middleware to refresh Xero token");
        $xeroCredentials = Option::firstOrCreate([
            'optionable_type' => null,
            'optionable_id' => null,
            'name' => 'xero_credentials',
        ], [
            'cast' => 'array'
        ]);
        // Tell Xero to save credentials using the model instance provided
        WebfoxXero::useModelStorage($xeroCredentials);
        // Tell Xero to use the `value` database column
        WebfoxXero::useAttributeOnModelStore('value');

        $next($job);
    }
}
