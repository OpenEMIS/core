<?php
use Migrations\AbstractMigration;

class POCOR6513 extends AbstractMigration
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
        /** Create OpenEMIS Core report_assessment_missing_mark_entry table */
        $this->execute('
        CREATE TABLE `report_assessment_missing_mark_entry`(
            `academic_period_id` int(10) DEFAULT NULL,
            `academic_period_name` varchar(100) DEFAULT NULL,
            `assessment_id` int(10) DEFAULT NULL,
            `assessment_code` varchar(100) DEFAULT NULL,
            `assessment_name` varchar(100) DEFAULT NULL,
            `assessment_period_id` int(10) DEFAULT NULL,
            `assessment_period_name` varchar(100) DEFAULT NULL,
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
            `count_students` int(10) DEFAULT NULL,
            `count_marked_students` int(10) DEFAULT NULL,
            `missing_marks` int(10) DEFAULT NULL,
            `created` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
      ');

      $this->execute("ALTER TABLE `report_assessment_missing_mark_entry` ADD KEY `academic_period_id` (`academic_period_id`),ADD KEY `assessment_id` (`assessment_id`),ADD KEY `assessment_period_id` (`assessment_period_id`),ADD KEY `education_grade_id` (`education_grade_id`),ADD KEY `institution_id` (`institution_id`),ADD KEY `institution_provider_id` (`institution_provider_id`),ADD KEY `area_id` (`area_id`)");

      $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
      VALUES ("report_assessment_missing_mark_entry_truncate","TRUNCATE report_student_attendance_summary;","month", 1, 1, NOW())');


      $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
          VALUES ("report_assessment_missing_mark_entry_insert", "INSERT INTO report_assessment_missing_mark_entry SELECT academic_periods.id academic_period_id, academic_periods.name academic_period_name, get_assessments.assessment_id, assessment_code, assessment_name, assessment_periods.id assessment_period_id, assessment_periods.name assessment_period_name, subject_id, subject_name, education_grades.id education_grade_id, education_grades.name education_grade, institutions.id institution_id, institutions.code institution_code, institutions.name institution_name, institution_providers.id institution_provider_id, institution_providers.name institution_provider, area_edu.area_id area_id, area_edu.area_name area, IF(count_students IS NULL,0,count_students) count_students, IF(count_marked_students IS NULL,0,count_marked_students) count_marked_students, IF(count_students IS NULL,0,IF(count_marked_students IS NULL,count_students,count_students-count_marked_students)) missing_marks, CURRENT_TIMESTAMP FROM(SELECT assessments.id assessment_id,assessments.code assessment_code,assessments.name assessment_name,assessments.academic_period_id,assessments.education_grade_id,assessment_periods.id assessment_period_id,education_subjects.id subject_id,education_subjects.code subject_code, education_subjects.name subject_name FROM assessments INNER JOIN assessment_periods ON assessments.id = assessment_periods.assessment_id INNER JOIN assessment_items ON assessment_items.assessment_id = assessments.id INNER JOIN education_subjects ON assessment_items.education_subject_id = education_subjects.id) get_assessments LEFT JOIN assessment_periods ON assessment_periods.id = get_assessments.assessment_period_id INNER JOIN academic_periods ON academic_periods.id = get_assessments.academic_period_id LEFT JOIN institution_grades ON institution_grades.education_grade_id = get_assessments.education_grade_id INNER JOIN institutions ON institutions.id = institution_grades.institution_id INNER JOIN institution_providers ON institution_providers.id = institutions.institution_provider_id LEFT JOIN (SELECT areas.id AS area_id, areas.name AS area_name FROM areas INNER JOIN area_levels ON area_levels.id = areas.area_level_id) AS area_edu ON institutions.area_id = area_edu.area_id INNER JOIN education_grades ON education_grades.id = get_assessments.education_grade_id LEFT JOIN (SELECT academic_period_id, institution_id, education_grade_id, education_subject_id, count(*) AS count_students FROM institution_subject_students GROUP BY academic_period_id, institution_id, education_grade_id, education_subject_id) AS get_total_student_subjects ON get_total_student_subjects.institution_id = institutions.id AND get_total_student_subjects.academic_period_id = get_assessments.academic_period_id AND get_total_student_subjects.education_grade_id = get_assessments.education_grade_id AND get_total_student_subjects.education_subject_id = get_assessments.subject_id LEFT JOIN (SELECT academic_period_id, assessment_id, assessment_period_id, institution_id, education_subject_id, count(*) count_marked_students FROM (SELECT student_id, institution_id, academic_period_id, education_subject_id, assessment_id, assessment_period_id FROM assessment_item_results GROUP BY student_id, institution_id, academic_period_id, assessment_id, assessment_period_id, education_subject_id) get_grouped_students_subjects GROUP BY institution_id, academic_period_id, assessment_period_id, education_subject_id) get_marked_students ON get_marked_students.institution_id = institutions.id AND get_marked_students.assessment_id = get_assessments.assessment_id AND get_marked_students.assessment_period_id = assessment_periods.id AND get_marked_students.academic_period_id = academic_periods.id AND get_marked_students.education_subject_id = get_assessments.subject_id WHERE area_edu.area_id IS NOT NULL AND institutions.id IS NOT NULL AND education_grades.id IS NOT NULL AND get_assessments.assessment_id IS NOT NULL AND assessment_periods.id IS NOT NULL ORDER BY academic_periods.id ASC, institutions.id ASC, assessment_periods.id ASC;","month", 1, 1, NOW())');
    
    }
    //rollback
    public function down()
    {
        /** Delete OpenEMIS Core report_assessment_missing_mark_entry table */
        $this->execute('DROP TABLE IF EXISTS `report_assessment_missing_mark_entry`');
        
        /** Delete OpenEMIS Core report_assessment_missing_mark_entry row in report_queries table */
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_assessment_missing_mark_entry_truncate"'); 
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_assessment_missing_mark_entry_insert"'); 
    }
}