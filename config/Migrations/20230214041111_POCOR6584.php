<?php
use Migrations\AbstractMigration;

class POCOR6584 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6584_alerts` LIKE `alerts`');
        $this->execute('INSERT INTO `z_6584_alerts` SELECT * FROM `alerts`');
        $this->execute('CREATE TABLE `z_6584_alert_rules` LIKE `alert_rules`');
        $this->execute('INSERT INTO `z_6584_alert_rules` SELECT * FROM `alert_rules`');
        $this->execute("UPDATE `alerts` SET `name` = 'Student Attendance' WHERE `alerts`.`name` = 'Attendance';");
        $this->execute("UPDATE `alert_rules` SET `feature` = 'StudentAttendance' WHERE `alert_rules`.`feature` = 'Attendance';");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `alerts`');  
        $this->execute('RENAME TABLE `z_6584_alerts` TO `alerts`');

        $this->execute('DROP TABLE IF EXISTS `alert_rules`');  
        $this->execute('RENAME TABLE `z_6584_alert_rules` TO `alert_rules`');
    }
}
