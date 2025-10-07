<?php

use Illuminate\Support\Facades\Route;
use AdminUI\AdminUIXero\Controllers\XeroOrdersController;
use AdminUI\AdminUIXero\Controllers\XeroWebhookController;
use AdminUI\AdminUIXero\Controllers\XeroPaymentsController;
use AdminUI\AdminUIXero\Controllers\XeroSetupIntegrationController;

Route::prefix(config('adminui.prefix'))->as('admin.setup.integrations.')->middleware(['adminui', 'auth:admin'])->group(function () {
    Route::get('setup/integrations/xero', [XeroSetupIntegrationController::class, 'index'])->name('xero');
    Route::post('setup/integrations/xero/orders/search', [XeroOrdersController::class, 'search'])->name('xero.orders.search');
    Route::post('setup/integrations/xero/orders/sync', [XeroOrdersController::class, 'sync'])->name('xero.orders.sync');
    Route::post('setup/integrations/xero/orders/retry', [XeroOrdersController::class, 'retry'])->name('xero.orders.retry');
    Route::post('setup/integrations/xero/orders/delete', [XeroOrdersController::class, 'delete'])->name('xero.orders.delete');

    Route::post('setup/integrations/xero/payments/sync', [XeroPaymentsController::class, 'sync'])->name('xero.payments.sync');
    Route::put('setup/integrations/xero/payments/resync', [XeroPaymentsController::class, 'resync'])->name('xero.payments.resync');
    Route::post('setup/integrations/xero/payments/retry', [XeroPaymentsController::class, 'retry'])->name('xero.payments.retry');
    Route::post('setup/integrations/xero/payments/delete', [XeroPaymentsController::class, 'delete'])->name('xero.payments.delete');

    Route::get('setup/integrations/xero/creditlimit/{account}', [XeroOrdersController::class, 'getCreditLimit']);
});

Route::prefix(config('adminui.prefix'))->group(function () {
    Route::post('webhooks/integrations/xero', XeroWebhookController::class)->name('admin.webhooks.integrations.xero');
});
