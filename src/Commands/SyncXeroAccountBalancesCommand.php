<?php

namespace AdminUI\AdminUIXero\Commands;

use AdminUI\AdminUIXero\Jobs\SyncXeroAccountBalances;
use Illuminate\Console\Command;

class SyncXeroAccountBalancesCommand extends Command
{
    protected $signature = 'adminui:xero-sync-balances {--queue : Dispatch to the queue instead of running immediately}';

    protected $description = 'Sync the outstanding and overdue balances of every Xero-linked account';

    public function handle(): int
    {
        if ($this->option('queue')) {
            SyncXeroAccountBalances::dispatch();
            $this->info('Balance sync dispatched to the queue');
            return self::SUCCESS;
        }

        SyncXeroAccountBalances::dispatchSync();
        $this->info('Account balances synced from Xero');

        return self::SUCCESS;
    }
}
