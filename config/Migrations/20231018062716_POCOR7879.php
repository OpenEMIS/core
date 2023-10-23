<?php

use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;

class POCOR7879 extends AbstractMigration
{
    public function up()
    {
        /*backup report_queries table */
        try {
            $this->execute('CREATE TABLE IF NOT EXISTS `z_7879_report_queries` LIKE `report_queries`');
        } catch (\Exception $exception) {

        }
        try {
            $this->execute('INSERT INTO `z_7879_report_queries` SELECT * FROM `report_queries`');
        } catch (\Exception $exception) {

        }
        try {
            /*delete existing report_student_attendance_summary entries in report_queries table */
            $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_student_attendance_summary_truncate"');
        } catch (\Exception $exception) {

        }
        try {
            /*delete existing report_student_attendance_summary entries in report_queries table */
            $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_student_attendance_summary_insert"');
        } catch (\Exception $exception) {

        }
        try {
            /*backup report_student_attendance_summary table. As it has no indexes, first rename then create an empty table */
            $this->execute('RENAME TABLE `report_student_attendance_summary` TO `z_7879_report_student_attendance_summary`');
        } catch (\Exception $exception) {

        }
        try {
            $this->execute('CREATE TABLE IF NOT EXISTS `report_student_attendance_summary` LIKE `z_7879_report_student_attendance_summary`');
        } catch (\Exception $exception) {

        }

        $make_indexes_sql_1 = "create index report_student_attendance_summary_academic_period_id_index
 on openemis_core.report_student_attendance_summary (academic_period_id)";

        $make_indexes_sql_2 = "create index report_student_attendance_summary_institution_id_index
 on openemis_core.report_student_attendance_summary (institution_id)";

        $make_indexes_sql_3 = "create index report_student_attendance_summary_class_id_index
 on openemis_core.report_student_attendance_summary (class_id)";

        $make_indexes_sql_4 = "create index report_student_attendance_summary_education_grade_id_index
 on openemis_core.report_student_attendance_summary (education_grade_id)";

        $make_indexes_sql_5 = "create index report_student_attendance_summary_period_id_index
 on openemis_core.report_student_attendance_summary (period_id)";

        $make_indexes_sql_6 = "create index report_student_attendance_summary_subject_id_index
 on openemis_core.report_student_attendance_summary (subject_id)";

        $make_indexes_sql_7 = "create index report_student_attendance_summary_date_index
 on openemis_core.report_student_attendance_summary (attendance_date)";

        $make_indexes_sql_8 = "create index report_student_attendance_summary_created_index
 on openemis_core.report_student_attendance_summary (created)";

        $make_indexes_sql_9 = "CREATE UNIQUE INDEX report_student_attendance_summary_uindex
 ON report_student_attendance_summary (academic_period_id, 
 attendance_date,
 institution_id, 
 class_id, 
 education_grade_id, 
 period_id,
 subject_id)";

        $make_indexes_sql_10 = "CREATE UNIQUE INDEX report_student_attendance_summary_gindex
 ON report_student_attendance_summary ( 
 attendance_date,
 class_id, 
 period_id,
 subject_id)";

        $truncate_sql = "TRUNCATE report_student_attendance_summary;";

        $insert_sql = "INSERT IGNORE INTO report_student_attendance_summary (
 `academic_period_id`,
 `academic_period_name`,
 `institution_id`,
 `institution_code`,
 `institution_name`, 
 `education_grade_id`,
 `education_grade_code`,
 `education_grade_name`,
 `class_id`,
 `class_name`,
 `attendance_date`,
 `period_id`,
 `period_name`,
 `subject_id`,
 `subject_name`,
 `female_count`,
 `male_count`,
 `total_count`,
 `marked_attendance`,
 `unmarked_attendance`,
 `present_female_count`,
 `present_male_count`,
 `present_total_count`,
 `absent_female_count`,
 `absent_male_count`,
 `absent_total_count`,
 `late_female_count`,
 `late_male_count`,
 `late_total_count`,
 `created` )
SELECT total_students_data.academic_period_id,
 total_students_data.academic_period_name,
 total_students_data.institution_id,
 total_students_data.institution_code,
 total_students_data.institution_name,
 total_students_data.education_grade_id,
 total_students_data.education_grade_code,
 total_students_data.education_grade_name,
 total_students_data.institution_class_id,
 total_students_data.institution_class_name,
 all_dates.selected_date,
 IFNULL(count_periods.period_id, 1) period_id,
 IFNULL(count_periods.period_name, 'Unmarked Period') period_name,
 IFNULL(subjects_info.institution_subject_id, 0) institution_subject_id,
 IFNULL(subjects_info.institution_subject_name, 'Unmarked Subject') institution_subject_name,
 total_students_data.female_students,
 total_students_data.male_students,
 total_students_data.total_students,
 IF(attendance_data.academic_period_id IS NOT NULL, total_students_data.total_students, 0) marked_attendance,
 IF(attendance_data.academic_period_id IS NULL, total_students_data.total_students, 0) unmarked_attendance,
 IF(attendance_data.academic_period_id IS NULL, 0,
 total_students_data.female_students - IFNULL(absence_data.total_absent_female, 0) -
 IFNULL(absence_data.total_late_female, 0)) female_present_students,
 IF(attendance_data.academic_period_id IS NULL, 0,
 total_students_data.male_students - IFNULL(absence_data.total_absent_male, 0) -
 IFNULL(absence_data.total_late_male, 0)) male_present_students,
 IF(attendance_data.academic_period_id IS NULL, 0,
 total_students_data.total_students - IFNULL(absence_data.total_absent, 0) -
 IFNULL(absence_data.total_late, 0)) total_present_students,
 IFNULL(absence_data.total_absent_female, 0) total_absent_female,
 IFNULL(absence_data.total_absent_male, 0) total_absent_male,
 IFNULL(absence_data.total_absent, 0) total_absent,
 IFNULL(absence_data.total_late_female, 0) total_late_female,
 IFNULL(absence_data.total_late_male, 0) total_late_male,
 IFNULL(absence_data.total_late, 0) total_late,
 CURRENT_TIMESTAMP created
FROM (SELECT academic_periods.id academic_period_id,
 academic_periods.name academic_period_name,
 institutions.id institution_id,
 institutions.code institution_code,
 institutions.name institution_name,
 education_grades.id education_grade_id,
 education_grades.code education_grade_code,
 education_grades.name education_grade_name,
 institution_classes.id institution_class_id,
 institution_classes.name institution_class_name,
 SUM(CASE WHEN security_users.gender_id IN (1, 2) THEN 1 ELSE 0 END) total_students,
 SUM(CASE WHEN security_users.gender_id = 2 THEN 1 ELSE 0 END) female_students,
 SUM(CASE WHEN security_users.gender_id = 1 THEN 1 ELSE 0 END) male_students,
 academic_periods.start_date,
 academic_periods.end_date
 FROM institution_class_students
 INNER JOIN security_users ON security_users.id = institution_class_students.student_id
 INNER JOIN institutions ON institutions.id = institution_class_students.institution_id
 INNER JOIN education_grades ON education_grades.id = institution_class_students.education_grade_id
 INNER JOIN institution_classes
 ON institution_classes.id = institution_class_students.institution_class_id AND
 institution_classes.institution_id = institution_class_students.institution_id AND
 institution_classes.academic_period_id = institution_class_students.academic_period_id
 INNER JOIN academic_periods ON academic_periods.id = institution_class_students.academic_period_id
 WHERE IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date),
 institution_class_students.student_status_id = 1,
 institution_class_students.student_status_id IN (1, 7, 6, 8))
 GROUP BY institution_classes.id) total_students_data
 INNER JOIN (WITH RECURSIVE Numbers AS (
 SELECT 0 AS i
 UNION ALL
 SELECT i + 1
 FROM Numbers
 WHERE i < 9
)
 SELECT date_generator.selected_date
 FROM (
 SELECT adddate('1993-01-01',
 Numbers_t4.i * 10000 + Numbers_t3.i * 1000 + Numbers_t2.i * 100 +
 Numbers_t1.i * 10 + Numbers_t0.i) AS selected_date
 FROM Numbers Numbers_t0
 CROSS JOIN Numbers Numbers_t1
 CROSS JOIN Numbers Numbers_t2
 CROSS JOIN Numbers Numbers_t3
 CROSS JOIN Numbers Numbers_t4
 ) date_generator
 INNER JOIN (SELECT MIN(academic_periods.start_date) min_date, CURDATE() max_date
 FROM institution_students
 INNER JOIN academic_periods
 ON academic_periods.id = institution_students.academic_period_id) date_ranges
 WHERE selected_date BETWEEN date_ranges.min_date AND date_ranges.max_date) all_dates
 ON all_dates.selected_date BETWEEN total_students_data.start_date AND total_students_data.end_date
 LEFT JOIN (SELECT student_attendance_marked_records.academic_period_id,
 student_attendance_marked_records.institution_id,
 student_attendance_marked_records.education_grade_id,
 student_attendance_marked_records.institution_class_id,
 student_attendance_marked_records.date,
 student_attendance_marked_records.period periods_presence_marked_id,
 student_attendance_marked_records.subject_id subjects_presence_marked_id
 FROM student_attendance_marked_records
 GROUP BY student_attendance_marked_records.academic_period_id,
 student_attendance_marked_records.institution_id,
 student_attendance_marked_records.education_grade_id,
 student_attendance_marked_records.institution_class_id,
 student_attendance_marked_records.date, 
 student_attendance_marked_records.period,
 student_attendance_marked_records.subject_id) attendance_data
 ON attendance_data.academic_period_id = total_students_data.academic_period_id AND
 attendance_data.institution_id = total_students_data.institution_id AND
 attendance_data.education_grade_id = total_students_data.education_grade_id AND
 attendance_data.institution_class_id = total_students_data.institution_class_id AND
 attendance_data.date = all_dates.selected_date
 LEFT JOIN (SELECT student_attendance_per_day_periods.id period_id,
 student_attendance_per_day_periods.name period_name
 from student_attendance_per_day_periods) count_periods
 ON count_periods.period_id = attendance_data.periods_presence_marked_id
 LEFT JOIN (SELECT institution_subjects.id institution_subject_id,
 institution_subjects.name institution_subject_name
 FROM institution_subjects) subjects_info
 ON subjects_info.institution_subject_id = attendance_data.subjects_presence_marked_id
 LEFT JOIN (SELECT institution_student_absence_details.academic_period_id,
 institution_student_absence_details.institution_id,
 institution_student_absence_details.education_grade_id,
 institution_student_absence_details.institution_class_id,
 institution_student_absence_details.date,
 institution_student_absence_details.period periods_absence_marked_id,
 institution_student_absence_details.subject_id subjects_absence_marked_id,
 SUM(CASE
 WHEN security_users.gender_id IN (1, 2) AND
 institution_student_absence_details.absence_type_id IN (1, 2) THEN 1
 ELSE 0 END) total_absent,
 SUM(CASE
 WHEN security_users.gender_id = 2 AND
 institution_student_absence_details.absence_type_id IN (1, 2) THEN 1
 ELSE 0 END) total_absent_female,
 SUM(CASE
 WHEN security_users.gender_id = 1 AND
 institution_student_absence_details.absence_type_id IN (1, 2) THEN 1
 ELSE 0 END) total_absent_male,
 SUM(CASE
 WHEN security_users.gender_id IN (1, 2) AND
 institution_student_absence_details.absence_type_id = 3 THEN 1
 ELSE 0 END) total_late,
 SUM(CASE
 WHEN security_users.gender_id = 2 AND
 institution_student_absence_details.absence_type_id = 3 THEN 1
 ELSE 0 END) total_late_female,
 SUM(CASE
 WHEN security_users.gender_id = 1 AND
 institution_student_absence_details.absence_type_id = 3 THEN 1
 ELSE 0 END) total_late_male
 FROM institution_student_absence_details
 INNER JOIN institution_class_students ON institution_student_absence_details.student_id =
 institution_class_students.student_id AND
 institution_student_absence_details.institution_class_id =
 institution_class_students.institution_class_id
 INNER JOIN security_users
 ON security_users.id = institution_student_absence_details.student_id
 GROUP BY institution_student_absence_details.academic_period_id,
 institution_student_absence_details.institution_id,
 institution_student_absence_details.education_grade_id,
 institution_student_absence_details.institution_class_id,
 institution_student_absence_details.date,
 institution_student_absence_details.period,
 institution_student_absence_details.subject_id) absence_data
 ON absence_data.academic_period_id = total_students_data.academic_period_id AND
 absence_data.institution_id = total_students_data.institution_id AND
 absence_data.education_grade_id = total_students_data.education_grade_id AND
 absence_data.institution_class_id = total_students_data.institution_class_id AND
 absence_data.date = all_dates.selected_date AND
 absence_data.periods_absence_marked_id = attendance_data.periods_presence_marked_id AND
 absence_data.subjects_absence_marked_id = attendance_data.subjects_presence_marked_id;
 ";
        try {
            $this->execute($truncate_sql);
        } catch (\Exception $exception) {

        }
        try {
            $this->execute($make_indexes_sql_1);
        } catch (\Exception $exception) {

        }
        try {
            $this->execute($make_indexes_sql_2);
        } catch (\Exception $exception) {

        }
        try {
            $this->execute($make_indexes_sql_3);
        } catch (\Exception $exception) {

        }
        try {
            $this->execute($make_indexes_sql_4);
        } catch (\Exception $exception) {

        }
        try {
            $this->execute($make_indexes_sql_5);
        } catch (\Exception $exception) {

        }
        try {
            $this->execute($make_indexes_sql_6);
        } catch (\Exception $exception) {

        }
        try {
            $this->execute($make_indexes_sql_7);
        } catch (\Exception $exception) {

        }
        try {
            $this->execute($make_indexes_sql_8);
        } catch (\Exception $exception) {

        }
        try {
            $this->execute($make_indexes_sql_9);
        } catch (\Exception $exception) {

        }
        try {
            $this->execute($make_indexes_sql_10);
        } catch (\Exception $exception) {

        }
        try {
            $this->execute($insert_sql);
        } catch (\Exception $exception) {

        }

        try {
            /*create necessary entries */
            $ReportQueries = TableRegistry::get('report_queries');
            $data = [
                'name' => 'report_student_attendance_summary_truncate',
                'query_sql' => $truncate_sql,
                'frequency' => 'week',
                'status' => 1,
                'created_user_id' => 1,
                'created' => Time::now()
            ];
            $entity = $ReportQueries->newEntity($data);
            $result = $ReportQueries->save($entity);
        } catch (\Exception $exception) {

        }
        try {

            $data = [
                'name' => 'report_student_attendance_summary_insert',
                'query_sql' => $insert_sql,
                'frequency' => 'day',
                'status' => 1,
                'created_user_id' => 1,
                'created' => Time::now()
            ];
            $entity = $ReportQueries->newEntity($data);
            $result = $ReportQueries->save($entity);
        } catch (\Exception $exception) {

        }
    }

    //rollback
    public function down()
    {
        /* Restore backup tables */
        try {
            $this->execute('DROP TABLE IF EXISTS `report_queries`');
        } catch (\Exception $exception) {

        }
        try {
            $this->execute('RENAME TABLE `z_7879_report_queries` TO `report_queries`');
        } catch (\Exception $exception) {

        }
        try {
            $this->execute('DROP TABLE IF EXISTS `report_student_attendance_summary`');
        } catch (\Exception $exception) {

        }
        try {
            $this->execute('RENAME TABLE `z_7879_report_student_attendance_summary` TO `report_student_attendance_summary`');
        } catch (\Exception $exception) {

        }
    }

}
