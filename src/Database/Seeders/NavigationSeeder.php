<?php

namespace AdminUI\AdminUIXero\Database\Seeders;

use AdminUI\AdminUI\Models\Navigation;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    public function run()
    {
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
