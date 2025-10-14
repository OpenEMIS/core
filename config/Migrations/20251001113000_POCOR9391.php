<?php

use Migrations\AbstractMigration;

class POCOR9391 extends AbstractMigration
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
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        $this->execute('DROP TABLE IF EXISTS `z_9391_alerts`');
        $this->execute('CREATE TABLE `z_9391_alerts` LIKE `alerts`');
        $this->execute('INSERT INTO `z_9391_alerts` SELECT * FROM `alerts`');
        $this->execute("UPDATE `alerts` SET `process_name`='AlertStudentAbsence' WHERE `alerts`.`name` = 'StudentAbsence';"); // For some errors
        $this->execute("UPDATE `alerts` SET `name`='StudentAttendance' WHERE `alerts`.`name` = 'StudentAbsence';"); // For some errors
        $this->execute("UPDATE `alerts` SET `process_name`='AlertStudentAbsence' WHERE `alerts`.`name` = 'StudentAttendance';");
        $this->execute("UPDATE `alerts` SET `frequency`='Once' WHERE `alerts`.`frequency` != 'Never' and `alerts`.`name` = 'StudentAttendance';"); // This alert runs only once
        $this->execute('DROP TABLE IF EXISTS `z_9391_alert_rules`');
        $this->execute('CREATE TABLE `z_9391_alert_rules` LIKE `alert_rules`');
        $this->execute('INSERT INTO `z_9391_alert_rules` SELECT * FROM `alert_rules`');
        $this->execute("UPDATE `alert_rules` SET `feature`='StudentAttendance' WHERE `alert_rules`.`feature` = 'StudentAbsence';"); // For some errors
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

    }

    public function down()
    {
        if ($this->hasTable('z_9391_alerts')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');

            $this->execute('DROP TABLE IF EXISTS `alerts`');
            $this->execute('RENAME TABLE `z_9391_alerts` TO `alerts`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if ($this->hasTable('z_9391_alert_rules')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `alert_rules`');
            $this->execute('RENAME TABLE `z_9391_alert_rules` TO `alert_rules`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
