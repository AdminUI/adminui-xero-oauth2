<?php

namespace AdminUI\AdminUIXero\Listeners;

use AdminUI\AdminUIXero\JobMiddleware\RefreshXeroToken;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class BaseXeroListener implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, Queueable;

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
     * Queue this listener for an event without firing the event itself (which would also trigger
     * every other listener, e.g. customer emails).
     *
     * Goes through Laravel's event dispatcher so the queued job is identical to one created by a
     * real event: tries, backoff, retryUntil and middleware are applied, and the job appears in the
     * failed syncs list if it fails.
     */
    public static function queue(object $event): void
    {
        app('events')->makeListener(static::class)(get_class($event), [$event]);
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
        Log::error("[AdminUI Xero] " . class_basename(static::class) . " failed: " . $exception->getMessage());
    }
}
