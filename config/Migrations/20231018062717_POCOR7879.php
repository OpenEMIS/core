<?php

use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;

class POCOR7879 extends AbstractMigration
{
    public function up()
    {
        /*backup report_queries table */
        $this->execute('CREATE TABLE IF NOT EXISTS `z_7879_report_queries` LIKE `report_queries`');
        $this->execute('INSERT INTO `z_7879_report_queries` SELECT * FROM `report_queries`');
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_student_attendance_summary_truncate"');
        $this->execute('DELETE FROM report_queries WHERE report_queries.name = "report_student_attendance_summary_insert"');
        $this->execute('RENAME TABLE `report_student_attendance_summary` TO `z_7879_report_student_attendance_summary`');
        $this->execute('CREATE TABLE IF NOT EXISTS `report_student_attendance_summary` LIKE `z_7879_report_student_attendance_summary`');

//        $make_indexes_sql_1 = "create index report_student_attendance_summary_academic_period_id_index
// on openemis_core.report_student_attendance_summary (academic_period_id)";
//
//        $make_indexes_sql_2 = "create index report_student_attendance_summary_institution_id_index
// on openemis_core.report_student_attendance_summary (institution_id)";
//
//        $make_indexes_sql_3 = "create index report_student_attendance_summary_class_id_index
// on openemis_core.report_student_attendance_summary (class_id)";
//
//        $make_indexes_sql_4 = "create index report_student_attendance_summary_education_grade_id_index
// on openemis_core.report_student_attendance_summary (education_grade_id)";
//
//        $make_indexes_sql_5 = "create index report_student_attendance_summary_period_id_index
// on openemis_core.report_student_attendance_summary (period_id)";
//
//        $make_indexes_sql_6 = "create index report_student_attendance_summary_subject_id_index
// on openemis_core.report_student_attendance_summary (subject_id)";
//
//        $make_indexes_sql_7 = "create index report_student_attendance_summary_date_index
// on openemis_core.report_student_attendance_summary (attendance_date)";
//
//        $make_indexes_sql_8 = "create index report_student_attendance_summary_created_index
// on openemis_core.report_student_attendance_summary (created)";
//
//        $make_indexes_sql_9 = "CREATE UNIQUE INDEX report_student_attendance_summary_uindex
// ON report_student_attendance_summary (academic_period_id,
// attendance_date,
// institution_id,
// class_id,
// education_grade_id,
// period_id,
// subject_id)";
//
//        $make_indexes_sql_10 = "CREATE UNIQUE INDEX report_student_attendance_summary_gindex
// ON report_student_attendance_summary (
// attendance_date,
// class_id,
// period_id,
// subject_id)";

        $truncate_sql = "TRUNCATE report_student_attendance_summary;";
        $fix_missing_sql = "INSERT IGNORE INTO `student_attendance_marked_records` (`institution_id`, `academic_period_id`, `institution_class_id`, `education_grade_id`, `date`, `period`, `subject_id`, `no_scheduled_class`)
SELECT absence_marked.institution_id
    ,absence_marked.academic_period_id
    ,absence_marked.institution_class_id
    ,absence_marked.education_grade_id
    ,absence_marked.date
    ,absence_marked.period
    ,absence_marked.subject_id
    ,'0' no_scheduled_class
FROM
(
    SELECT institution_student_absence_details.institution_id
        ,institution_student_absence_details.academic_period_id
        ,institution_student_absence_details.institution_class_id
        ,institution_student_absence_details.education_grade_id
        ,institution_student_absence_details.date
        ,institution_student_absence_details.period
        ,institution_student_absence_details.subject_id
    FROM institution_student_absence_details
    GROUP BY institution_student_absence_details.institution_id
        ,institution_student_absence_details.academic_period_id
        ,institution_student_absence_details.institution_class_id
        ,institution_student_absence_details.education_grade_id
        ,institution_student_absence_details.date
        ,institution_student_absence_details.period
        ,institution_student_absence_details.subject_id
) absence_marked
LEFT JOIN
(
    SELECT student_attendance_marked_records.institution_id
        ,student_attendance_marked_records.academic_period_id
        ,student_attendance_marked_records.institution_class_id
        ,student_attendance_marked_records.education_grade_id
        ,student_attendance_marked_records.date
        ,student_attendance_marked_records.period
        ,student_attendance_marked_records.subject_id
    FROM student_attendance_marked_records
    GROUP BY student_attendance_marked_records.institution_id
        ,student_attendance_marked_records.academic_period_id
        ,student_attendance_marked_records.institution_class_id
        ,student_attendance_marked_records.education_grade_id
        ,student_attendance_marked_records.date
        ,student_attendance_marked_records.period
        ,student_attendance_marked_records.subject_id
) attendance_marked
ON attendance_marked.institution_id = absence_marked.institution_id
AND attendance_marked.academic_period_id = absence_marked.academic_period_id
AND attendance_marked.institution_class_id = absence_marked.institution_class_id
AND attendance_marked.education_grade_id = absence_marked.education_grade_id
AND attendance_marked.date = absence_marked.date
AND attendance_marked.period = absence_marked.period
AND attendance_marked.subject_id = absence_marked.subject_id
WHERE attendance_marked.institution_id IS NULL";

            $insert_sql = "INSERT IGNORE INTO report_student_attendance_summary SELECT main_query.academic_period_id ,main_query.academic_period_name ,main_query.institution_id ,main_query.institution_code ,main_query.institution_name ,main_query.education_grade_id ,main_query.education_grade_code ,main_query.education_grade_name ,main_query.institution_class_id ,main_query.institution_class_name ,dates_generator.date_info attendance_date ,attendance_marking_type.period_id ,attendance_marking_type.period_name ,attendance_marking_type.subject_id ,attendance_marking_type.subject_name ,main_query.total_female_count female_count ,main_query.total_male_count male_count ,main_query.total_male_count + main_query.total_female_count total_count ,CASE WHEN IF(marked_attendance.institution_id IS NOT NULL, IFNULL(marked_attendance.present_female_count, 0) + IFNULL(marked_attendance.present_male_count, 0), 0) < 0 THEN 0 ELSE IF(marked_attendance.institution_id IS NOT NULL, IFNULL(marked_attendance.present_female_count, 0) + IFNULL(marked_attendance.present_male_count, 0), 0) END marked_attendance ,IF(marked_attendance.institution_id IS NULL, main_query.total_male_count + main_query.total_female_count, 0) unmarked_attendance ,CASE WHEN IFNULL(marked_attendance.present_female_count, 0) - IFNULL(marked_absence.absent_female_count, 0) < 0 THEN 0 ELSE IFNULL(marked_attendance.present_female_count, 0) - IFNULL(marked_absence.absent_female_count, 0) END present_female_count ,CASE WHEN IFNULL(marked_attendance.present_male_count, 0) - IFNULL(marked_absence.absent_male_count, 0) < 0 THEN 0 ELSE IFNULL(marked_attendance.present_male_count, 0) - IFNULL(marked_absence.absent_male_count, 0) END present_male_count ,CASE WHEN IFNULL(marked_attendance.present_female_count, 0) + IFNULL(marked_attendance.present_male_count, 0) - (IFNULL(marked_absence.absent_female_count, 0) + IFNULL(marked_absence.absent_male_count, 0)) < 0 THEN 0 ELSE IFNULL(marked_attendance.present_female_count, 0) + IFNULL(marked_attendance.present_male_count, 0) - (IFNULL(marked_absence.absent_female_count, 0) + IFNULL(marked_absence.absent_male_count, 0)) END present_total_count ,CASE WHEN IFNULL(marked_absence.absent_female_count, 0) < 0 THEN 0 ELSE IFNULL(marked_absence.absent_female_count, 0) END absent_female_count ,CASE WHEN IFNULL(marked_absence.absent_male_count, 0) < 0 THEN 0 ELSE IFNULL(marked_absence.absent_male_count, 0) END absent_male_count ,CASE WHEN IFNULL(marked_absence.absent_female_count, 0) + IFNULL(marked_absence.absent_male_count, 0) < 0 THEN 0 ELSE IFNULL(marked_absence.absent_female_count, 0) + IFNULL(marked_absence.absent_male_count, 0) END absent_total_count ,CASE WHEN IFNULL(marked_absence.late_female_count, 0) < 0 THEN 0 ELSE IFNULL(marked_absence.late_female_count, 0) END late_female_count ,CASE WHEN IFNULL(marked_absence.late_male_count, 0) < 0 THEN 0 ELSE IFNULL(marked_absence.late_male_count, 0) END late_male_count ,CASE WHEN IFNULL(marked_absence.late_female_count, 0) + IFNULL(marked_absence.late_male_count, 0) < 0 THEN 0 ELSE IFNULL(marked_absence.late_female_count, 0) + IFNULL(marked_absence.late_male_count, 0) END late_total_count ,CURRENT_TIMESTAMP FROM ( SELECT institution_class_students.academic_period_id ,academic_periods.name academic_period_name ,academic_periods.start_date ,academic_periods.end_date ,institution_class_students.institution_id ,institutions.code institution_code ,institutions.name institution_name ,institution_class_students.education_grade_id ,education_grades.code education_grade_code ,education_grades.name education_grade_name ,institution_class_students.institution_class_id ,institution_classes.name institution_class_name ,COUNT(DISTINCT(CASE WHEN security_users.gender_id = 1 THEN institution_class_students.student_id END)) total_male_count ,COUNT(DISTINCT(CASE WHEN security_users.gender_id = 2 THEN institution_class_students.student_id END)) total_female_count FROM institution_class_students INNER JOIN security_users ON security_users.id = institution_class_students.student_id INNER JOIN institution_classes ON institution_classes.id = institution_class_students.institution_class_id INNER JOIN education_grades ON education_grades.id = institution_class_students.education_grade_id INNER JOIN institutions ON institutions.id = institution_class_students.institution_id INNER JOIN academic_periods ON academic_periods.id = institution_class_students.academic_period_id WHERE IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_class_students.student_status_id = 1, institution_class_students.student_status_id IN (1, 7, 6, 8)) GROUP BY institution_class_students.education_grade_id ,institution_class_students.institution_id ,institution_class_students.academic_period_id ,institution_class_students.institution_class_id ) main_query INNER JOIN ( SELECT institutions.id institution_id ,month_generator.date_info ,month_generator.start_date ,YEAR(month_generator.date_info) year_info FROM institutions INNER JOIN ( SELECT YEAR(m1) AS year_name ,MONTH(m1) AS month_id ,MONTHNAME(m1) AS month_name ,generated_date AS date_info ,start_date FROM ( SELECT dates_boundaries.start_date ,(dates_boundaries.start_date - INTERVAL DAYOFMONTH(dates_boundaries.start_date)-1 DAY) +INTERVAL m MONTH AS m1 ,dates_boundaries.end_date ,DATE((dates_boundaries.start_date - INTERVAL DAYOFMONTH(dates_boundaries.start_date)-1 DAY) +INTERVAL m MONTH + INTERVAL days.day DAY) AS generated_date FROM ( SELECT MIN(student_attendance_marked_records.date) start_date ,CURRENT_DATE end_date FROM student_attendance_marked_records ) dates_boundaries CROSS JOIN ( SELECT @rownum:= @rownum+1 AS m FROM ( SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 ) t1, ( SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 ) t2, ( SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 ) t3, ( SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 ) t4, ( SELECT @rownum:= -1 ) t0 ) d1 CROSS JOIN ( SELECT 0 AS day UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29 UNION ALL SELECT 30 ) AS days CROSS JOIN ( SELECT MAX(CASE WHEN config_items.code = 'first_day_of_week' THEN IF(LENGTH(config_items.value) = 0, config_items.default_value, config_items.value) END) first_day_of_week ,MAX(CASE WHEN config_items.code = 'days_per_week' THEN IF(LENGTH(config_items.value) = 0, config_items.default_value, config_items.value) END) days_per_week FROM config_items ) working_days LEFT JOIN ( SELECT calendar_event_dates.date public_hol FROM calendar_event_dates INNER JOIN calendar_events ON calendar_events.id = calendar_event_dates.calendar_event_id WHERE calendar_events.institution_id = -1 GROUP BY calendar_event_dates.date ) public_hol_info ON public_hol_info.public_hol = DATE((dates_boundaries.start_date - INTERVAL DAYOFMONTH(dates_boundaries.start_date)-1 DAY) +INTERVAL m MONTH + INTERVAL days.day DAY) WHERE DATE((dates_boundaries.start_date - INTERVAL DAYOFMONTH(dates_boundaries.start_date)-1 DAY) +INTERVAL m MONTH + INTERVAL days.day DAY) <= dates_boundaries.end_date AND MONTH(DATE((dates_boundaries.start_date - INTERVAL DAYOFMONTH(dates_boundaries.start_date)-1 DAY) +INTERVAL m MONTH + INTERVAL days.day DAY)) = MONTH((dates_boundaries.start_date - INTERVAL DAYOFMONTH(dates_boundaries.start_date)-1 DAY) +INTERVAL m MONTH) AND YEAR(DATE((dates_boundaries.start_date - INTERVAL DAYOFMONTH(dates_boundaries.start_date)-1 DAY) +INTERVAL m MONTH + INTERVAL days.day DAY)) = YEAR((dates_boundaries.start_date - INTERVAL DAYOFMONTH(dates_boundaries.start_date)-1 DAY) +INTERVAL m MONTH) AND DAYOFWEEK(DATE((dates_boundaries.start_date - INTERVAL DAYOFMONTH(dates_boundaries.start_date)-1 DAY) +INTERVAL m MONTH + INTERVAL days.day DAY)) BETWEEN working_days.first_day_of_week + 1 AND working_days.first_day_of_week + days_per_week AND public_hol_info.public_hol IS NULL ) d2 WHERE d2.m1 <= d2.end_date ) month_generator LEFT JOIN ( SELECT calendar_events.id calendar_event_id ,calendar_events.institution_id ,calendar_event_dates.date date_info FROM calendar_event_dates INNER JOIN calendar_events ON calendar_events.id = calendar_event_dates.calendar_event_id INNER JOIN calendar_types ON calendar_types.id = calendar_events.calendar_type_id WHERE calendar_events.institution_id != -1 AND calendar_types.is_attendance_required = 0 GROUP BY calendar_events.institution_id ,calendar_event_dates.date ) private_holidays ON private_holidays.institution_id = institutions.id AND private_holidays.date_info = month_generator.date_info WHERE private_holidays.calendar_event_id IS NULL GROUP BY month_generator.date_info ,institutions.id ) dates_generator ON dates_generator.institution_id = main_query.institution_id AND dates_generator.date_info BETWEEN main_query.start_date AND main_query.end_date INNER JOIN ( SELECT student_mark_type_status_grades.education_grade_id ,student_mark_type_statuses.academic_period_id ,student_attendance_mark_types.attendance_per_day ,IFNULL(student_attendance_per_day_periods.period, 1) period_id ,IFNULL(student_attendance_per_day_periods.name, '') period_name ,IFNULL(subjects_info.education_subject_id, 0) subject_id ,IFNULL(subjects_info.education_subject_name, '') subject_name FROM student_mark_type_status_grades INNER JOIN student_mark_type_statuses ON student_mark_type_statuses.id = student_mark_type_status_grades.student_mark_type_status_id INNER JOIN student_attendance_mark_types ON student_attendance_mark_types.id = student_mark_type_statuses.student_attendance_mark_type_id LEFT JOIN student_attendance_per_day_periods ON student_attendance_per_day_periods.student_attendance_mark_type_id = student_attendance_mark_types.id LEFT JOIN ( SELECT education_systems.academic_period_id ,education_grades_subjects.education_grade_id ,education_grades_subjects.education_subject_id ,education_subjects.name education_subject_name FROM education_grades_subjects INNER JOIN education_subjects ON education_subjects.id = education_grades_subjects.education_subject_id INNER JOIN education_grades ON education_grades.id = education_grades_subjects.education_grade_id INNER JOIN education_programmes ON education_programmes.id = education_grades.education_programme_id INNER JOIN education_cycles ON education_cycles.id = education_programmes.education_cycle_id INNER JOIN education_levels ON education_levels.id = education_cycles.education_level_id INNER JOIN education_systems ON education_systems.id = education_levels.education_system_id ) subjects_info ON subjects_info.academic_period_id = student_mark_type_statuses.academic_period_id AND subjects_info.education_grade_id = student_mark_type_status_grades.education_grade_id AND student_attendance_mark_types.attendance_per_day = 0 ) attendance_marking_type ON attendance_marking_type.academic_period_id = main_query.academic_period_id AND attendance_marking_type.education_grade_id = main_query.education_grade_id LEFT JOIN ( SELECT attendance_details.institution_id ,attendance_details.academic_period_id ,attendance_details.institution_class_id ,attendance_details.education_grade_id ,attendance_details.date ,attendance_details.period ,attendance_details.subject_id ,class_counter.present_male_count ,class_counter.present_female_count FROM ( SELECT student_attendance_marked_records.institution_id ,student_attendance_marked_records.academic_period_id ,student_attendance_marked_records.institution_class_id ,student_attendance_marked_records.education_grade_id ,student_attendance_marked_records.date ,student_attendance_marked_records.period ,IFNULL(education_subjects.id, 0) subject_id FROM student_attendance_marked_records LEFT JOIN institution_subjects ON institution_subjects.id = student_attendance_marked_records.subject_id LEFT JOIN education_subjects ON education_subjects.id = institution_subjects.education_subject_id WHERE student_attendance_marked_records.no_scheduled_class = 0 ) attendance_details INNER JOIN ( SELECT institution_class_students.education_grade_id ,institution_class_students.institution_id ,institution_class_students.academic_period_id ,institution_class_students.institution_class_id ,COUNT(DISTINCT(CASE WHEN security_users.gender_id = 1 THEN institution_class_students.student_id END)) present_male_count ,COUNT(DISTINCT(CASE WHEN security_users.gender_id = 2 THEN institution_class_students.student_id END)) present_female_count FROM institution_class_students INNER JOIN security_users ON security_users.id = institution_class_students.student_id INNER JOIN academic_periods ON academic_periods.id = institution_class_students.academic_period_id WHERE IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_class_students.student_status_id = 1, institution_class_students.student_status_id IN (1, 7, 6, 8)) GROUP BY institution_class_students.education_grade_id ,institution_class_students.institution_id ,institution_class_students.academic_period_id ,institution_class_students.institution_class_id ) class_counter ON class_counter.education_grade_id = attendance_details.education_grade_id AND class_counter.institution_id = attendance_details.institution_id AND class_counter.institution_class_id = attendance_details.institution_class_id AND class_counter.academic_period_id = attendance_details.academic_period_id ) marked_attendance ON marked_attendance.institution_id = main_query.institution_id AND marked_attendance.academic_period_id = main_query.academic_period_id AND marked_attendance.institution_class_id = main_query.institution_class_id AND marked_attendance.education_grade_id = main_query.education_grade_id AND marked_attendance.date = dates_generator.date_info AND marked_attendance.period = attendance_marking_type.period_id AND marked_attendance.subject_id = attendance_marking_type.subject_id LEFT JOIN ( SELECT institution_student_absence_details.academic_period_id ,institution_student_absence_details.institution_id ,institution_student_absence_details.education_grade_id ,institution_student_absence_details.institution_class_id ,institution_student_absence_details.date ,institution_student_absence_details.period ,IFNULL(education_subjects.id, 0) subject_id ,COUNT(DISTINCT(CASE WHEN security_users.gender_id = 2 AND institution_student_absence_details.absence_type_id != 3 THEN institution_student_absence_details.student_id END)) absent_female_count ,COUNT(DISTINCT(CASE WHEN security_users.gender_id = 1 AND institution_student_absence_details.absence_type_id != 3 THEN institution_student_absence_details.student_id END)) absent_male_count ,COUNT(DISTINCT(CASE WHEN security_users.gender_id = 2 AND institution_student_absence_details.absence_type_id = 3 THEN institution_student_absence_details.student_id END)) late_female_count ,COUNT(DISTINCT(CASE WHEN security_users.gender_id = 1 AND institution_student_absence_details.absence_type_id = 3 THEN institution_student_absence_details.student_id END)) late_male_count FROM institution_student_absence_details INNER JOIN ( SELECT institution_class_students.education_grade_id ,institution_class_students.institution_id ,institution_class_students.academic_period_id ,institution_class_students.institution_class_id ,institution_class_students.student_id FROM institution_class_students INNER JOIN academic_periods ON academic_periods.id = institution_class_students.academic_period_id WHERE IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_class_students.student_status_id = 1, institution_class_students.student_status_id IN (1, 7, 6, 8)) GROUP BY institution_class_students.education_grade_id ,institution_class_students.institution_id ,institution_class_students.academic_period_id ,institution_class_students.institution_class_id ,institution_class_students.student_id ) current_students ON current_students.education_grade_id = institution_student_absence_details.education_grade_id AND current_students.institution_id = institution_student_absence_details.institution_id AND current_students.academic_period_id = institution_student_absence_details.academic_period_id AND current_students.institution_class_id = institution_student_absence_details.institution_class_id AND current_students.student_id = institution_student_absence_details.student_id INNER JOIN security_users ON security_users.id = institution_student_absence_details.student_id LEFT JOIN institution_subjects ON institution_subjects.id = institution_student_absence_details.subject_id LEFT JOIN education_subjects ON education_subjects.id = institution_subjects.education_subject_id GROUP BY institution_student_absence_details.academic_period_id ,institution_student_absence_details.institution_id ,institution_student_absence_details.education_grade_id ,institution_student_absence_details.institution_class_id ,institution_student_absence_details.date ,institution_student_absence_details.period ,IFNULL(education_subjects.id, 0) ) marked_absence ON marked_absence.institution_id = main_query.institution_id AND marked_absence.academic_period_id = main_query.academic_period_id AND marked_absence.institution_class_id = main_query.institution_class_id AND marked_absence.education_grade_id = main_query.education_grade_id AND marked_absence.date = dates_generator.date_info AND marked_absence.period = attendance_marking_type.period_id AND marked_absence.subject_id = attendance_marking_type.subject_id ORDER BY main_query.academic_period_name ,main_query.institution_name ,main_query.education_grade_name ,main_query.institution_class_name ,dates_generator.date_info DESC ,attendance_marking_type.period_name ,attendance_marking_type.subject_name;";

//            $this->execute($make_indexes_sql_1);
//            $this->execute($make_indexes_sql_2);
//            $this->execute($make_indexes_sql_3);
//            $this->execute($make_indexes_sql_4);
//            $this->execute($make_indexes_sql_5);
//            $this->execute($make_indexes_sql_6);
//            $this->execute($make_indexes_sql_7);
//            $this->execute($make_indexes_sql_8);
//            $this->execute($make_indexes_sql_9);
//            $this->execute($make_indexes_sql_10);

            $this->execute($fix_missing_sql);
            $this->execute($insert_sql);
//
//        try {
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
//        } catch (\Exception $exception) {
//
//        }
//        try {

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
//        } catch (\Exception $exception) {
//
//        }
    }

    //rollback
    public function down()
    {
        /* Restore backup tables */
//        try {
            $this->execute('DROP TABLE IF EXISTS `report_queries`');
//        } catch (\Exception $exception) {
//
//        }
//        try {
            $this->execute('RENAME TABLE `z_7879_report_queries` TO `report_queries`');
//        } catch (\Exception $exception) {
//
//        }
//        try {
            $this->execute('DROP TABLE IF EXISTS `report_student_attendance_summary`');
//        } catch (\Exception $exception) {
//
//        }
//        try {
            $this->execute('RENAME TABLE `z_7879_report_student_attendance_summary` TO `report_student_attendance_summary`');
//        } catch (\Exception $exception) {
//
//        }
    }

}
