<?php

namespace AdminUI\AdminUIXero;

use Illuminate\Foundation\Vite;
use AdminUI\AdminUI\Facades\Seeder;
use AdminUI\AdminUI\Models\Account;
use AdminUI\AdminUI\Facades\Filters;
use Illuminate\Support\Facades\View;
use AdminUI\AdminUI\Constants\Filter;
use Illuminate\Support\ServiceProvider;
use AdminUI\AdminUI\Facades\Application;
use AdminUI\AdminUI\Models\Option;
use AdminUI\AdminUIXero\Facades\XeroContact;
use AdminUI\AdminUIXero\Services\XeroService;
use function Illuminate\Filesystem\join_paths;
use AdminUI\AdminUIXero\Services\XeroContactService;
use AdminUI\AdminUIXero\Services\XeroInvoiceService;
use AdminUI\AdminUIXero\Services\XeroPaymentService;
use AdminUI\AdminUIXero\Commands\PushAllOrdersToXero;
use AdminUI\AdminUIXero\Providers\EventServiceProvider;
use AdminUI\AdminUIXero\Commands\PushOrderToXeroCommand;
use AdminUI\AdminUIXero\Providers\ConfigServiceProvider;
use AdminUI\AdminUIXero\Database\Seeders\NavigationSeeder;
use AdminUI\AdminUIXero\Database\Seeders\ConfigurationSeeder;
use Webfox\Xero\Xero as WebfoxXero;

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
        $this->app->register(EventServiceProvider::class);
        $this->app->register(\Webfox\Xero\XeroServiceProvider::class);
        $this->registerFacades();
        $this->loadRoutesFrom(__DIR__ . '/Routes/admin.php');
    }

    public function boot(): void
    {
        $baseDir = dirname(__DIR__);

        // Prime the database to save the Xero OAuth credentials
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

        $this->registerDatabaseResources();

        if (!$this->app->runningInConsole()) {
            $this->pushJavascript();
        } else {
            $this->commands([PushOrderToXeroCommand::class, PushAllOrdersToXero::class]);
        }

        $this->publishes([
            $baseDir . '/publish/js' => public_path('vendor/adminui-xero-oauth2')
        ], 'adminui-public');

        Application::registerDevTask(key: 'xero', name: 'AdminUI Xero', args: [
            'key' => 'xero',
            'command' => ['./node_modules/.bin/vite'],
            'env' => [
                'APP_URL' => config('app.url'),
            ],
            'workingDirectory' => join_paths(__DIR__, '../'),
            'mode' => 'start',
        ]);

        Application::registerProdTask(key: 'xero', name: 'AdminUI Xero', args: [
            'key' => 'xero',
            'command' => ['./node_modules/.bin/vite', 'build'],
            'env' => [
                'APP_URL' => config('app.url'),
            ],
            'workingDirectory' => join_paths(__DIR__, '../'),
            'mode' => 'run',
        ]);

        // If enabled, filter the AdminUI Ledger content to use Xero instead
        if (auiSetting('xero_use_account_balance', false)) {
            Filters::add(Filter::ACCOUNT_SHOW_LEDGER, fn() => false);
            Filters::add(Filter::ACCOUNT_CREDIT_INFORMATION, function ($null, Account $account) {
                return XeroContact::getCreditLimit($account);
            });
            Filters::add(Filter::ACCOUNT_USE_LEDGER, function () {
                return true;
            });
        }
    }

    /**
     * Add the package JavaScript to the stack
     */
    private function pushJavascript(): void
    {
        if ($this->app->runningInConsole()) return;

        $viteInstance = new Vite;
        $viteInstance->useHotFile(base_path('vendor/adminui/adminui-xero-oauth2/publish/js/hot'));
        $output = $viteInstance(['resources/index.js'], 'vendor/adminui-xero-oauth2');

        View::startPush('aui_packages', $output);
    }

    private function registerDatabaseResources()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        Seeder::add([NavigationSeeder::class, ConfigurationSeeder::class]);
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
