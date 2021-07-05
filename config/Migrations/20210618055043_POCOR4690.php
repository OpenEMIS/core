<?php
use Migrations\AbstractMigration;

class POCOR4690 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_4690_user_healths` LIKE `user_healths`');
        $this->execute('INSERT INTO `z_4690_user_healths` SELECT * FROM `user_healths`'); 

        $this->execute('CREATE TABLE `z_4690_user_health_allergies` LIKE `user_health_allergies`');
        $this->execute('INSERT INTO `z_4690_user_health_allergies` SELECT * FROM `user_health_allergies`'); 

        $this->execute('CREATE TABLE `z_4690_user_health_consultations` LIKE `user_health_consultations`');
        $this->execute('INSERT INTO `z_4690_user_health_consultations` SELECT * FROM `user_health_consultations`'); 

        $this->execute('CREATE TABLE `z_4690_user_health_families` LIKE `user_health_families`');
        $this->execute('INSERT INTO `z_4690_user_health_families` SELECT * FROM `user_health_families`');

        $this->execute('CREATE TABLE `z_4690_user_health_histories` LIKE `user_health_histories`');
        $this->execute('INSERT INTO `z_4690_user_health_histories` SELECT * FROM `user_health_histories`');

        $this->execute('CREATE TABLE `z_4690_user_health_immunizations` LIKE `user_health_immunizations`');
        $this->execute('INSERT INTO `z_4690_user_health_immunizations` SELECT * FROM `user_health_immunizations`'); 

        $this->execute('CREATE TABLE `z_4690_user_health_medications` LIKE `user_health_medications`');
        $this->execute('INSERT INTO `z_4690_user_health_medications` SELECT * FROM `user_health_medications`');

        $this->execute('CREATE TABLE `z_4690_user_health_tests` LIKE `user_health_tests`');
        $this->execute('INSERT INTO `z_4690_user_health_tests` SELECT * FROM `user_health_tests`'); 




        //rename module name profile to personal
        $this->execute("ALTER TABLE `user_healths` ADD `file_name` VARCHAR(250) NULL DEFAULT NULL AFTER `security_user_id`, ADD `file_content` LONGBLOB NULL DEFAULT NULL AFTER `file_name`");

        $this->execute("ALTER TABLE `user_health_allergies` ADD `file_name` VARCHAR(250) NULL DEFAULT NULL AFTER `security_user_id`, ADD `file_content` LONGBLOB NULL DEFAULT NULL AFTER `file_name`");

        $this->execute("ALTER TABLE `user_health_consultations` ADD `file_name` VARCHAR(250) NULL DEFAULT NULL AFTER `security_user_id`, ADD `file_content` LONGBLOB NULL DEFAULT NULL AFTER `file_name`");

        $this->execute("ALTER TABLE `user_health_families` ADD `file_name` VARCHAR(250) NULL DEFAULT NULL AFTER `security_user_id`, ADD `file_content` LONGBLOB NULL DEFAULT NULL AFTER `file_name`");

        $this->execute("ALTER TABLE `user_health_histories` ADD `file_name` VARCHAR(250) NULL DEFAULT NULL AFTER `security_user_id`, ADD `file_content` LONGBLOB NULL DEFAULT NULL AFTER `file_name`");

        $this->execute("ALTER TABLE `user_health_immunizations` ADD `file_name` VARCHAR(250) NULL DEFAULT NULL AFTER `security_user_id`, ADD `file_content` LONGBLOB NULL DEFAULT NULL AFTER `file_name`");

        $this->execute("ALTER TABLE `user_health_medications` ADD `file_name` VARCHAR(250) NULL DEFAULT NULL AFTER `security_user_id`, ADD `file_content` LONGBLOB NULL DEFAULT NULL AFTER `file_name`");

        $this->execute("ALTER TABLE `user_health_tests` ADD `file_name` VARCHAR(250) NULL DEFAULT NULL AFTER `security_user_id`, ADD `file_content` LONGBLOB NULL DEFAULT NULL AFTER `file_name`");        

    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `user_healths`');
        $this->execute('RENAME TABLE `z_4690_user_healths` TO `user_healths`');

        $this->execute('DROP TABLE IF EXISTS `user_health_allergies`');
        $this->execute('RENAME TABLE `z_4690_user_health_allergies` TO `user_health_allergies`');

        $this->execute('DROP TABLE IF EXISTS `user_health_consultations`');
        $this->execute('RENAME TABLE `z_4690_user_health_consultations` TO `user_health_consultations`');

        $this->execute('DROP TABLE IF EXISTS `user_health_families`');
        $this->execute('RENAME TABLE `z_4690_user_health_families` TO `user_health_families`');

        $this->execute('DROP TABLE IF EXISTS `user_health_histories`');
        $this->execute('RENAME TABLE `z_4690_user_health_histories` TO `user_health_histories`');

        $this->execute('DROP TABLE IF EXISTS `user_health_immunizations`');
        $this->execute('RENAME TABLE `z_4690_user_health_immunizations` TO `user_health_immunizations`');

        $this->execute('DROP TABLE IF EXISTS `user_health_medications`');
        $this->execute('RENAME TABLE `z_4690_user_health_medications` TO `user_health_medications`');

        $this->execute('DROP TABLE IF EXISTS `user_health_tests`');
        $this->execute('RENAME TABLE `z_4690_user_health_tests` TO `user_health_tests`');
    }
}
