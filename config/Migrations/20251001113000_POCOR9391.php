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

        $this->execute('CREATE TABLE `z_9391_alerts` LIKE `alerts`');
        $this->execute('INSERT INTO `z_9391_alerts` SELECT * FROM `alerts`');
        $this->execute("UPDATE `alerts` SET `process_name`='AlertStudentAbsence' WHERE `alerts`.`name` = 'StudentAttendance';");
    }

    public function down()
    {
        if ($this->hasTable('z_9391_alerts')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');

            $this->execute('DROP TABLE IF EXISTS `alerts`');
            $this->execute('RENAME TABLE `z_9391_alerts` TO `alerts`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
