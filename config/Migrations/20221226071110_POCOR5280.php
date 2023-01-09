<?php
use Migrations\AbstractMigration;

class POCOR5280 extends AbstractMigration
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
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5280_calendar_events` LIKE `calendar_events`');
        $this->execute('INSERT INTO `z_5280_calendar_events` SELECT * FROM `calendar_events`');
        // End
        $this->execute("ALTER TABLE `calendar_events` ADD `start_time` TIME NOT NULL AFTER `name`, ADD `end_time` TIME NOT NULL AFTER `start_time`, ADD `institution_shift_id` INT(11) NOT NULL COMMENT 'shift option id' AFTER `end_time`");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `calendar_events`');
        $this->execute('RENAME TABLE `z_5280_calendar_events` TO `calendar_events`');
    }
}
