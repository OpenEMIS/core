<?php

use Migrations\AbstractMigration;
use Cake\Utility\Text;

class POCOR7414 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_7414_report_student_assessment_summary` LIKE `report_student_assessment_summary`');
        $this->execute('INSERT INTO `z_7414_report_student_assessment_summary` SELECT * FROM `report_student_assessment_summary`');
        $this->execute('CREATE TABLE `z_7414_report_student_attendance_summary` LIKE `report_student_attendance_summary`');
        $this->execute('INSERT INTO `z_7414_report_student_attendance_summary` SELECT * FROM `report_student_attendance_summary`');

        $this->execute('RENAME TABLE `report_student_assessment_summary` TO `summary_student_assessments`');

        $this->execute('RENAME TABLE `report_student_attendance_summary` TO `summary_student_attendances`');
        
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `report_student_assessment_summary`');
        $this->execute('RENAME TABLE `z_7414_report_student_assessment_summary` TO `report_student_assessment_summary`');
        $this->execute('DROP TABLE IF EXISTS `report_student_attendance_summary`');
        $this->execute('RENAME TABLE `z_7414_report_student_attendance_summary` TO `report_student_attendance_summary`');
    }

        
}
