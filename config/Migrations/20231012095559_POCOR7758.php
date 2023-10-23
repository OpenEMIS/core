<?php
use Migrations\AbstractMigration;

class POCOR7758 extends AbstractMigration
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
        //Backup staff_position_titles table
        $this->execute('CREATE TABLE `zz_7758_staff_position_titles` LIKE `staff_position_titles`');
        $this->execute('INSERT INTO `zz_7758_staff_position_titles` SELECT * FROM `staff_position_titles`');
       
        $this->execute("ALTER TABLE `staff_position_titles` ADD `file_name` varchar(250) DEFAULT NULL COMMENT 'description' AFTER `security_role_id`;");
        $this->execute("ALTER TABLE `staff_position_titles` ADD `file_content` longblob  DEFAULT NULL COMMENT 'description_content' AFTER `file_name`;");

    }
    public function down()
    {
        // Recover staff_position_titles table
        $this->execute('DROP TABLE IF EXISTS `staff_position_titles`');
        $this->execute('RENAME TABLE `zz_7758_staff_position_titles` TO `staff_position_titles`');
    }
}
