<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * POCOR-9899
 *
 * Student attendance summary (dashboard / Student Attendance Summary report) fixes.
 * The two report_queries rows are updated in place by name -- not deleted and
 * re-inserted -- so they keep their ids (83/84/85 on most environments), and with
 * them their place in openemis_core_reports' `ORDER BY id` run order:
 * summary_class_attendances -> summary_student_attendances -> summary_student_absences
 * -> summary_area_institution_grade_attendances.
 *
 * 1. summary_student_absences_insert: drops the `EXISTS institution_subject_students`
 *    condition. Students with no subject enrolment (mostly infant classes) had their
 *    absences ignored and were counted as present on the dashboard.
 *
 * 2. summary_class_attendances_insert: used to build rows for CURDATE() only, so
 *    attendance marked for a day the daily run did not cover (or for a class set up
 *    later) never reached the dashboard. It now builds rows for every date on which
 *    an institution marked attendance in the current academic period -- weekends,
 *    holidays and closures are excluded naturally since nothing is marked on them --
 *    plus today (if a school day), as before, so today's unmarked classes still show.
 *    The institution_students join is also matched on institution and academic period,
 *    so a student is only counted in a class on dates they were enrolled there.
 *
 * 3. Regenerates the current academic period's summary tables by running the four
 *    summary queries once, in their normal run order.
 */
class POCOR9899 extends AbstractMigration
{
    private const SUMMARY_QUERY_NAMES = [
        'summary_class_attendances_insert',
        'summary_student_attendances_insert',
        'summary_student_absences_insert',
        'summary_area_institution_grade_attendances_insert',
    ];

    public function up(): void
    {
        $this->execute('DROP TABLE IF EXISTS `z_9899_report_queries`');
        $this->execute('CREATE TABLE `z_9899_report_queries` LIKE `report_queries`');
        $this->execute('INSERT INTO `z_9899_report_queries` SELECT * FROM `report_queries`');

        $this->updateQuerySql('summary_class_attendances_insert', $this->summaryClassAttendancesSql());
        $this->updateQuerySql('summary_student_absences_insert', $this->summaryStudentAbsencesSql());

        $this->regenerateSummaryTables();
    }

    public function down(): void
    {
        $this->execute('UPDATE `report_queries` rq INNER JOIN `z_9899_report_queries` z ON z.`id` = rq.`id` SET rq.`query_sql` = z.`query_sql`, rq.`modified_user_id` = z.`modified_user_id`, rq.`modified` = z.`modified` WHERE rq.`name` IN (\'summary_class_attendances_insert\', \'summary_student_absences_insert\')');
        $this->execute('DROP TABLE IF EXISTS `z_9899_report_queries`');
    }

    private function updateQuerySql(string $name, string $sql): void
    {
        $this->execute(
            'UPDATE `report_queries` SET `query_sql` = ?, `modified_user_id` = 1, `modified` = NOW() WHERE `name` = ?',
            [$sql, $name]
        );
    }

    /**
     * Runs the active summary queries once, in the same order the scheduler does, so
     * the current academic period is rebuilt now rather than on the next daily run.
     * Best-effort: the daily run executes the same queries, so a failure here is
     * reported but does not fail the migration.
     */
    private function regenerateSummaryTables(): void
    {
        $quotedNames = "'" . implode("', '", self::SUMMARY_QUERY_NAMES) . "'";
        $rows = $this->fetchAll("SELECT `name`, `query_sql` FROM `report_queries` WHERE `status` = 1 AND `name` IN ({$quotedNames}) ORDER BY `id` ASC");

        foreach ($rows as $row) {
            try {
                $this->execute($row['query_sql']);
            } catch (\Throwable $e) {
                $this->getOutput()->writeln("<comment>POCOR-9899: regenerating via {$row['name']} failed, the daily report run will retry it: {$e->getMessage()}</comment>");
            }
        }
    }

