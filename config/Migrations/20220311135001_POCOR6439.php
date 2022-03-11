<?php
use Migrations\AbstractMigration;

class POCOR6439 extends AbstractMigration
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
        /*Backup report_queries table*/
        $this->execute('CREATE TABLE `z_report_queries` LIKE `report_queries`');
        $this->execute('INSERT INTO `z_report_queries` SELECT * FROM `report_queries`');

        /*Delete OpenEMIS Core report_student_attendance_summary row in report_queries table */
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_student_attendance_summary_truncate"'); 
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_student_attendance_summary_insert"'); 
        
        /*Insert queries*/
        $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
        VALUES ("report_student_attendance_summary_truncate","TRUNCATE report_student_attendance_summary;","day", 1, 1, NOW())');
        
        $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
        VALUES ("report_student_attendance_summary_insert", "INSERT INTO report_student_attendance_summary SELECT institution_class_grades.education_grade_id, education_grades.name education_grade_name, student_attendance_marked_records.institution_class_id, institution_classes.name, institutions.id institution_id, institutions.name institution_name, student_attendance_marked_records.academic_period_id, academic_periods.name academic_period_name, IF(student_attendance_marked_records.subject_id=0,student_attendance_marked_records.period,NULL) period_name, IF(student_attendance_marked_records.subject_id!=0,institution_subjects.name,NULL) subject_name, student_attendance_marked_records.date attendance_date, IF(male_count IS NULL,0,male_count), IF(female_count IS NULL,0,female_count), IF((male_count+female_count) IS NULL, 0,(male_count+female_count)) total_students, IF(absent_female_count IS NULL,IF(female_count IS NULL,0,female_count),(female_count-absent_female_count)) present_female_count, IF(absent_male_count IS NULL,IF(male_count IS NULL,0,male_count),(male_count-absent_male_count)) present_male_count, IF(absent_total_count IS NULL,IF((male_count+female_count) IS NULL, 0,(male_count+female_count)),(male_count+female_count-absent_total_count)) present_total_count, IF(absent_female_count IS NULL,0,absent_female_count) absent_female_count, IF(absent_male_count IS NULL,0,absent_male_count) absent_male_count, IF(absent_total_count IS NULL,0,absent_total_count) absent_total_count, IF(late_female_count IS NULL,0,late_female_count) late_female_count, IF(late_male_count IS NULL,0,late_male_count) late_male_count, IF(late_total_count IS NULL,0,late_total_count) late_total_count, CURRENT_TIMESTAMP FROM student_attendance_marked_records INNER JOIN institution_classes ON student_attendance_marked_records.academic_period_id = institution_classes.academic_period_id AND student_attendance_marked_records.institution_id = institution_classes.institution_id AND student_attendance_marked_records.institution_class_id = institution_classes.id INNER JOIN institution_class_grades ON institution_class_grades.institution_class_id = institution_classes.id AND student_attendance_marked_records.education_grade_id = institution_class_grades.education_grade_id LEFT JOIN(SELECT id institution_class_id,total_male_students male_count,total_female_students female_count FROM institution_classes) class_student_counter_mainq ON class_student_counter_mainq.institution_class_id = institution_classes.id INNER JOIN academic_periods ON academic_periods.id = student_attendance_marked_records.academic_period_id LEFT JOIN institution_subjects ON institution_subjects.id = student_attendance_marked_records.subject_id INNER JOIN education_grades ON education_grades.id = student_attendance_marked_records.education_grade_id AND education_grades.id = institution_class_grades.education_grade_id INNER JOIN institutions ON institutions.id = institution_classes.institution_id LEFT JOIN(SELECT institution_class_id,education_grade_id,date,period, subject_id, absence_type_id, SUM(count_absent_male) absent_male_count, SUM(count_absent_female) absent_female_count, SUM(count_absent_total) absent_total_count, SUM(count_late_male) late_male_count, SUM(count_late_female) late_female_count, SUM(count_late_total) late_total_count FROM (SELECT institution_student_absence_details.institution_class_id,institution_student_absence_details.education_grade_id,date,period, subject_id, absence_type_id, IF(gender_id = 1 AND absence_type_id IN (1,2) ,count(*),0) count_absent_male, IF(gender_id = 2 AND absence_type_id IN (1,2),count(*),0) count_absent_female, IF(absence_type_id IN (1,2),count(*),0) count_absent_total, IF(gender_id = 1 AND absence_type_id = 3 ,count(*),0) count_late_male, IF(gender_id = 2 AND absence_type_id =3 ,count(*),0) count_late_female, IF(absence_type_id = 3,count(*),0) count_late_total FROM institution_student_absence_details INNER JOIN institution_class_students ON institution_student_absence_details.student_id = institution_class_students.student_id AND institution_student_absence_details.institution_class_id = institution_class_students.institution_class_id INNER JOIN security_users ON security_users.id = institution_student_absence_details.student_id GROUP BY institution_student_absence_details.institution_class_id,education_grade_id,date,period, subject_id, absence_type_id, gender_id) subq GROUP BY institution_class_id,education_grade_id,date,period, subject_id) student_absences ON student_absences.institution_class_id = institution_classes.id AND student_absences.education_grade_id = institution_class_grades.education_grade_id AND student_absences.date = student_attendance_marked_records.date AND student_absences.period = student_attendance_marked_records.period AND student_absences.subject_id = student_attendance_marked_records.subject_id;","day", 1, 1, NOW())');
        
    }
    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `report_queries`');
        $this->execute('RENAME TABLE `z_report_queries` TO `report_queries`');
    }
}