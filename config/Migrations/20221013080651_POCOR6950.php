<?php
use Migrations\AbstractMigration;

class POCOR6950 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_6950_staff_position_titles` LIKE `staff_position_titles`');
        $this->execute('INSERT INTO `zz_6950_staff_position_titles` SELECT * FROM `staff_position_titles`');

        $this->execute('ALTER TABLE `staff_position_titles` ADD `staff_position_categories_id` INT(11) NOT NULL AFTER `type`');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `staff_position_titles`');
        $this->execute('RENAME TABLE `zz_6950_staff_position_titles` TO `staff_position_titles`');
    }
}
