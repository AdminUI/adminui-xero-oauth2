<?php

namespace AdminUI\AdminUIXero\Listeners;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

abstract class BaseXeroListener implements ShouldQueue
{
    use InteractsWithQueue, Dispatchable, SerializesModels, Queueable;

    /**
     * The number of times the queued listener may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("Order failed to push to Xero");
        /* Mail::to('k.turner@evomark.co.uk')
            ->send(new GenericEmail(
                config('app.name') . ': Order failed to push to Xero',
                json_encode($this->event, JSON_PRETTY_PRINT)
            )); */
    }
}
