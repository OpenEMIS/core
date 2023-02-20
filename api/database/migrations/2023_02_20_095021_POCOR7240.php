<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class POCOR7240 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Creating backup 'api_credentials' table...
        DB::statement('CREATE TABLE `zz_7240_api_credentials` LIKE `api_credentials`');
        DB::statement('INSERT INTO `zz_7240_api_credentials` SELECT * FROM `api_credentials`');

        Schema::table('api_credentials', function (Blueprint $table) {
            $table->text('api_key', 1000)->nullable()->after('public_key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS `api_credentials`');
        DB::statement('RENAME TABLE `zz_7240_api_credentials` TO `api_credentials`');
    }
}