    private function summaryClassAttendancesSql(): string
    {
        return <<<'SQL'
INSERT INTO `summary_class_attendances`(`id`, `institution_id`, `institution_class_id`, `academic_period_id`, `academic_period_name`, `education_grade_id`, `date`, `period_id`, `period_name`, `female_count`, `male_count`, `total_count`, `created`) SELECT UUID() AS `id`, a.`institution_id`, a.`institution_class_id`, a.`academic_period_id`, a.`academic_period_name`, a.`education_grade_id`, a.`dates` AS `date`, COALESCE(period_config.`period_id`, 0) AS `period_id`, period_config.`period_name`, a.`female_count`, a.`male_count`, a.`total_count`, NOW() AS `created` FROM(SELECT ics.`institution_id`, ics.`institution_class_id`, apd.`academic_period_id`, apd.`academic_period_name`, icg.`education_grade_id`, apd.`attendance_date` AS `dates`, SUM(CASE WHEN su.`gender_id` = 2 THEN 1 ELSE 0 END) AS `female_count`, SUM(CASE WHEN su.`gender_id` = 1 THEN 1 ELSE 0 END) AS `male_count`, SUM(CASE WHEN su.`gender_id` IN (1, 2) THEN 1 ELSE 0 END) AS `total_count` FROM (SELECT ap.`id` AS `academic_period_id`, ap.`name` AS `academic_period_name`, samr.`institution_id`, samr.`date` AS `attendance_date` FROM `academic_periods` ap INNER JOIN `student_attendance_marked_records` samr ON samr.`academic_period_id` = ap.`id` AND samr.`no_scheduled_class` = 0 WHERE ap.`id` = (SELECT `id` FROM `academic_periods` WHERE `current` = 1 AND `parent_id` > 0 AND `editable` = 1 AND `visible` > 0 ORDER BY `start_date` DESC LIMIT 1) AND samr.`date` BETWEEN ap.`start_date` AND ap.`end_date` GROUP BY ap.`id`, ap.`name`, samr.`institution_id`, samr.`date` UNION SELECT ap.`id` AS `academic_period_id`, ap.`name` AS `academic_period_name`, i.`id` AS `institution_id`, CURDATE() AS `attendance_date` FROM `academic_periods` ap CROSS JOIN `institutions` i INNER JOIN `config_items` fdow ON fdow.`code` = 'first_day_of_week' INNER JOIN `config_items` dpw ON dpw.`code` = 'days_per_week' WHERE ap.`parent_id` <> 0 AND ap.`current` = 1 AND CURDATE() BETWEEN ap.`start_date` AND ap.`end_date` AND MOD((WEEKDAY(CURDATE()) + 1 - CAST(IF(TRIM(IFNULL(fdow.`value`, '')) = '', '1', fdow.`value`) AS SIGNED) + 7), 7) < CAST(IF(TRIM(IFNULL(dpw.`value`, '')) = '', '5', dpw.`value`) AS SIGNED)) apd INNER JOIN `institution_class_students` ics ON ics.`academic_period_id` = apd.`academic_period_id` AND ics.`institution_id` = apd.`institution_id` INNER JOIN `institution_class_grades` icg ON icg.`institution_class_id` = ics.`institution_class_id` INNER JOIN `institution_students` ist ON ist.`student_id` = ics.`student_id` AND ist.`education_grade_id` = icg.`education_grade_id` AND ist.`institution_id` = ics.`institution_id` AND ist.`academic_period_id` = ics.`academic_period_id` INNER JOIN `security_users` su ON su.`id` = ist.`student_id` WHERE apd.`attendance_date` BETWEEN ist.`start_date` AND ist.`end_date` GROUP BY icg.`education_grade_id`, ics.`institution_id`, ics.`institution_class_id`, apd.`academic_period_id`, apd.`academic_period_name`, apd.`attendance_date`) a INNER JOIN (SELECT smts.`academic_period_id`, smtsg.`education_grade_id`, smts.`date_enabled`, smts.`date_disabled`, sapdp.`name` AS `period_name`, sapdp.`period` AS `period_id` FROM `student_mark_type_statuses` smts INNER JOIN `student_mark_type_status_grades` smtsg ON smts.`id` = smtsg.`student_mark_type_status_id` INNER JOIN `student_attendance_mark_types` satmt ON satmt.`id` = smts.`student_attendance_mark_type_id` LEFT JOIN `student_attendance_per_day_periods` sapdp ON sapdp.`student_attendance_mark_type_id` = satmt.`id`) AS period_config ON a.`academic_period_id` = period_config.`academic_period_id` AND a.`education_grade_id` = period_config.`education_grade_id` AND a.`dates` BETWEEN period_config.`date_enabled` AND COALESCE(period_config.`date_disabled`, '9999-12-31') ON DUPLICATE KEY UPDATE `academic_period_name` = VALUES(`academic_period_name`), `period_name` = VALUES(`period_name`), `female_count` = VALUES(`female_count`), `male_count` = VALUES(`male_count`), `total_count` = VALUES(`total_count`);
SQL;
    }

