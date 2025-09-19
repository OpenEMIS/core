<?php

use Phinx\Migration\AbstractMigration;
use Cake\Utility\Inflector;

class POCOR9353 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_9353_student_attendance_types` LIKE `student_attendance_types`');
        $this->execute('INSERT INTO `z_9353_student_attendance_types` SELECT * FROM `student_attendance_types`');

        // Insert new option
       $this->execute("
            INSERT INTO `student_attendance_types` (`id`, `code`, `name`)
            SELECT 3, 'DAY_AND_SUBJECT', 'Day and Subject'
            WHERE NOT EXISTS (
                SELECT 1 FROM `student_attendance_types` 
                WHERE `id` = 3 OR `code` = 'DAY_AND_SUBJECT'
            )
        ");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `student_attendance_types`');
        $this->execute('RENAME TABLE `z_9353_student_attendance_types` TO `student_attendance_types`');
        
    }
}
