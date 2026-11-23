<?php

use AdminUI\AdminUI\Facades\Config;
use AdminUI\AdminUI\Models\Configuration;
use Illuminate\Database\Migrations\Migration;

/**
 * The settings form saves through a route that requires each configuration row to exist,
 * so create the new rows here rather than relying on the seeder having been run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Config::create('xero_sales_account_code', [
            'label' => 'Sales Account Code',
            'section' => 'xero',
            'type' => 'text',
            'is_private' => true,
            'is_active' => true
        ], '200');

        Config::create('xero_standard_tax_type', [
            'label' => 'Standard Rate Tax Type',
            'section' => 'xero',
            'type' => 'text',
            'is_private' => true,
            'is_active' => true
        ], 'OUTPUT2');
    }

    public function down(): void
    {
        Configuration::whereIn('name', ['xero_sales_account_code', 'xero_standard_tax_type'])->delete();
    }
};
