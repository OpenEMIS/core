<?php

use Phinx\Migration\AbstractMigration;
use Cake\Utility\Inflector;

class POCOR9353 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_9353_student_attendance_types` LIKE `student_attendance_types`');
        $this->execute('INSERT INTO `z_9353_student_attendance_types` SELECT * FROM `student_attendance_types`');

        // id autoincreament
        $this->execute("
            ALTER TABLE `student_attendance_types`
            MODIFY COLUMN `id` INT(11) NOT NULL AUTO_INCREMENT
        ");

        // Insert new option
        $this->execute("
            INSERT INTO `student_attendance_types` (`code`, `name`)
            SELECT 'DAY_AND_SUBJECT', 'Day and Subject'
            WHERE NOT EXISTS (
                SELECT 1 FROM `student_attendance_types` WHERE `code` = 'DAY_AND_SUBJECT'
            )
        ");

    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `student_attendance_types`');
        $this->execute('RENAME TABLE `z_9353_student_attendance_types` TO `student_attendance_types`');
        
    }
}
