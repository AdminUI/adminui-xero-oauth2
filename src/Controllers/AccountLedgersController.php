<?php

namespace AdminUI\AdminUIXero\Controllers;

use AdminUI\AdminUI\Models\Account;
use AdminUI\AdminUIXero\Facades\Xero;
use AdminUI\AdminUIXero\Facades\XeroContact;
use AdminUI\AdminUIXero\Resources\AccountWithLedgerResource;
use EvoMark\EvoLaravelDatatable\DatatableManager;
use EvoMark\EvoLaravelDatatable\HeaderDTO;
use EvoMark\EvoLaravelDatatable\PermissionsDTO;
use EvoMark\EvoLaravelDatatable\RoutesDTO;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class AccountLedgersController extends Controller
{
    public function index()
    {
        return Inertia::render('xero::AccountLedgers', [
            'ledgers' => fn() => tap(DatatableManager::for(Account::class)
                ->setPermissions(PermissionsDTO::create(create: false, read: true, update: true, destroy: false))
                ->setAuthGuard('admin')
                ->setRoutes(RoutesDTO::create(index: 'admin.ledgers.index', show: 'admin.account.ledger'))
                ->setHeaders([
                    new HeaderDTO(value: 'id', title: "ID"),
                    new HeaderDTO(value: 'name', title: 'Name'),
                    new HeaderDTO(value: 'credit_limit', title: 'Credit Limit'),
                    new HeaderDTO(value: 'payment_terms', title: 'Payment Terms'),
                    new HeaderDTO(value: 'outstanding', title: 'Outstanding'),
                    new HeaderDTO(value: 'overdue', title: 'Overdue'),
                    new HeaderDTO(value: 'actions')
                ])
                ->setCollection(AccountWithLedgerResource::class)
                ->where('credit_limit', '>', 0)
                ->get(), function ($results) {
                $items = $results->items();
                $xeroIds = collect($items)->pluck('xero_contact_id')->toArray();
                $xero = Xero::getContacts(ids: [...$xeroIds, '362819c9-f285-4d09-ac95-26327863adac']);
                $xero = collect($xero->getContacts());
                $results->resource->getCollection()->transform(function ($account) use ($xero) {
                    $xeroContact = $xero->firstWhere('contact_id', $account->xero_contact_id);
                    if (empty($xeroContact)) {
                        return $account;
                    }

                    $account->outstanding = data_get($xeroContact, 'balances.accounts_receivable.outstanding');
                    $account->overdue = data_get($xeroContact, 'balances.accounts_receivable.overdue');

                    return $account;
                });
                return $results;
            })
        ]);
    }
}
