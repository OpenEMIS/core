<?php
use Migrations\AbstractMigration;

class POCOR6725 extends AbstractMigration
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
        CREATE TABLE IF NOT EXISTS `summary_institution_student_absences`(
            `institution_id` int(10) DEFAULT NULL,
            `institution_code` varchar(100) DEFAULT NULL,
            `institution_name` varchar(100) DEFAULT NULL,
            `area_id` int(10) DEFAULT NULL,
            `area_code` varchar(100) DEFAULT NULL,
            `area_name` varchar(100) DEFAULT NULL,
            `area_administrative_id` int(10) DEFAULT NULL,
            `area_administrative_code` varchar(100) DEFAULT NULL,
            `area_administrative_name` varchar(100) DEFAULT NULL,
            `student_id` int(10) DEFAULT NULL,
            `openemis_no` varchar(100) DEFAULT NULL,
            `default_identity_number` varchar(100) DEFAULT NULL,
            `student_name` varchar(100) DEFAULT NULL,
            `enrol_start_date` varchar(100) DEFAULT NULL,
            `enrol_end_date` varchar(100) DEFAULT NULL,
            `academic_period_id` int(10) DEFAULT NULL,
            `academic_period_code` varchar(100) DEFAULT NULL,
            `academic_period_name` varchar(100) DEFAULT NULL,
            `education_grade_id` int(10) DEFAULT NULL,
            `education_grade_code` varchar(100) DEFAULT NULL,
            `education_grade_name` varchar(100) DEFAULT NULL,
            `absent_date` varchar(100) DEFAULT NULL,
            `absent_days` varchar(100) DEFAULT NULL,
            `absence_subject_period` varchar(100) DEFAULT NULL,
            `absence_type_id` int(10) DEFAULT NULL,
            `absence_type` varchar(100) DEFAULT NULL,
            `student_absence_reason_id` int(10) DEFAULT NULL,
            `student_absence_reasons` varchar(100) DEFAULT NULL,
            `student_status_id` int(10) DEFAULT NULL,
            `student_status` varchar(100) DEFAULT NULL,
            `created` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
      ');

      $this->execute("ALTER TABLE `summary_institution_student_absences` ADD INDEX( `institution_id`, `area_id`, `area_administrative_id`, `student_id`, `academic_period_id`, `education_grade_id`, `absence_type_id`, `student_absence_reason_id`, `student_status_id`)");

      $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
      VALUES ("summary_institution_student_absences_truncate","TRUNCATE summary_institution_student_absences;","day", 1, 1, NOW())');

      $this->execute('INSERT INTO report_queries (`name`, `query_sql`, `frequency`, `status`, `created_user_id`, `created`) 
          VALUES ("summary_institution_student_absences_insert", "INSERT INTO summary_institution_student_absences SELECT institution_students.institution_id, institutions.code institution_code, institutions.name institution_name, areas.id area_id, areas.code area_code, areas.name area_name, area_administratives.id area_administrative_id, area_administratives.code area_administrative_code, area_administratives.name area_administrative_name, institution_students.student_id, security_users.openemis_no, get_student_default_identity.number default_identity_number, IF(security_users.middle_name IS NOT NULL AND security_users.third_name IS NOT NULL, CONCAT(security_users.first_name," ",security_users.middle_name," ",security_users.third_name," ",security_users.last_name), IF(security_users.middle_name IS NULL AND security_users.third_name IS NULL, CONCAT(security_users.first_name," ",security_users.last_name), IF(security_users.middle_name IS NULL AND security_users.third_name IS NOT NULL, CONCAT(security_users.first_name," ",security_users.third_name," ",security_users.last_name), CONCAT(security_users.first_name," ",security_users.middle_name," ",security_users.last_name)))) student_name, institution_students.start_date enrol_start_date, institution_students.end_date enrol_end_date, institution_students.academic_period_id, academic_periods.code academic_period_code, academic_periods.name academic_period_name, institution_student_absence_details.education_grade_id, education_grades.code education_grade_code, education_grades.name education_grade_name, institution_student_absence_details.date absent_date, institution_student_absence_days.absent_days, IF(institution_student_absence_details.subject_id = 0,IF(get_period_data.period_name IS NULL, `Period 1`, get_period_data.period_name),institution_subjects.name) absence_subject_period, institution_student_absence_details.absence_type_id, absence_types.name absence_type, institution_student_absence_details.student_absence_reason_id, student_absence_reasons.name student_absence_reasons, institution_students.student_status_id, student_statuses.name student_status, NOW() created FROM institution_students INNER JOIN student_statuses ON student_statuses.id = institution_students.student_status_id INNER JOIN institution_student_absence_details ON institution_students.student_id = institution_student_absence_details.student_id AND institution_students.institution_id = institution_student_absence_details.institution_id AND institution_students.education_grade_id = institution_student_absence_details.education_grade_id AND institution_students.academic_period_id = institution_student_absence_details.academic_period_id AND institution_student_absence_details.date BETWEEN institution_students.start_date AND institution_students.end_date INNER JOIN institution_student_absence_days ON institution_student_absence_days.student_id = institution_student_absence_details.student_id AND institution_student_absence_days.institution_id = institution_student_absence_details.institution_id AND institution_student_absence_details.date BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date INNER JOIN academic_periods ON academic_periods.id = institution_student_absence_details.academic_period_id INNER JOIN institutions ON institutions.id = institution_students.institution_id INNER JOIN security_users ON security_users.id = institution_students.student_id INNER JOIN education_grades ON education_grades.id = institution_students.education_grade_id INNER JOIN absence_types ON absence_types.id = institution_student_absence_details.absence_type_id LEFT JOIN student_absence_reasons ON student_absence_reasons.id = institution_student_absence_details.student_absence_reason_id INNER JOIN areas ON areas.id = institutions.area_id LEFT JOIN area_administratives ON area_administratives.id = institutions.area_administrative_id LEFT JOIN(SELECT student_mark_type_status_grades.education_grade_id, student_mark_type_statuses.academic_period_id, student_mark_type_statuses.date_enabled, student_mark_type_statuses.date_disabled, period period_number, student_attendance_per_day_periods.name period_name FROM student_attendance_mark_types INNER JOIN student_attendance_per_day_periods ON student_attendance_per_day_periods.student_attendance_mark_type_id = student_attendance_mark_types.id INNER JOIN student_mark_type_statuses ON student_mark_type_statuses.student_attendance_mark_type_id = student_attendance_mark_types.id INNER JOIN student_mark_type_status_grades ON student_mark_type_status_grades.student_mark_type_status_id = student_mark_type_statuses.id) get_period_data ON get_period_data.academic_period_id = institution_student_absence_details.academic_period_id AND get_period_data.education_grade_id = institution_student_absence_details.education_grade_id AND institution_student_absence_details.date BETWEEN get_period_data.date_enabled AND get_period_data.date_disabled LEFT JOIN institution_subjects ON institution_subjects.id = institution_student_absence_details.subject_id LEFT JOIN (SELECT user_identities.number, user_identities.security_user_id FROM user_identities INNER JOIN identity_types ON identity_types.id = user_identities.identity_type_id WHERE identity_types.default = 1) get_student_default_identity ON get_student_default_identity.security_user_id = institution_student_absence_details.student_id ORDER BY institution_student_absence_details.student_id ASC, institution_students.academic_period_id ASC","day", 1, 1, NOW())');

    }
    //rollback
    public function down()
    {
        /** Delete OpenEMIS Core report_assessment_missing_mark_entry table */
        $this->execute('DROP TABLE IF EXISTS `summary_institution_student_absences`');
        
        /** Delete OpenEMIS Core report_assessment_missing_mark_entry row in report_queries table */
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "summary_institution_student_absences_truncate"'); 
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "summary_institution_student_absences_insert"');

    }
}