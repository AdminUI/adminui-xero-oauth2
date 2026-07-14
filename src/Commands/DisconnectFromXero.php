<?php

namespace AdminUI\AdminUIXero\Commands;

use AdminUI\AdminUI\Models\Configuration;
use AdminUI\AdminUI\Models\Option;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DisconnectFromXero extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adminui:xero-disconnect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disconnect from Xero account';

    public function handle()
    {
        $linked = Configuration::firstWhere('name', 'xero_linked_account');
        $linked->value = null;
        $linked->save();

        $enabled = Configuration::firstWhere('name', 'xero_enabled');
        $enabled->value = false;
        $enabled->save();

        $creds = Option::firstWhere('name', 'xero_credentials');
        $creds->value = null;
        $creds->save();

        Artisan::call('optimize:clear');
    }
}
