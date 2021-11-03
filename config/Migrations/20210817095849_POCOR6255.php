<?php
use Migrations\AbstractMigration;

class POCOR6255 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6255_user_body_masses` LIKE `user_body_masses`');
        $this->execute('INSERT INTO `zz_6255_user_body_masses` SELECT * FROM `user_body_masses`');

        $this->execute('CREATE TABLE `zz_6255_user_insurances` LIKE `user_insurances`');
        $this->execute('INSERT INTO `zz_6255_user_insurances` SELECT * FROM `user_insurances`');
        //add columns in user_body_masses table
        $this->execute("ALTER TABLE `user_body_masses` ADD `file_name` VARCHAR(250) NULL DEFAULT NULL AFTER `comment`, ADD `file_content` LONGBLOB NULL DEFAULT NULL AFTER `file_name`");
        //add columns in user_insurances table
        $this->execute("ALTER TABLE `user_insurances` ADD `file_name` VARCHAR(250) NULL DEFAULT NULL AFTER `comment`, ADD `file_content` LONGBLOB NULL DEFAULT NULL AFTER `file_name`");

    }

    public function down() {
        $this->execute('DROP TABLE IF EXISTS `user_body_masses`');
        $this->execute('RENAME TABLE `zz_6255_user_body_masses` TO `user_body_masses`');

        $this->execute('DROP TABLE IF EXISTS `user_insurances`');
        $this->execute('RENAME TABLE `zz_6255_user_insurances` TO `user_insurances`');
    }
}
