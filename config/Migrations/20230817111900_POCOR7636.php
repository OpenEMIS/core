<?php
use Migrations\AbstractMigration;

class POCOR7636 extends AbstractMigration
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
        // Backup affected tables
        $this->execute('CREATE TABLE `zz_7636_summary_area_institution_grade_attendances` LIKE `summary_area_institution_grade_attendances`');
        $this->execute('INSERT INTO `zz_7636_summary_area_institution_grade_attendances` SELECT * FROM `summary_area_institution_grade_attendances`');

        $this->execute('CREATE TABLE `zz_7636_summary_assessment_item_results` LIKE `summary_assessment_item_results`');
        $this->execute('INSERT INTO `zz_7636_summary_assessment_item_results` SELECT * FROM `summary_assessment_item_results`');

        $this->execute('CREATE TABLE `zz_7636_summary_grade_gender_ages` LIKE `summary_grade_gender_ages`');
        $this->execute('INSERT INTO `zz_7636_summary_grade_gender_ages` SELECT * FROM `summary_grade_gender_ages`');

        $this->execute('CREATE TABLE `zz_7636_summary_grade_status_genders` LIKE `summary_grade_status_genders`');
        $this->execute('INSERT INTO `zz_7636_summary_grade_status_genders` SELECT * FROM `summary_grade_status_genders`');

        $this->execute('CREATE TABLE `zz_7636_summary_institutions` LIKE `summary_institutions`');
        $this->execute('INSERT INTO `zz_7636_summary_institutions` SELECT * FROM `summary_institutions`');

        $this->execute('CREATE TABLE `zz_7636_summary_institution_grades` LIKE `summary_institution_grades`');
        $this->execute('INSERT INTO `zz_7636_summary_institution_grades` SELECT * FROM `summary_institution_grades`');

        $this->execute('CREATE TABLE `zz_7636_summary_institution_grade_nationalities` LIKE `summary_institution_grade_nationalities`');
        $this->execute('INSERT INTO `zz_7636_summary_institution_grade_nationalities` SELECT * FROM `summary_institution_grade_nationalities`');

        $this->execute('CREATE TABLE `zz_7636_summary_institution_room_types` LIKE `summary_institution_room_types`');
        $this->execute('INSERT INTO `zz_7636_summary_institution_room_types` SELECT * FROM `summary_institution_room_types`');

        $this->execute('CREATE TABLE `zz_7636_summary_institution_student_absences` LIKE `summary_institution_student_absences`');
        $this->execute('INSERT INTO `zz_7636_summary_institution_student_absences` SELECT * FROM `summary_institution_student_absences`');

        $this->execute('CREATE TABLE `zz_7636_summary_isced_sectors` LIKE `summary_isced_sectors`');
        $this->execute('INSERT INTO `zz_7636_summary_isced_sectors` SELECT * FROM `summary_isced_sectors`');

        $this->execute('CREATE TABLE `zz_7636_summary_programme_sector_genders` LIKE `summary_programme_sector_genders`');
        $this->execute('INSERT INTO `zz_7636_summary_programme_sector_genders` SELECT * FROM `summary_programme_sector_genders`');

        $this->execute('CREATE TABLE `zz_7636_summary_programme_sector_specialization_genders` LIKE `summary_programme_sector_specialization_genders`');
        $this->execute('INSERT INTO `zz_7636_summary_programme_sector_specialization_genders` SELECT * FROM `summary_programme_sector_specialization_genders`');



        // Increase the size of the affected columns
        $this->execute('ALTER TABLE `summary_area_institution_grade_attendances` MODIFY COLUMN `area_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_area_institution_grade_attendances` MODIFY COLUMN `area_name` varchar(150)');

        $this->execute('ALTER TABLE `summary_assessment_item_results` MODIFY COLUMN `academic_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_assessment_item_results` MODIFY COLUMN `assessment_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_assessment_item_results` MODIFY COLUMN `assessment_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_assessment_item_results` MODIFY COLUMN `assessment_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_assessment_item_results` MODIFY COLUMN `academic_term` varchar(150)');
        $this->execute('ALTER TABLE `summary_assessment_item_results` MODIFY COLUMN `subject_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_assessment_item_results` MODIFY COLUMN `education_grade` varchar(150)');
        $this->execute('ALTER TABLE `summary_assessment_item_results` MODIFY COLUMN `institution_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_assessment_item_results` MODIFY COLUMN `institution_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_assessment_item_results` MODIFY COLUMN `institution_provider` varchar(150)');
        $this->execute('ALTER TABLE `summary_assessment_item_results` MODIFY COLUMN `area_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_assessment_item_results` MODIFY COLUMN `institution_class_name` varchar(150)');

        $this->execute('ALTER TABLE `summary_grade_gender_ages` MODIFY COLUMN `academic_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_gender_ages` MODIFY COLUMN `education_system_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_gender_ages` MODIFY COLUMN `education_level_isced_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_gender_ages` MODIFY COLUMN `education_level_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_gender_ages` MODIFY COLUMN `education_cycle_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_gender_ages` MODIFY COLUMN `education_programme_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_gender_ages` MODIFY COLUMN `education_programme_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_gender_ages` MODIFY COLUMN `education_grade_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_gender_ages` MODIFY COLUMN `education_grade_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_gender_ages` MODIFY COLUMN `student_gender_name` varchar(150)');

        $this->execute('ALTER TABLE `summary_grade_status_genders` MODIFY COLUMN `academic_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_status_genders` MODIFY COLUMN `education_system_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_status_genders` MODIFY COLUMN `education_level_isced_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_status_genders` MODIFY COLUMN `education_level_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_status_genders` MODIFY COLUMN `education_cycle_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_status_genders` MODIFY COLUMN `education_programme_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_status_genders` MODIFY COLUMN `education_programme_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_status_genders` MODIFY COLUMN `education_grade_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_status_genders` MODIFY COLUMN `education_grade_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_status_genders` MODIFY COLUMN `student_gender_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_grade_status_genders` MODIFY COLUMN `student_status_name` varchar(150)');

        $this->execute('ALTER TABLE `summary_institutions` MODIFY COLUMN `academic_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_institutions` MODIFY COLUMN `institution_code` varchar(150)');

        $this->execute('ALTER TABLE `summary_institution_grades` MODIFY COLUMN `academic_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_grades` MODIFY COLUMN `institution_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_grades` MODIFY COLUMN `grade_name` varchar(150)');

        $this->execute('ALTER TABLE `summary_institution_grade_nationalities` MODIFY COLUMN `academic_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_grade_nationalities` MODIFY COLUMN `institution_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_grade_nationalities` MODIFY COLUMN `grade_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_grade_nationalities` MODIFY COLUMN `nationality_name` varchar(150)');

        $this->execute('ALTER TABLE `summary_institution_nationalities` MODIFY COLUMN `academic_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_nationalities` MODIFY COLUMN `institution_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_nationalities` MODIFY COLUMN `nationality_name` varchar(150)');

        $this->execute('ALTER TABLE `summary_institution_room_types` MODIFY COLUMN `academic_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_room_types` MODIFY COLUMN `institution_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_room_types` MODIFY COLUMN `room_type` varchar(150)');

        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `institution_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `institution_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `area_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `area_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `area_administrative_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `area_administrative_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `openemis_no` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `default_identity_number` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `student_name` varchar(300)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `enrol_start_date` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `enrol_end_date` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `academic_period_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `academic_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `education_grade_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `education_grade_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `absent_date` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `absent_days` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `absence_subject_period` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `absence_type` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `student_absence_reasons` varchar(150)');
        $this->execute('ALTER TABLE `summary_institution_student_absences` MODIFY COLUMN `student_status` varchar(150)');

        $this->execute('ALTER TABLE `summary_isced_sectors` MODIFY COLUMN `academic_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_isced_sectors` MODIFY COLUMN `institution_sector_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_isced_sectors` MODIFY COLUMN `education_system_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_isced_sectors` MODIFY COLUMN `education_level_isced_name` varchar(150)');

        $this->execute('ALTER TABLE `summary_programme_sector_genders` MODIFY COLUMN `academic_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_genders` MODIFY COLUMN `institution_sector_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_genders` MODIFY COLUMN `education_system_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_genders` MODIFY COLUMN `education_level_isced_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_genders` MODIFY COLUMN `education_level_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_genders` MODIFY COLUMN `education_cycle_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_genders` MODIFY COLUMN `education_programme_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_genders` MODIFY COLUMN `education_programme_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_genders` MODIFY COLUMN `gender_name` varchar(150)');

        $this->execute('ALTER TABLE `summary_programme_sector_specialization_genders` MODIFY COLUMN `academic_period_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_specialization_genders` MODIFY COLUMN `institution_sector_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_specialization_genders` MODIFY COLUMN `education_system_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_specialization_genders` MODIFY COLUMN `education_level_isced_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_specialization_genders` MODIFY COLUMN `education_level_isced_level` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_specialization_genders` MODIFY COLUMN `education_level_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_specialization_genders` MODIFY COLUMN `education_cycle_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_specialization_genders` MODIFY COLUMN `education_programme_code` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_specialization_genders` MODIFY COLUMN `education_programme_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_specialization_genders` MODIFY COLUMN `staff_gender_name` varchar(150)');
        $this->execute('ALTER TABLE `summary_programme_sector_specialization_genders` MODIFY COLUMN `staff_training_category_name` varchar(150)');

    }
         
    // rollback
    public function down()
    {
        // Restore backup tables
        $this->execute('DROP TABLE IF EXISTS `summary_area_institution_grade_attendances`');
        $this->execute('RENAME TABLE `zz_7636_summary_area_institution_grade_attendances` TO `summary_area_institution_grade_attendances`');

        $this->execute('DROP TABLE IF EXISTS `summary_assessment_item_results`');
        $this->execute('RENAME TABLE `zz_7636_summary_assessment_item_results` TO `summary_assessment_item_results`');

        $this->execute('DROP TABLE IF EXISTS `summary_grade_gender_ages`');
        $this->execute('RENAME TABLE `zz_7636_summary_grade_gender_ages` TO `summary_grade_gender_ages`');

        $this->execute('DROP TABLE IF EXISTS `summary_grade_status_genders`');
        $this->execute('RENAME TABLE `zz_7636_summary_grade_status_genders` TO `summary_grade_status_genders`');

        $this->execute('DROP TABLE IF EXISTS `summary_institutions`');
        $this->execute('RENAME TABLE `zz_7636_summary_institutions` TO `summary_institutions`');

        $this->execute('DROP TABLE IF EXISTS `summary_institution_grades`');
        $this->execute('RENAME TABLE `zz_7636_summary_institution_grades` TO `summary_institution_grades`');
        
        $this->execute('DROP TABLE IF EXISTS `summary_institution_grade_nationalities`');
        $this->execute('RENAME TABLE `zz_7636_summary_institution_grade_nationalities` TO `summary_institution_grade_nationalities`');

        $this->execute('DROP TABLE IF EXISTS `summary_institution_room_types`');
        $this->execute('RENAME TABLE `zz_7636_summary_institution_room_types` TO `summary_institution_room_types`');

        $this->execute('DROP TABLE IF EXISTS `summary_institution_student_absences`');
        $this->execute('RENAME TABLE `zz_7636_summary_institution_student_absences` TO `summary_institution_student_absences`');

        $this->execute('DROP TABLE IF EXISTS `summary_isced_sectors`');
        $this->execute('RENAME TABLE `zz_7636_summary_isced_sectors` TO `summary_isced_sectors`');

        $this->execute('DROP TABLE IF EXISTS `summary_programme_sector_genders`');
        $this->execute('RENAME TABLE `zz_7636_summary_programme_sector_genders` TO `summary_programme_sector_genders`');

        $this->execute('DROP TABLE IF EXISTS `summary_programme_sector_specialization_genders`');
        $this->execute('RENAME TABLE `zz_7636_summary_programme_sector_specialization_genders` TO `summary_programme_sector_specialization_genders`');

    }
}

?>
