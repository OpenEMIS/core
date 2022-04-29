<?php
use Migrations\AbstractMigration;

class POCOR6702 extends AbstractMigration
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

        
        $this->execute('CREATE TABLE `zz_report_student_assessment_summary` LIKE `report_student_assessment_summary`');
        $this->execute('INSERT INTO `zz_report_student_assessment_summary` SELECT * FROM `report_student_assessment_summary`');
        $this->execute('CREATE TABLE `zz_report_queries` LIKE `report_queries`');
        $this->execute('INSERT INTO `zz_report_queries` SELECT * FROM `report_queries`');

        /** Delete OpenEMIS Core report_assessment_missing_mark_entry table */
        $this->execute('DROP TABLE IF EXISTS `report_student_assessment_summary`');

        /** Create OpenEMIS Core report_student_assessment_summary table */
        $this->execute('
        CREATE TABLE `report_student_assessment_summary` (
            `academic_period_id` int(10) DEFAULT NULL,
            `academic_period_code` varchar(100) DEFAULT NULL,
            `academic_period_name` varchar(100) DEFAULT NULL,
            `area_id` int(10) DEFAULT NULL,
            `area_code` varchar(100) DEFAULT NULL,
            `area_name` varchar(100) DEFAULT NULL,
            `institution_id` int(10) DEFAULT NULL,
            `institution_code` varchar(100) DEFAULT NULL,
            `institution_name` varchar(100) DEFAULT NULL,
            `grade_id` int(10) DEFAULT NULL,
            `grade_code` varchar(100) DEFAULT NULL,
            `grade_name` varchar(100) DEFAULT NULL,
            `institution_classes_id` int(11) NOT NULL,
            `institution_classes_name` varchar(250) NOT NULL,
            `homeroom_teacher_id` int(11) NOT NULL,
            `homeroom_teacher_name` varchar(250) NOT NULL,
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
            `student_id` int(11) NOT NULL,
            `student_name` varchar(250) NOT NULL,
            `latest_mark` float NOT NULL,
            `total_mark` float NULL,
            `average_mark` float NULL,
            `institution_average_mark` float NULL,
            `area_average_mark` float NULL,
            `created` datetime NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
      ');
    
      $this->execute("ALTER TABLE `report_student_assessment_summary` ADD KEY `academic_period_id`(`academic_period_id`), ADD KEY `area_id` (`area_id`), ADD KEY `institution_id` (`institution_id`), ADD KEY `grade_id` (`grade_id`), ADD KEY `subject_id` (`subject_id`), ADD KEY `assessment_id` (`assessment_id`), ADD KEY `period_id` (`period_id`), ADD KEY `institution_classes_id` (`institution_classes_id`), ADD KEY `homeroom_teacher_id` (`homeroom_teacher_id`), ADD KEY `student_id` (`student_id`)");
      $this->execute('DELETE FROM report_queries WHERE name = "report_student_assessment_summary_insert"');
      $this->execute('DELETE FROM report_queries WHERE name = "report_student_assessment_summary_truncate"');

      $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
      VALUES ("report_student_assessment_summary_truncate","TRUNCATE report_student_assessment_summary;","year", 1, 1, NOW())');

      $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
          VALUES ("report_student_assessment_summary_insert", "INSERT INTO report_student_assessment_summary SELECT academic_periods.id academic_period_id, academic_periods.code academic_period_code, academic_periods.name academic_period_name, areas.id area_id, areas.code area_code, areas.name area_name, institutions.id institution_id, institutions.code institution_code, institutions.name institution_name, education_grades.id education_grade_id, education_grades.code education_grade_code, education_grades.name education_grade_name, institution_classes.id institution_class_id, institution_classes.name institution_class_name, homeroom_teacher.id homeroom_teacher_id, CONCAT(homeroom_teacher.first_name,homeroom_teacher.last_name) homeroom_teacher_name, education_subjects.id education_subject_id, education_subjects.code education_subject_code, education_subjects.name education_subject_name, assessment_items.weight subject_weight, assessments.id assessment_id, assessments.code assessment_code, assessments.name assessment_name, assessment_periods.id assessment_period_id, assessment_periods.code assessment_period_code, assessment_periods.name assessment_period_name, assessment_periods.academic_term, assessment_periods.weight period_weight, students.id student_id, CONCAT(students.first_name,students.last_name) student_name, latest_mark, total_mark, average_mark, instituion_average_mark, area_average_mark, NOW() created FROM assessments INNER JOIN assessment_periods ON assessment_periods.assessment_id = assessments.id INNER JOIN assessment_items ON assessment_items.assessment_id = assessments.id INNER JOIN academic_periods ON academic_periods.id = assessments.academic_period_id INNER JOIN(SELECT assessment_id,education_subject_id,assessment_item_results.education_grade_id,assessment_item_results.academic_period_id,assessment_period_id,assessment_item_results.institution_id,prev_class_student.institution_class_id,assessment_item_results.student_id, ROUND(IF(institution_class_students.id IS NOT NULL,IF(institution_class_students.id IS NOT NULL,MAX(marks*100),MIN(marks/100))/100,IF(institution_class_students.id IS NOT NULL,MAX(marks*100),MIN(marks/100))*100),2) latest_mark FROM assessment_item_results LEFT JOIN institution_class_students ON institution_class_students.institution_class_id = assessment_item_results.institution_classes_id AND institution_class_students.student_id = assessment_item_results.student_id LEFT JOIN institution_class_students prev_class_student ON prev_class_student.student_id = assessment_item_results.student_id GROUP BY assessment_item_results.student_id,assessment_item_results.assessment_period_id,education_subject_id) get_latest_mark ON get_latest_mark.assessment_id = assessments.id AND get_latest_mark.assessment_period_id = assessment_periods.id AND get_latest_mark.academic_period_id = academic_periods.id INNER JOIN education_subjects ON education_subjects.id = get_latest_mark.education_subject_id AND assessment_items.education_subject_id = education_subjects.id INNER JOIN institutions ON get_latest_mark.institution_id = institutions.id INNER JOIN areas ON areas.id = institutions.area_id INNER JOIN institution_classes ON institution_classes.id = get_latest_mark.institution_class_id INNER JOIN institution_class_students ON institution_class_students.student_id = get_latest_mark.student_id INNER JOIN security_users homeroom_teacher ON institution_classes.staff_id = homeroom_teacher.id INNER JOIN security_users students ON students.id = get_latest_mark.student_id INNER JOIN education_grades ON education_grades.id = get_latest_mark.education_grade_id LEFT JOIN institution_subject_students ON institution_subject_students.student_id = get_latest_mark.student_id AND institution_subject_students.institution_class_id = get_latest_mark.institution_class_id AND institution_subject_students.education_subject_id = get_latest_mark.education_subject_id AND institution_subject_students.education_grade_id = get_latest_mark.education_grade_id LEFT JOIN (SELECT ROUND(AVG(total_mark),2) average_mark,student_id,institution_class_id,education_grade_id,institution_id,academic_period_id FROM institution_subject_students GROUP BY student_id,education_grade_id,institution_class_id) get_total_mark ON get_total_mark.student_id = get_latest_mark.student_id AND get_total_mark.institution_class_id =get_latest_mark.institution_class_id AND get_total_mark.education_grade_id = get_latest_mark.education_grade_id AND get_total_mark.institution_id = get_latest_mark.institution_id AND get_total_mark.academic_period_id = get_latest_mark.academic_period_id LEFT JOIN (SELECT institution_id,education_grade_id,academic_period_id,ROUND(SUM(total_mark)/count(*),2) instituion_average_mark FROM `institution_subject_students` GROUP BY institution_id,education_grade_id,academic_period_id) get_instituion_average_mark ON get_instituion_average_mark.education_grade_id = get_latest_mark.education_grade_id AND get_instituion_average_mark.institution_id = get_latest_mark.institution_id AND get_instituion_average_mark.academic_period_id = get_latest_mark.academic_period_id LEFT JOIN (SELECT education_grade_id,area_id,academic_period_id,ROUND(SUM(total_mark)/count(*),2) area_average_mark FROM institution_subject_students INNER JOIN institutions ON institution_subject_students.institution_id = institutions.id GROUP BY education_grade_id,area_id,academic_period_id) get_area_average_mark ON get_area_average_mark.education_grade_id = get_latest_mark.education_grade_id AND get_area_average_mark.academic_period_id = get_latest_mark.academic_period_id AND get_area_average_mark.area_id = areas.id;","year", 1, 1, NOW())');
    
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