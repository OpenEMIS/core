<?php
use Migrations\AbstractMigration;

class POCOR6658 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6658_student_attendance_marked_records` LIKE `student_attendance_marked_records`');
        $this->execute('INSERT INTO `z_6658_student_attendance_marked_records` SELECT * FROM `student_attendance_marked_records`');
        // End

        $this->execute('ALTER TABLE `student_attendance_marked_records` DROP PRIMARY KEY'); 

        $this->execute('ALTER TABLE `student_attendance_marked_records` ADD PRIMARY KEY( `institution_id`, `academic_period_id`, `institution_class_id`, `education_grade_id`, `date`, `period`, `subject_id`)'); 
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `student_attendance_marked_records`');
        $this->execute('RENAME TABLE `z_6658_student_attendance_marked_records` TO `student_attendance_marked_records`');
    }
}
