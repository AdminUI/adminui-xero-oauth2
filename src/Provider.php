<?php

namespace AdminUI\AdminUIXero;

use AdminUI\AdminUI\Facades\Seeder;
use Illuminate\Support\Facades\View;
use AdminUI\AdminUIXero\Facades\Xero;
use Illuminate\Support\ServiceProvider;
use AdminUI\AdminUIXero\Models\XeroToken;
use Illuminate\Console\Scheduling\Schedule;
use AdminUI\AdminUIXero\Services\XeroService;
use AdminUI\AdminUIXero\Commands\CopyContacts;
use AdminUI\AdminUIXero\Commands\InstallAUIXero;
use AdminUI\AdminUIXero\Commands\PushOrderToXeroCommand;
use AdminUI\AdminUIXero\Services\XeroContactService;
use AdminUI\AdminUIXero\Services\XeroInvoiceService;
use AdminUI\AdminUIXero\Services\XeroPaymentService;
use Illuminate\Contracts\Support\DeferrableProvider;
use AdminUI\AdminUIXero\Providers\XeroServiceProvider;
use AdminUI\AdminUIXero\Providers\EventServiceProvider;
use AdminUI\AdminUIXero\Providers\ConfigServiceProvider;
use AdminUI\AdminUIXero\Database\Seeders\NavigationSeeder;
use AdminUI\AdminUIXero\Database\Seeders\ConfigurationSeeder;

class Provider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(ConfigServiceProvider::class);
        $this->app->register(\Webfox\Xero\XeroServiceProvider::class);
        $this->registerFacades();

        $this->loadRoutesFrom(__DIR__ . '/Routes/admin.php');
    }

    public function boot(): void
    {
        $baseDir = dirname(__DIR__);


        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        Seeder::add([NavigationSeeder::class, ConfigurationSeeder::class]);

        if (!$this->app->runningInConsole()) {
            $this->pushJavascript();
        } else {
            $this->commands([PushOrderToXeroCommand::class]);
        }

        $this->publishes([
            $baseDir . '/publish/js' => public_path('vendor/adminui-xero-oauth2')
        ], 'adminui-public');
    }

    private function pushJavascript(): void
    {
        $output = \Illuminate\Support\Facades\Vite::useHotFile(base_path('vendor/adminui/adminui-xero-oauth2/publish/js/hot'))
            ->withEntryPoints(['resources/index.js'])
            ->useBuildDirectory('vendor/adminui-xero-oauth2')
            ->toHtml();

        View::startPush('aui_packages', $output);
    }

    private function registerFacades()
    {
        $this->app->singleton('xero', function () {
            return new XeroService;
        });
        $this->app->singleton('xero-contact', function () {
            return new XeroContactService;
        });
        $this->app->singleton('xero-invoice', function () {
            return new XeroInvoiceService;
        });
        $this->app->singleton('xero-payment', function () {
            return new XeroPaymentService;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['xero', 'xero-contact', 'xero-invoice', 'xero-payment'];
    }
}
