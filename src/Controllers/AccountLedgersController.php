<?php

namespace AdminUI\AdminUIXero\Controllers;

use AdminUI\AdminUI\Models\Account;
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
            'ledgers' => fn() => DatatableManager::for(Account::class)
                ->setPermissions(PermissionsDTO::create(create: false, read: true, update: true, destroy: false))
                ->setAuthGuard('admin')
                ->setRoutes(RoutesDTO::create(
                    index: 'admin.ledgers.index',
                    show: 'admin.account.ledger',
                    update: 'admin.account.update'
                ))
                ->setHeaders([
                    new HeaderDTO(value: 'id', title: "ID"),
                    new HeaderDTO(value: 'name', title: 'Name'),
                    new HeaderDTO(value: 'credit_limit', title: 'Credit Limit', editable: true, editComponent: 'currency'),
                    new HeaderDTO(value: 'payment_terms', title: 'Payment Terms', editable: true, editComponent: 'number'),
                    // Balances are synced from Xero into local columns (see SyncXeroAccountBalances)
                    new HeaderDTO(value: 'outstanding', title: 'Outstanding', sortColumn: 'xero_outstanding'),
                    new HeaderDTO(value: 'overdue', title: 'Overdue', sortColumn: 'xero_overdue'),
                    new HeaderDTO(value: 'available_credit', title: 'Available Credit'),
                    new HeaderDTO(value: 'balance_status', title: 'Status', sortColumn: 'xero_outstanding', nowrap: true),
                    new HeaderDTO(value: 'on_hold', title: 'On Hold', editable: true, editComponent: 'switch'),
                    new HeaderDTO(value: 'is_active', title: 'Active', editable: true, editComponent: 'switch'),
                    new HeaderDTO(value: 'actions')
                ])
                // Computed in SQL so the column can be sorted. Null until the account has synced with Xero.
                ->setQuery(fn($query) => $query->select('accounts.*')->selectRaw(
                    'CASE WHEN xero_outstanding IS NULL THEN NULL ELSE credit_limit - xero_outstanding END AS available_credit'
                ))
                ->setCollection(AccountWithLedgerResource::class)
                ->where('credit_limit', '>', 0)
                ->get()
        ]);
    }
}