    private function summaryStudentAbsencesSql(): string
    {
        return <<<'SQL'
INSERT INTO `summary_student_absences`(`id`, `academic_period_id`, `education_grade_id`, `institution_id`, `institution_class_id`, `date`, `student_id`, `student_gender_id`, `total_absences_day`, `total_late_day`, `valid_marked_slots`, `partial_day_absence`, `full_day_absence`, `created`) SELECT UUID() AS `id`, count_student_absent_day.`academic_period_id`, count_student_absent_day.`education_grade_id`, count_student_absent_day.`institution_id`, count_student_absent_day.`institution_class_id`, count_student_absent_day.`date`, count_student_absent_day.`student_id`, su.`gender_id`, count_student_absent_day.`total_absences_day`, count_student_absent_day.`total_late_day`, marked_slots.`valid_marked_slots`, CASE WHEN marked_slots.`valid_marked_slots` > 0 AND count_student_absent_day.`total_absences_day` > 0 AND count_student_absent_day.`total_absences_day` < marked_slots.`valid_marked_slots` THEN 1 ELSE 0 END AS `partial_day_absence`, CASE WHEN marked_slots.`valid_marked_slots` > 0 AND count_student_absent_day.`total_absences_day` >= marked_slots.`valid_marked_slots` THEN 1 ELSE 0 END AS `full_day_absence`, NOW() AS `created` FROM(SELECT isad.`academic_period_id`, isad.`date`, isad.`institution_id`, isad.`education_grade_id`, isad.`institution_class_id`, isad.`student_id`, SUM(CASE WHEN isad.`absence_type_id` != 3 THEN 1 ELSE 0 END) AS `total_absences_day`, SUM(CASE WHEN isad.`absence_type_id` = 3 THEN 1 ELSE 0 END) AS `total_late_day` FROM `institution_student_absence_details` isad INNER JOIN `institution_class_students` ics ON ics.`student_id` = isad.`student_id` AND ics.`institution_class_id` = isad.`institution_class_id` INNER JOIN student_attendance_marked_records samr ON samr.institution_class_id = isad.institution_class_id AND samr.date = isad.date AND samr.period = isad.period AND samr.education_grade_id = isad.education_grade_id WHERE samr.no_scheduled_class = 0 AND isad.`academic_period_id` = (SELECT `id` FROM `academic_periods` WHERE `current` = 1 AND `parent_id` > 0 AND `editable` = 1 AND `visible` > 0 ORDER BY `start_date` DESC LIMIT 1) GROUP BY isad.`academic_period_id`, isad.`date`, isad.`institution_id`, isad.`education_grade_id`, isad.`institution_class_id`, isad.`student_id`) count_student_absent_day INNER JOIN `security_users` su ON su.`id` = count_student_absent_day.`student_id` INNER JOIN (SELECT `academic_period_id`, `institution_id`, `education_grade_id`, `class_id`, `attendance_date`, COUNT(*) AS `valid_marked_slots` FROM `summary_student_attendances` WHERE `marked_attendance` > 0 AND `academic_period_id` = (SELECT `id` FROM `academic_periods` WHERE `current` = 1 AND `parent_id` > 0 AND `editable` = 1 AND `visible` > 0 ORDER BY `start_date` DESC LIMIT 1) GROUP BY `academic_period_id`, `institution_id`, `education_grade_id`, `class_id`, `attendance_date`) marked_slots ON marked_slots.`academic_period_id` = count_student_absent_day.`academic_period_id` AND marked_slots.`institution_id` = count_student_absent_day.`institution_id` AND marked_slots.`education_grade_id` = count_student_absent_day.`education_grade_id` AND marked_slots.`class_id` = count_student_absent_day.`institution_class_id` AND marked_slots.`attendance_date` = count_student_absent_day.`date` ON DUPLICATE KEY UPDATE `student_gender_id` = VALUES(`student_gender_id`), `total_absences_day` = VALUES(`total_absences_day`), `total_late_day` = VALUES(`total_late_day`), `valid_marked_slots` = VALUES(`valid_marked_slots`), `partial_day_absence` = VALUES(`partial_day_absence`), `full_day_absence` = VALUES(`full_day_absence`);
SQL;
    }
}
