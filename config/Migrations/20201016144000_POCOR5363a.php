<?php

use Phinx\Migration\AbstractMigration;

class POCOR5363a extends AbstractMigration
{
    public function up()
    {
        // Create tables

        $this->execute('CREATE TABLE `z_5363a_student_attendance_mark_types` LIKE `student_attendance_mark_types`');
        $this->execute('INSERT INTO `z_5363a_student_attendance_mark_types` SELECT * FROM `student_attendance_mark_types`');
        $this->execute('CREATE TABLE `z_5363a_student_attendance_per_day_periods` LIKE `student_attendance_per_day_periods`');
        $this->execute('INSERT INTO `z_5363a_student_attendance_per_day_periods` SELECT * FROM `student_attendance_per_day_periods`');
        $this->execute('CREATE TABLE `z_5363a_student_mark_type_statuses` LIKE `student_mark_type_statuses`');
        $this->execute('INSERT INTO `z_5363a_student_mark_type_statuses` SELECT * FROM `student_mark_type_statuses`');
        $this->execute('CREATE TABLE `z_5363a_student_mark_type_status_grades` LIKE `student_mark_type_status_grades`');
        $this->execute('INSERT INTO `z_5363a_student_mark_type_status_grades` SELECT * FROM `student_mark_type_status_grades`');

        $this->execute("UPDATE `student_attendance_mark_types`
        INNER JOIN `education_grades`
        ON student_attendance_mark_types.education_grade_id = education_grades.id
        SET student_attendance_mark_types.name = education_grades.name
                ");
        
        $this->execute("UPDATE `student_attendance_mark_types`
        INNER JOIN `education_grades` 
        ON student_attendance_mark_types.education_grade_id = education_grades.id
        SET student_attendance_mark_types.code = education_grades.code");

        $this->execute("INSERT INTO `student_mark_type_statuses` (`academic_period_id`, `student_attendance_mark_type_id`, `date_enabled`, `date_disabled`)
        SELECT student_attendance_mark_types.academic_period_id,student_attendance_mark_types.id,academic_periods.start_date,academic_periods.end_date 
        FROM `student_attendance_mark_types` 
        INNER JOIN `academic_periods` ON student_attendance_mark_types.academic_period_id = academic_periods.id"); 

        $this->execute("INSERT INTO `student_mark_type_status_grades` (`id`,`education_grade_id`, `student_mark_type_status_id`)
        SELECT uuid(),student_attendance_mark_types.education_grade_id,student_mark_type_statuses.id 
        FROM `student_mark_type_statuses`
        INNER JOIN `student_attendance_mark_types`
        ON student_attendance_mark_types.id = student_mark_type_statuses.student_attendance_mark_type_id");

        $this->execute("UPDATE `student_attendance_per_day_periods`
        INNER JOIN `student_attendance_mark_types`
        ON student_attendance_per_day_periods.education_grade_id = student_attendance_mark_types.education_grade_id
        AND student_attendance_per_day_periods.academic_period_id = student_attendance_mark_types.academic_period_id
        SET student_attendance_per_day_periods.student_attendance_mark_type_id = student_attendance_mark_types.id");
    }

    public function down()
    {
        // For tables
        $this->execute('DROP TABLE IF EXISTS `student_attendance_mark_types`');
        $this->execute('RENAME TABLE `z_5363a_student_attendance_mark_types` TO `student_attendance_mark_types`'); 
        $this->execute('DROP TABLE IF EXISTS `student_attendance_per_day_periods`');
        $this->execute('RENAME TABLE `z_5363a_student_attendance_per_day_periods` TO `student_attendance_per_day_periods`'); 
        $this->execute('DROP TABLE IF EXISTS `student_mark_type_statuses`');
        $this->execute('RENAME TABLE `z_5363a_student_mark_type_statuses` TO `student_mark_type_statuses`'); 
        $this->execute('DROP TABLE IF EXISTS `student_mark_type_status_grades`');
        $this->execute('RENAME TABLE `z_5363a_student_mark_type_status_grades` TO `student_mark_type_status_grades`');       
    }
}
