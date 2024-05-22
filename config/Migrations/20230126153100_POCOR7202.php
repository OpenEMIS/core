<?php
use Migrations\AbstractMigration;

class POCOR7202 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_7202_report_queries` LIKE `report_queries`');
        $this->execute('INSERT INTO `zz_7202_report_queries` SELECT * FROM `report_queries`');


        // CREATE summary tables and INSERT new rows into report_queries table        
        $this->execute('INSERT INTO `report_queries` (`name`, `query_sql`, `frequency`, `status`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("summary_area_provider_grade_subject_results_create", "CREATE TABLE IF NOT EXISTS `summary_area_provider_grade_subject_results`( `academic_period_id` int(11) NOT NULL ,`academic_period_name` varchar(200) NOT NULL ,`area_id` int(11) NOT NULL ,`area_code` varchar(200) NOT NULL ,`area_name` varchar(200) NOT NULL ,`institution_provider_id` int(11) NOT NULL ,`institution_provider_name` varchar(200) NOT NULL ,`education_grade_id` int(11) NOT NULL ,`education_grade_code` varchar(200) NOT NULL ,`education_grade_name` varchar(200) NOT NULL ,`education_subject_id` int(11) NOT NULL ,`education_subject_code` varchar(200) NOT NULL ,`education_subject_name` varchar(200) NOT NULL ,`total_avg_results` varchar(200) NULL ,`male_avg_results` varchar(200) NULL ,`female_avg_results` varchar(200) NULL ,`created` datetime NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "week", 1, NULL, NULL, 1, CURRENT_TIMESTAMP)');
        $this->execute('INSERT INTO `report_queries` (`name`, `query_sql`, `frequency`, `status`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("summary_area_provider_grade_subject_results_truncate", "TRUNCATE summary_area_provider_grade_subject_results;", "week", 1, NULL, NULL, 1, CURRENT_TIMESTAMP)');
        $this->execute('INSERT INTO `report_queries` (`name`, `query_sql`, `frequency`, `status`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("summary_area_provider_grade_subject_results_insert", "INSERT INTO `summary_area_provider_grade_subject_results`(`academic_period_id`, `academic_period_name`, `area_id`, `area_code`, `area_name`, `institution_provider_id`, `institution_provider_name`, `education_grade_id`, `education_grade_code`, `education_grade_name`, `education_subject_id`, `education_subject_code`, `education_subject_name`, `total_avg_results`, `male_avg_results`, `female_avg_results`, `created`) SELECT subq.academic_period_id ,subq.academic_period_name ,subq.area_id ,subq.area_code ,subq.area_name ,subq.institution_provider_id ,subq.institution_provider_name ,subq.education_grade_id ,subq.education_grade_code ,subq.education_grade_name ,subq.education_subject_id ,subq.education_subject_code ,subq.education_subject_name ,ROUND(AVG(subq.all_total), 5) total_avg_results ,ROUND(AVG(subq.all_male), 5) male_avg_results ,ROUND(AVG(subq.all_female), 5) female_avg_results ,CURRENT_TIMESTAMP() created FROM ( SELECT areas.id area_id ,areas.code area_code ,areas.name area_name ,academic_periods.id academic_period_id ,academic_periods.name academic_period_name ,institution_providers.id institution_provider_id ,institution_providers.name institution_provider_name ,education_grades.id education_grade_id ,education_grades.code education_grade_code ,education_grades.name education_grade_name ,education_subjects.id education_subject_id ,education_subjects.code education_subject_code ,education_subjects.name education_subject_name ,SUM(assessment_item_results.marks * assessment_periods.weight) / IFNULL(assessment_grading_types.max, CEILING(MAX(assessment_item_results.marks) / 10) * 10) * 100 all_total ,CASE WHEN genders.id = 1 THEN SUM(assessment_item_results.marks * assessment_periods.weight) / IFNULL(assessment_grading_types.max, CEILING(MAX(assessment_item_results.marks) / 10) * 10) * 100 END all_male ,CASE WHEN genders.id = 2 THEN SUM(assessment_item_results.marks * assessment_periods.weight) / IFNULL(assessment_grading_types.max, CEILING(MAX(assessment_item_results.marks) / 10) * 10) * 100 END all_female FROM assessment_item_results INNER JOIN ( SELECT assessment_item_results.student_id ,assessment_item_results.assessment_id ,assessment_item_results.education_subject_id ,assessment_item_results.assessment_period_id ,MAX(assessment_item_results.created) latest_created FROM assessment_item_results GROUP BY assessment_item_results.student_id ,assessment_item_results.assessment_id ,assessment_item_results.education_subject_id ,assessment_item_results.assessment_period_id) latest_grades ON latest_grades.student_id = assessment_item_results.student_id AND latest_grades.assessment_id = assessment_item_results.assessment_id AND latest_grades.education_subject_id = assessment_item_results.education_subject_id AND latest_grades.assessment_period_id = assessment_item_results.assessment_period_id AND latest_grades.latest_created = assessment_item_results.created LEFT JOIN assessment_grading_options ON assessment_grading_options.id = assessment_item_results.assessment_grading_option_id LEFT JOIN assessment_grading_types ON assessment_grading_types.id = assessment_grading_options.assessment_grading_type_id INNER JOIN assessment_periods ON assessment_periods.id = assessment_item_results.assessment_period_id AND assessment_periods.assessment_id = assessment_item_results.assessment_id INNER JOIN academic_periods ON academic_periods.id = assessment_item_results.academic_period_id INNER JOIN institutions ON institutions.id = assessment_item_results.institution_id INNER JOIN institution_providers ON institution_providers.id = institutions.institution_provider_id INNER JOIN areas ON areas.id = institutions.area_id INNER JOIN security_users ON security_users.id = assessment_item_results.student_id INNER JOIN genders ON genders.id = security_users.gender_id INNER JOIN education_grades ON education_grades.id = assessment_item_results.education_grade_id INNER JOIN education_subjects ON education_subjects.id = assessment_item_results.education_subject_id GROUP BY education_subjects.id ,security_users.id ) subq GROUP BY subq.area_id ,subq.academic_period_id ,subq.institution_provider_id ,subq.education_grade_id ,subq.education_subject_id", "week", 1, NULL, NULL, 1, CURRENT_TIMESTAMP)');
    
    }
         
    // rollback
    public function down()
    {
        
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `report_queries`');
        $this->execute('RENAME TABLE `zz_7202_report_queries` TO `report_queries`');

        // Drop summary tables
        $this->execute('DROP TABLE IF EXISTS `summary_area_provider_grade_subject_results`');

    }
}
?>