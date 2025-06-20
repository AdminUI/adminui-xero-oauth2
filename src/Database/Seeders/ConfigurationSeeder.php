<?php

namespace AdminUI\AdminUIXero\Database\Seeders;

use AdminUI\AdminUI\Enums\PaymentMethod;
use AdminUI\AdminUI\Facades\Config;
use Illuminate\Database\Seeder;
use AdminUI\AdminUI\Models\Navigation;
use AdminUI\AdminUI\Models\Configuration;

class ConfigurationSeeder extends Seeder
{
    public function run()
    {

        Configuration::where('name', 'xero_account_id')->delete();

        Config::create('xero_sync_orders', [
            'label' => 'Sync Orders',
            'value_cast' => 'boolean',
            'section' => 'xero',
            'type' => 'switch',
            'is_private' => true,
            'is_active' => true
        ], false);

        Config::create('xero_sync_contacts', [
            'label' => 'Sync Contacts',
            'value_cast' => 'boolean',
            'section' => 'xero',
            'type' => 'switch',
            'is_private' => true,
            'is_active' => true
        ], false);

        Config::create('xero_sync_payments', [
            'label' => 'Sync Payments',
            'value_cast' => 'boolean',
            'section' => 'xero',
            'type' => 'switch',
            'is_private' => true,
            'is_active' => true
        ], false);

        Config::create('xero_use_account_balance', [
            'label' => 'Use Account Balance',
            'value_cast' => 'boolean',
            'section' => 'xero',
            'type' => 'switch',
            'is_private' => true,
            'is_active' => true
        ], false);

        Config::create('xero_sync_payment_methods', [
            'label' => 'Included Payment Methods',
            'value_cast' => 'array',
            'section' => 'xero',
            'type' => 'select',
            'is_private' => true,
            'is_active' => true
        ], PaymentMethod::values());

        Config::create('xero_client_id', [
            'label' => 'Client ID',
            'section' => 'xero',
            'type' => 'text',
            'is_private' => true,
            'is_active' => true
        ]);

        Config::create('xero_client_secret', [
            'label' => 'Client Secret',
            'section' => 'xero',
            'type' => 'password',
            'is_private' => true,
            'is_active' => true
        ]);

        Config::create('xero_linked_account', [
            'label' => 'Linked Account',
            'section' => 'xero',
            'type' => 'text',
            'is_private' => true,
            'is_active' => true
        ]);

        Config::create('xero_webhook_key', [
            'label' => 'Webhook Key',
            'section' => 'xero',
            'type' => 'password',
            'is_private' => true,
            'is_active' => true
        ]);
    }
}
