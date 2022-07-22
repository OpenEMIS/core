<?php
use Migrations\AbstractMigration;

class POCOR6848 extends AbstractMigration
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
        /*backup report_queries table */
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_6848_report_queries` LIKE `report_queries`');
        $this->execute('INSERT INTO `zz_6848_report_queries` SELECT * FROM `report_queries`');
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_6848_report_assessment_missing_mark_entry` LIKE `report_assessment_missing_mark_entry`');
        $this->execute('INSERT INTO `zz_6848_report_assessment_missing_mark_entry` SELECT * FROM `report_assessment_missing_mark_entry`');
        $this->execute('DROP TABLE IF EXISTS `report_assessment_missing_mark_entry`');

        /*delete existing report_assessment_missing_mark_entry in report_queries table */
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_assessment_missing_mark_entry_truncate"'); 
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_assessment_missing_mark_entry_insert"');

        /*create summary_assessment_item_results summary table */
        $this->execute('CREATE TABLE IF NOT EXISTS `summary_assessment_item_results`(
            `academic_period_id` int(10) DEFAULT NULL, 
            `academic_period_name` varchar(100) DEFAULT NULL, 
            `assessment_id` int(10) DEFAULT NULL, 
            `assessment_code` varchar(100) DEFAULT NULL, 
            `assessment_name` varchar(100) DEFAULT NULL, 
            `assessment_period_id` int(10) DEFAULT NULL, 
            `assessment_period_name` varchar(100) DEFAULT NULL, 
            `academic_term` varchar(100) DEFAULT NULL, 
            `subject_id` int(10) DEFAULT NULL, 
            `subject_name` varchar(100) DEFAULT NULL, 
            `education_grade_id` int(10) DEFAULT NULL, 
            `education_grade` varchar(100) DEFAULT NULL, 
            `institution_id` int(10) DEFAULT NULL, 
            `institution_code` varchar(100) DEFAULT NULL, 
            `institution_name` varchar(100) DEFAULT NULL, 
            `institution_provider_id` int(10) DEFAULT NULL, 
            `institution_provider` varchar(100) DEFAULT NULL, 
            `area_id` int(10) DEFAULT NULL, 
            `area_name` varchar(100) DEFAULT NULL, 
            `institution_class_id` int(10) DEFAULT NULL, 
            `institution_class_name` varchar(100) DEFAULT NULL, 
            `count_students` int(10) DEFAULT NULL, 
            `count_marked_students` int(10) DEFAULT NULL, 
            `missing_marks` int(10) DEFAULT NULL, 
            `created` datetime NOT NULL, 
            INDEX(
                `academic_period_id`, `assessment_id`, 
                `assessment_period_id`, `education_grade_id`, 
                `institution_id`, `institution_provider_id`, 
                `area_id`)
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8;');
            
        /*insert into report_queries table */
        /*create get_total_student_subjects temporary table */
        $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
        VALUES ("get_total_student_subjects_create","CREATE TABLE IF NOT EXISTS `get_total_student_subjects`( `academic_period_id` INT(10) NOT NULL, `institution_id` INT(10) NOT NULL, `education_grade_id` INT(10) NOT NULL, `institution_class_id` INT(10) NOT NULL, `education_subject_id` INT(10) NOT NULL, `count_students` INT(10) NOT NULL, INDEX(`academic_period_id`, `institution_id`, `education_grade_id`, `education_subject_id`)) ENGINE = InnoDB DEFAULT CHARSET=utf8;","week", 1, 1, NOW())');

        /*insert get_total_student_subjects temporary table */
        $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
        VALUES ("get_total_student_subjects_insert","INSERT INTO get_total_student_subjects SELECT institution_subject_students.academic_period_id, institution_subject_students.institution_id, institution_subject_students.education_grade_id, institution_class_students.institution_class_id, institution_subject_students.education_subject_id, count(*) AS count_students FROM institution_subject_students INNER JOIN academic_periods ON academic_periods.id = academic_period_id INNER JOIN institution_students ON institution_students.student_id = institution_subject_students.student_id AND institution_students.education_grade_id = institution_subject_students.education_grade_id AND institution_students.academic_period_id = institution_subject_students.academic_period_id AND institution_students.student_status_id = institution_subject_students.student_status_id AND institution_students.institution_id = institution_subject_students.institution_id INNER JOIN institution_class_students ON institution_students.student_id = institution_class_students.student_id AND institution_students.education_grade_id = institution_class_students.education_grade_id AND institution_students.academic_period_id = institution_class_students.academic_period_id AND institution_students.student_status_id = institution_class_students.student_status_id AND institution_students.institution_id = institution_class_students.institution_id WHERE institution_students.student_status_id = 1 AND academic_periods.current = 1 GROUP BY institution_subject_students.academic_period_id, institution_subject_students.institution_id, institution_subject_students.education_grade_id, institution_class_students.institution_class_id, institution_subject_students.education_subject_id;","week", 1, 1, NOW())');

        /*create get_grouped_students_subjects temporary table */
        $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
        VALUES ("get_grouped_students_subjects_create","CREATE TABLE IF NOT EXISTS `get_grouped_students_subjects`( `student_id` int(10) NOT NULL, `institution_id` int(10) NOT NULL, `academic_period_id` int(10) NOT NULL, `institution_class_id` int(10) NOT NULL, `education_subject_id` int(10) NOT NULL, `assessment_id` int(10) NOT NULL, `assessment_period_id` int(10) NOT NULL, `academic_term` varchar(100) NOT NULL, INDEX(`student_id`, `institution_id`,`institution_class_id` ,`academic_period_id`, `education_subject_id`, `assessment_id`, `assessment_period_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;","week", 1, 1, NOW())');

        /*insert get_grouped_students_subjects temporary table */
        $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
        VALUES ("get_grouped_students_subjects_insert","INSERT INTO get_grouped_students_subjects SELECT assessment_item_results.student_id, assessment_item_results.institution_id, assessment_item_results.academic_period_id, institution_class_id, education_subject_id, assessment_item_results.assessment_id, assessment_period_id, academic_term FROM assessment_item_results INNER JOIN assessment_periods ON assessment_periods.assessment_id = assessment_item_results.assessment_id INNER JOIN academic_periods ON academic_periods.id = assessment_item_results.academic_period_id INNER JOIN institution_students ON institution_students.student_id = assessment_item_results.student_id AND institution_students.education_grade_id = assessment_item_results.education_grade_id AND institution_students.academic_period_id = assessment_item_results.academic_period_id AND institution_students.institution_id = assessment_item_results.institution_id INNER JOIN institution_class_students ON institution_students.student_id = institution_class_students.student_id AND institution_students.education_grade_id = institution_class_students.education_grade_id AND institution_students.academic_period_id = institution_class_students.academic_period_id AND institution_students.student_status_id = institution_class_students.student_status_id AND institution_students.institution_id = institution_class_students.institution_id WHERE assessment_item_results.marks IS NOT NULL AND institution_students.student_status_id = 1 AND academic_periods.current = 1 GROUP BY assessment_item_results.student_id, assessment_item_results.institution_id, assessment_item_results.academic_period_id, assessment_item_results.assessment_id, assessment_item_results.education_grade_id, institution_class_id, assessment_item_results.assessment_period_id, education_subject_id;","week", 1, 1, NOW())');

        /*create get_marked_students temporary table */
        $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
        VALUES ("get_marked_students_create","CREATE TABLE IF NOT EXISTS `get_marked_students` ( `academic_period_id` int(10) NOT NULL, `assessment_id` int(10) NOT NULL, `assessment_period_id` int(10) NOT NULL, `academic_term` varchar(100) NOT NULL, `institution_id` int(10) NOT NULL, `institution_class_id` int(10) NOT NULL, `education_subject_id` int(10) NOT NULL, `count_marked_students` int(10) NOT NULL, INDEX(`academic_period_id`, `assessment_id`, `assessment_period_id`, `institution_id`, `education_subject_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;","week", 1, 1, NOW())');

        /*insert get_marked_students temporary table */
        $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
        VALUES ("get_marked_students_insert","INSERT INTO get_marked_students SELECT academic_period_id, assessment_id, assessment_period_id, academic_term, institution_id, institution_class_id, education_subject_id, count(*) count_marked_students FROM get_grouped_students_subjects GROUP BY institution_id, academic_period_id, assessment_period_id, institution_class_id, education_subject_id;","week", 1, 1, NOW())');

        /*truncate summary_assessment_item_results summary table */
        $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
        VALUES ("summary_assessment_item_results_truncate","TRUNCATE summary_assessment_item_results;","week", 1, 1, NOW())');

        /*insert summary_assessment_item_results summary table */
        $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
        VALUES ("summary_assessment_item_results_insert","CREATE TABLE IF NOT EXISTS `summary_assessment_item_results`( `academic_period_id` int(10) DEFAULT NULL, `academic_period_name` varchar(100) DEFAULT NULL, `assessment_id` int(10) DEFAULT NULL, `assessment_code` varchar(100) DEFAULT NULL, `assessment_name` varchar(100) DEFAULT NULL, `assessment_period_id` int(10) DEFAULT NULL, `assessment_period_name` varchar(100) DEFAULT NULL, `academic_term` varchar(100) DEFAULT NULL, `subject_id` int(10) DEFAULT NULL, `subject_name` varchar(100) DEFAULT NULL, `education_grade_id` int(10) DEFAULT NULL, `education_grade` varchar(100) DEFAULT NULL, `institution_id` int(10) DEFAULT NULL, `institution_code` varchar(100) DEFAULT NULL, `institution_name` varchar(100) DEFAULT NULL, `institution_provider_id` int(10) DEFAULT NULL, `institution_provider` varchar(100) DEFAULT NULL, `area_id` int(10) DEFAULT NULL, `area_name` varchar(100) DEFAULT NULL, `institution_class_id` int(10) DEFAULT NULL, `institution_class_name` varchar(100) DEFAULT NULL, `count_students` int(10) DEFAULT NULL, `count_marked_students` int(10) DEFAULT NULL, `missing_marks` int(10) DEFAULT NULL, `created` datetime NOT NULL, INDEX( `academic_period_id`, `assessment_id`, `assessment_period_id`, `education_grade_id`, `institution_id`, `institution_provider_id`, `area_id`)) ENGINE = InnoDB DEFAULT CHARSET = utf8;","week", 1, 1, NOW())');

        /*drop temporary tables*/
        $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
        VALUES ("drop_summary_assessment_item_results_tmp","DROP TABLE IF EXISTS `get_grouped_students_subjects`, `get_marked_students`, `get_total_student_subjects`;","week", 1, 1, NOW())');
    }
    //rollback
    public function down()
    {
        /* Restore backup tables */
        $this->execute('DROP TABLE IF EXISTS `report_queries`');
        $this->execute('RENAME TABLE `zz_6848_report_queries` TO `report_queries`');
        $this->execute('CREATE TABLE IF NOT EXISTS `report_assessment_missing_mark_entry` LIKE `zz_6848_report_assessment_missing_mark_entry`');
        $this->execute('DROP TABLE IF EXISTS `zz_6848_report_assessment_missing_mark_entry`');
    }
}