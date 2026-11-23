<?php

namespace AdminUI\AdminUIXero\Jobs;

use AdminUI\AdminUIXero\Facades\Xero;
use AdminUI\AdminUIXero\Facades\XeroContact;
use AdminUI\AdminUIXero\JobMiddleware\RefreshXeroToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Refresh the locally stored Xero accounts receivable balances for accounts.
 *
 * With no IDs given, every account linked to a Xero contact is synced.
 */
class SyncXeroAccountBalances implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;

    public $backoff = 60;

    /**
     * @param string[] $contactIds Xero contact IDs to sync
     * @param string[] $invoiceIds Xero invoice IDs whose contacts should be synced
     */
    public function __construct(
        public array $contactIds = [],
        public array $invoiceIds = [],
    ) {}

    public function middleware(): array
    {
        $middleware = [new RefreshXeroToken];

        // Only one full sync should ever run at a time
        if ($this->isFullSync()) {
            $middleware[] = (new WithoutOverlapping('xero-balances-full-sync'))->dontRelease()->expireAfter(1800);
        }

        return $middleware;
    }

    public function handle(): void
    {
        if (!Xero::isConnected()) {
            return;
        }

        if ($this->isFullSync()) {
            XeroContact::syncBalances();
            return;
        }

        $contactIds = [...$this->contactIds, ...$this->resolveInvoiceContactIds()];
        if (!empty($contactIds)) {
            XeroContact::syncBalances($contactIds);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('[AdminUI Xero] Failed to sync account balances', ['message' => $exception->getMessage()]);
    }

    private function isFullSync(): bool
    {
        return empty($this->contactIds) && empty($this->invoiceIds);
    }

    /**
     * Look up the contact attached to each invoice
     */
    private function resolveInvoiceContactIds(): array
    {
        if (empty($this->invoiceIds)) {
            return [];
        }

        $invoices = Xero::getInvoices(ids: array_values(array_unique($this->invoiceIds)), summary_only: true);
        if (empty($invoices)) {
            return [];
        }

        return collect($invoices->getInvoices())
            ->map(fn($invoice) => $invoice->getContact()?->getContactId())
            ->filter()
            ->values()
            ->all();
    }
}
