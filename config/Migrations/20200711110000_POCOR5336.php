<?php

use Migrations\AbstractMigration;

class POCOR5336 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_5336_api_securities` LIKE `api_securities`');
		$this->execute('INSERT INTO `z_5336_api_securities` SELECT * FROM `api_securities`');	

        $this->execute("INSERT INTO `api_securities` (`name`, `model`, `index`, `view`, `add`, `edit`, `delete`, `execute`) values('Subjects','Institution.InstitutionClassSubjects','1','1','1','1','0','0')");	
		$this->execute("INSERT INTO `api_securities` (`name`, `model`, `index`, `view`, `add`, `edit`, `delete`, `execute`) values('StudentAttendanceTypes','Attendance.StudentAttendanceTypes','1','1','1','1','0','0')");

        $this->execute('ALTER TABLE `student_attendance_marked_records` ADD COLUMN `subject_id` int(11) AFTER `period`');
        $this->execute("ALTER TABLE `student_attendance_marked_records` DROP PRIMARY KEY, ADD primary key (`institution_id`,`academic_period_id`,`institution_class_id`,`date`,`period`,`subject_id`)");
        $this->execute('ALTER TABLE `institution_student_absence_details` ADD COLUMN `subject_id` int(11) AFTER `student_absence_reason_id`');
        $this->execute("ALTER TABLE `institution_student_absence_details` DROP PRIMARY KEY, ADD primary key (`student_id`,`institution_id`,`academic_period_id`,`institution_class_id`,`date`,`period`,`subject_id`)");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `api_securities`');
        $this->execute('RENAME TABLE `z_5336_api_securities` TO `api_securities`');
        $this->execute('ALTER TABLE `student_attendance_marked_records` DROP COLUMN `subject_id`');
        $this->execute('ALTER TABLE `institution_student_absence_details` DROP COLUMN `subject_id`');
    }
}
