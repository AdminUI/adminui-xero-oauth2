<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Stored in subunits (pence) to match `credit_limit`
            $table->bigInteger('xero_outstanding')->nullable()->after('xero_contact_id');
            $table->bigInteger('xero_overdue')->nullable()->after('xero_outstanding');
            $table->timestamp('xero_balances_synced_at')->nullable()->after('xero_overdue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['xero_outstanding', 'xero_overdue', 'xero_balances_synced_at']);
        });
    }
};
