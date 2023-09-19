<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('xero_contact_id')->nullable()->after('import_id');
        });

        sleep(1);
        DB::statement('UPDATE accounts SET xero_contact_id = import_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('UPDATE accounts SET import_id = xero_contact_id');
        sleep(1);

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['xero_contact_id']);
        });
    }
};
