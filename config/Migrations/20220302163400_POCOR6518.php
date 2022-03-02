<?php
use Migrations\AbstractMigration;

class POCOR6518 extends AbstractMigration
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
        /** Create OpenEMIS Core report_student_assessment_summary table */
        $this->execute('
        CREATE TABLE `report_student_assessment_summary`(
            `academic_period_id` int(10) DEFAULT NULL,
            `academic_period_code` varchar(100) DEFAULT NULL,
            `academic_period_name` varchar(100) DEFAULT NULL,
            `area_id` int(10) DEFAULT NULL,
            `area_name` varchar(100) DEFAULT NULL,
            `area_code` varchar(100) DEFAULT NULL,
            `institution_id` int(10) DEFAULT NULL,
            `institution_code` varchar(100) DEFAULT NULL,
            `institution_name` varchar(100) DEFAULT NULL,
            `grade_id` int(10) DEFAULT NULL,
            `grade_code` varchar(100) DEFAULT NULL,
            `grade_name` varchar(100) DEFAULT NULL,
            `subject_id` int(10) DEFAULT NULL,
            `subject_code` varchar(100) DEFAULT NULL,
            `subject_name` varchar(100) DEFAULT NULL,
            `subject_weight` decimal(6,2) DEFAULT 0.00,
            `assessment_id` int(10) DEFAULT NULL,
            `assessment_code` varchar(100) DEFAULT NULL,
            `assessment_name` varchar(100) DEFAULT NULL,
            `period_id` int(10) DEFAULT NULL,
            `period_code` varchar(100) DEFAULT NULL,
            `period_name` varchar(100) DEFAULT NULL,
            `academic_term` varchar(100) DEFAULT NULL,
            `period_weight` decimal(6,2) DEFAULT 0.00,
            `average_marks` int(10) DEFAULT NULL,
            `created` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
      ');

      $this->execute("ALTER TABLE `report_student_assessment_summary` ADD KEY `academic_period_id` (`academic_period_id`), ADD KEY `academic_period_code` (`academic_period_code`), ADD KEY `academic_period_name` (`academic_period_name`), ADD KEY `area_id` (`area_id`), ADD KEY `area_name` (`area_name`), ADD KEY `area_code` (`area_code`), ADD KEY `institution_id` (`institution_id`), ADD KEY `institution_code` (`institution_code`), ADD KEY `institution_name` (`institution_name`), ADD KEY `grade_id` (`grade_id`), ADD KEY `grade_code` (`grade_code`), ADD KEY `grade_name` (`grade_name`), ADD KEY `subject_id` (`subject_id`), ADD KEY `subject_code` (`subject_code`), ADD KEY `subject_name` (`subject_name`), ADD KEY `subject_weight` (`subject_weight`), ADD KEY `assessment_id,` (`assessment_id,`)  ADD KEY `assessment_code` (`assessment_code`), ADD KEY `assessment_name` (`assessment_name`), ADD KEY `period_id` (`period_id`), ADD KEY `period_code` (`period_code`), ADD KEY `period_name` (`period_name`), ADD KEY `academic_term` (`academic_term`), ADD KEY `period_weight` (`period_weight`), ADD KEY `average_marks` (`average_marks`), ADD KEY `created` (`created`)");

      $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
      VALUES ("report_student_assessment_summary_truncate","TRUNCATE report_student_assessment_summary;","year", 1, 1, NOW())');


      $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
          VALUES ("report_student_assessment_summary_insert", "INSERT INTO report_student_assessment_summary SELECT academic_periods.id academic_period_id, academic_periods.code academic_period_code, academic_periods.name academic_period_name, areas.id area_id, areas.name area_name, areas.code area_code, institutions.id institution_id, institutions.code institution_code, institutions.name institution_name, education_grades.id grade_id, education_grades.code grade_code, education_grades.name grade_name, education_subjects.id subject_id, education_subjects.code subject_code, education_subjects.name subject_name, assessment_items.weight subject_weight, assessments.id assessment_id, assessments.code assessment_code, assessments.name assessment_name, assessment_periods.id period_id, assessment_periods.code period_code, assessment_periods.name period_name, assessment_periods.academic_term, assessment_periods.weight period_weight, IF(average_marks IS NULL,0,average_marks) average_marks, CURRENT_TIMESTAMP FROM assessments INNER JOIN assessment_periods ON assessment_periods.assessment_id = assessments.id INNER JOIN assessment_items ON assessment_items.assessment_id = assessments.id INNER JOIN institution_grades ON institution_grades.education_grade_id = assessments.education_grade_id INNER JOIN institutions ON institutions.id = institution_grades.institution_id INNER JOIN academic_periods ON academic_periods.id = assessments.academic_period_id INNER JOIN areas ON areas.id = institutions.area_id INNER JOIN education_grades ON education_grades.id = assessments.education_grade_id INNER JOIN education_subjects ON education_subjects.id = assessment_items.education_subject_id LEFT JOIN(SELECT assessment_id,assessment_period_id,academic_period_id,institution_id,education_subject_id,education_grade_id,marks,ROUND(AVG(marks),2) average_marks FROM assessment_item_results INNER JOIN (SELECT student_id s_student_id,education_subject_id s_education_subject_id,education_grade_id s_education_grade_id,academic_period_id s_academic_period_id,max(created) latest_mark FROM assessment_item_results WHERE student_id IS NOT NULL AND education_subject_id IS NOT NULL AND education_grade_id IS NOT NULL AND academic_period_id IS NOT NULL GROUP BY student_id,education_subject_id,education_grade_id,academic_period_id) get_latest_assessment_item_results ON assessment_item_results.student_id = get_latest_assessment_item_results.s_student_id AND assessment_item_results.education_subject_id = get_latest_assessment_item_results.s_education_subject_id AND assessment_item_results.education_grade_id = get_latest_assessment_item_results.s_education_grade_id AND assessment_item_results.academic_period_id = get_latest_assessment_item_results.s_academic_period_id AND assessment_item_results.created = get_latest_assessment_item_results.latest_mark GROUP BY institution_id,assessment_id,assessment_period_id) get_assessment_results ON assessments.id = get_assessment_results.assessment_id AND assessment_periods.id = get_assessment_results.assessment_period_id AND academic_periods.id = get_assessment_results.academic_period_id AND institutions.id = get_assessment_results.institution_id AND education_subjects.id = get_assessment_results.education_subject_id;","year", 1, 1, NOW())');
    
    }
    //rollback
    public function down()
    {
        /** Delete OpenEMIS Core report_assessment_missing_mark_entry table */
        $this->execute('DROP TABLE IF EXISTS `report_student_assessment_summary`');
        
        /** Delete OpenEMIS Core report_assessment_missing_mark_entry row in report_queries table */
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_student_assessment_summary_truncate"'); 
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_student_assessment_summary_insert"'); 
    }
}


