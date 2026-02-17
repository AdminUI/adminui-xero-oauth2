<?php

namespace AdminUI\AdminUIXero\Listeners;

use AdminUI\AdminUIXero\JobMiddleware\RefreshXeroToken;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

abstract class BaseXeroListener implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, Queueable, Dispatchable;

    /**
     * The number of times the queued listener may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    protected $debug = false;

    public function debug(): static
    {
        $this->debug = true;

        return $this;
    }

    /**

     * Get the middleware the job should pass through.

     *

     * @return array<int, object>

     */

    public function middleware(): array
    {
        return [new RefreshXeroToken];
    }

    /**
     * Handle a job failure.
     */
    public function failed(mixed $event, Throwable $exception): void
    {
        Log::error("Order failed to push to Xero");
    }
}
