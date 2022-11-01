<?php
use Migrations\AbstractMigration;

class POCOR5972 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5972_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_5972_security_functions` SELECT * FROM `security_functions`');

        //update
        $this->execute('UPDATE `security_functions` SET `_delete` = "ScheduleTimetableOverview.remove" WHERE `name` = "Timetable"');
        $this->execute('UPDATE `security_functions` SET `_delete` = "ScheduleIntervals.remove" WHERE `name` = "Intervals"');
        $this->execute('UPDATE `security_functions` SET `_delete` = "ScheduleTerms.remove" WHERE `name` = "Terms"');
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_5972_security_functions` TO `security_functions`');
    }
}
