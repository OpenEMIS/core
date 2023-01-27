<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PORGI8 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Creating registration_otp table...
        Schema::create('registration_otp', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('otp');
            $table->boolean('is_expired');
            $table->datetime('modified')->nullable();
            $table->datetime('created');
        });

        //Creating backup 'zz_8_config_item' table...
        DB::statement('CREATE TABLE `zz_8_config_items` LIKE `config_items`');
        DB::statement('INSERT INTO `zz_8_config_items` SELECT * FROM `config_items`');

        DB::statement("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Allow OpenEMIS Registrations to Add New Students', 'NewStudent', 'Add New Student', 'New Student', '0', '', '0', '1', '1', 'Dropdown', 'completeness', NULL, NULL, '1', now())");


        //Creating backup 'zz_8_student_statuses' table...
        /*DB::statement('CREATE TABLE `zz_8_student_statuses` LIKE `student_statuses`');
        DB::statement('INSERT INTO `zz_8_student_statuses` SELECT * FROM `student_statuses`');

        DB::statement("INSERT INTO `student_statuses` (`id`, `code`, `name`) VALUES (NULL, 'PENDINGADMISSION', 'Pending Admission')");*/

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('registration_otp');

        DB::statement('DROP TABLE IF EXISTS `config_items`');
        DB::statement('RENAME TABLE `zz_8_config_items` TO `config_items`');


        /*DB::statement('DROP TABLE IF EXISTS `student_statuses`');
        DB::statement('RENAME TABLE `zz_8_student_statuses` TO `student_statuses`');*/
    }
}
