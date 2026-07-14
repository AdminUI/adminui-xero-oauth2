<?php

namespace AdminUI\AdminUIXero\Database\Seeders;

use AdminUI\AdminUI\Models\Navigation;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    public function run()
    {
        $accounts = Navigation::firstWhere('ref', 'accounts');

        Navigation::updateOrCreate(
            ['ref' => 'accounts.ledgers'],
            [
                'title' => 'Account Ledgers',
                'route' => 'admin.ledgers.index',
                'icon' => null,
                'parent_id' => $accounts->id,
                'permissions' => 'admin read',
                'package' => 'AdminUI',
                'is_active' => true,
                'sort_order' => 21,
            ]
        );


        $setup = Navigation::firstWhere('ref', 'setup');

        Navigation::where('ref', 'setup.xero')->delete();

        $integrations = Navigation::updateOrCreate(
            ['ref' => 'setup.integrations'],
            [
                'title' => 'Integrations',
                'route' => 'admin.setup.integrations.index',
                'icon' => null,
                'parent_id' => $setup->id,
                'permissions' => null,
                'package' => 'Ecommerce',
                'is_active' => true,
                'sort_order' => 40,
            ]
        );

        Navigation::updateOrCreate(
            ['ref' => 'setup.integrations.xero'],
            [
                'title' => 'Xero',
                'route' => 'admin.setup.integrations.xero',
                'icon' => null,
                'parent_id' => $integrations->id,
                'permissions' => null,
                'package' => 'Ecommerce',
                'is_active' => true,
                'sort_order' => 41,
            ]
        );
    }
}
