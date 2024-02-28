<?php

namespace AdminUI\AdminUIXero\Providers;

use Illuminate\Support\ServiceProvider;
use AdminUI\AdminUI\Models\Configuration;
use AdminUI\AdminUIXero\Listeners\SendOrderToXero;

class ConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $enabled = Configuration::firstWhere('name', 'xero_enabled');
        $clientId = Configuration::firstWhere('name', 'xero_client_id');
        $clientSecret = Configuration::firstWhere('name', 'xero_client_secret');
        $webhookKey = Configuration::firstWhere('name', 'xero_webhook_key');
        $linkedAccount = Configuration::firstWhere('name', 'xero_linked_account');

        config([
            'xero.enabled' => !empty($enabled) ? $enabled->value : false,
            'xero.linked_account' => !empty($linkedAccount) ? $linkedAccount->value : null,
            'xero.oauth.client_id' => !empty($clientId) ? $clientId->value : null,
            'xero.oauth.client_secret' => !empty($clientSecret) ? $clientSecret->value : null,
            'xero.oauth.webhook_signing_key' => !empty($webhookKey) ? $webhookKey->value : null,
            'xero.oauth.redirect_on_success' => 'admin.setup.integrations.xero',
            'xero.oauth.scopes' => [
                'openid',
                'email',
                'profile',
                'offline_access',
                'accounting.settings',
                'accounting.contacts',
                'accounting.transactions',
                'accounting.reports.read'
            ]
        ]);
    }
}
