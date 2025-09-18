<?php

use AdminUI\AdminUI\Models\Option;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (file_exists(storage_path('app/xero.json'))) {
            $credentials = json_decode(file_get_contents(storage_path('app/xero.json')), associative: true);
            $xeroCredentials = Option::firstOrCreate([
                'optionable_type' => null,
                'optionable_id' => null,
                'name' => 'xero_credentials',
            ], [
                'cast' => 'array'
            ]);
            $xeroCredentials->value = $credentials;
            $xeroCredentials->save();

            unlink(storage_path('app/xero.json'));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
