<?php

namespace AdminUI\AdminUIXero\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUI\Facades\Flash;
use Illuminate\Support\Facades\DB;
use AdminUI\AdminUI\Models\Account;
use AdminUI\AdminUIXero\Facades\Xero;
use AdminUI\AdminUI\Models\Navigation;
use Webfox\Xero\OauthCredentialManager;
use AdminUI\AdminUI\Models\Configuration;
use AdminUI\AdminUIXero\Models\XeroToken;
use AdminUI\AdminUI\Traits\ApiResponseTrait;
use AdminUI\AdminUIXero\Services\XeroContactService;
use AdminUI\AdminUIXero\Services\XeroInvoiceService;
use AdminUI\AdminUIXero\Services\XeroPaymentService;
use AdminUI\AdminUI\Facades\Navigation as FacadesNavigation;
use AdminUI\AdminUI\Controllers\AdminUI\Inertia\InertiaCoreController;
use AdminUI\AdminUI\Models\OrderStatus;
use AdminUI\AdminUIXero\Helpers\FailedJobs;

class XeroSetupIntegrationController extends InertiaCoreController
{
    public function __construct()
    {
        FacadesNavigation::setSubmenu('setup.integrations');
    }

    public function index()
    {
        // dd(Xero::getContacts(null, 'Name="24 Locks"'));
        $this->seo([
            'title' => 'Xero Integration Setup'
        ]);
        return Inertia::render('xero::XeroSetup', [
            'xeroCallback' => fn () => route('xero.auth.callback'),
            'xeroWebhookDeliveryURL' => fn () => route('admin.webhooks.integrations.xero'),
            'xeroSettings' => fn () => Xero::getSettings(),
            'xeroStatus' => function () {
                try {
                    if (Xero::isConnected()) {
                        $organisationName = Xero::getOrganisation()->getName();
                        $user             = Xero::getUser();
                        $username         = "{$user['given_name']} {$user['family_name']} ({$user['username']})";
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
                return [
                    'connected' => Xero::isConnected(),
                    'error' => $error ?? null,
                    'organisationName' => $organisationName ?? null,
                    'username'         => $username ?? null
                ];
            },
            'failedOrderSyncs' => function () {
                return FailedJobs::getFailedJobs();
            },
            'orderStatuses' => fn () => OrderStatus::active()->orderBy('sort_order')->get(),
            'tabs' => function () {
                $integrationsNav = Navigation::firstWhere('ref', 'setup.integrations');
                return Navigation::active()->where('parent_id', $integrationsNav->id)->get();
            }
        ]);
    }
}
