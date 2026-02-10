<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9549 extends AbstractMigration
{
    public function up(): void
    {
        // Backup table
        $this->execute('DROP TABLE IF EXISTS `zz_9549_report_queries`');
        $this->execute('CREATE TABLE `zz_9549_report_queries` LIKE `report_queries`');
        $this->execute('INSERT INTO `zz_9549_report_queries` SELECT * FROM `report_queries`');
        // Update existing records
        $this->execute('UPDATE report_queries SET name = "summary_area_institution_grade_attendances_insert" WHERE name = "summary_area_institution_grade_attendances"');
        // Insert records
        $this->execute('INSERT INTO `report_queries` (`id`, `name`, `query_sql`, `frequency`, `status`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, "summary_area_institution_grade_attendances_update", "UPDATE summary_area_institution_grade_attendances sai LEFT JOIN( SELECT samr.institution_id, samr.education_grade_id, samr.date, COUNT(DISTINCT CASE WHEN su.gender_id = 1 AND samr.no_scheduled_class = 1 THEN ics.student_id END) AS nolesson_male_count, COUNT(DISTINCT CASE WHEN su.gender_id = 2 AND samr.no_scheduled_class = 1 THEN ics.student_id END) AS nolesson_female_count, ( COUNT(DISTINCT CASE WHEN su.gender_id = 1 AND samr.no_scheduled_class = 1 THEN ics.student_id END) + COUNT(DISTINCT CASE WHEN su.gender_id = 2 AND samr.no_scheduled_class = 1 THEN ics.student_id END)) AS nolesson_total_count, COUNT(DISTINCT CASE WHEN su.gender_id = 1 AND samr.no_scheduled_class = 0 AND isad.absence_type_id IN (1,2) THEN isad.student_id END) AS absent_male_count, COUNT(DISTINCT CASE WHEN su.gender_id = 2 AND samr.no_scheduled_class = 0 AND isad.absence_type_id IN (1,2) THEN isad.student_id END) AS absent_female_count, ( COUNT(DISTINCT CASE WHEN su.gender_id = 1 AND samr.no_scheduled_class = 0 AND isad.absence_type_id IN (1,2) THEN isad.student_id END) + COUNT(DISTINCT CASE WHEN su.gender_id = 2 AND samr.no_scheduled_class = 0 AND isad.absence_type_id IN (1,2) THEN isad.student_id END) ) AS absent_total_count, COUNT(DISTINCT CASE WHEN su.gender_id = 1 AND samr.no_scheduled_class = 0 AND isad.absence_type_id NOT IN (1,2) THEN isad.student_id END) AS late_male_count, COUNT(DISTINCT CASE WHEN su.gender_id = 2 AND samr.no_scheduled_class = 0 AND isad.absence_type_id NOT IN (1,2) THEN isad.student_id END) AS late_female_count, ( COUNT(DISTINCT CASE WHEN su.gender_id = 1 AND samr.no_scheduled_class = 0 AND isad.absence_type_id NOT IN (1,2) THEN isad.student_id END) + COUNT(DISTINCT CASE WHEN su.gender_id = 2 AND samr.no_scheduled_class = 0 AND isad.absence_type_id NOT IN (1,2) THEN isad.student_id END) ) AS late_total_count FROM student_attendance_marked_records samr INNER JOIN institution_class_students ics ON ics.institution_class_id = samr.institution_class_id AND ics.academic_period_id = samr.academic_period_id AND ics.education_grade_id = samr.education_grade_id AND ics.institution_id = samr.institution_id INNER JOIN institution_students istu ON istu.student_id = ics.student_id AND istu.education_grade_id = ics.education_grade_id AND istu.academic_period_id = ics.academic_period_id AND istu.institution_id = ics.institution_id AND samr.date BETWEEN istu.start_date AND istu.end_date INNER JOIN security_users su ON su.id = istu.student_id LEFT JOIN institution_student_absence_details isad ON isad.institution_id = samr.institution_id AND isad.academic_period_id = samr.academic_period_id AND isad.institution_class_id = samr.institution_class_id AND isad.education_grade_id = samr.education_grade_id AND isad.date = samr.date AND isad.student_id = ics.student_id WHERE samr.date BETWEEN CURRENT_DATE - INTERVAL 7 DAY AND CURRENT_DATE GROUP BY samr.institution_id, samr.education_grade_id, samr.date ) summary_absence_table ON summary_absence_table.institution_id = sai.institution_id AND summary_absence_table.education_grade_id = sai.education_grade_id AND summary_absence_table.date = sai.attendance_date SET sai.present_female_count = GREATEST(0, COALESCE(sai.female_count, 0) - COALESCE(summary_absence_table.absent_female_count, 0) - COALESCE(summary_absence_table.nolesson_female_count, 0) ), sai.present_male_count = GREATEST(0, COALESCE(sai.male_count, 0) - COALESCE(summary_absence_table.absent_male_count, 0) - COALESCE(summary_absence_table.nolesson_male_count, 0) ), sai.present_total_count = GREATEST(0, COALESCE(sai.total_count, 0) - COALESCE(summary_absence_table.absent_total_count, 0) - COALESCE(summary_absence_table.nolesson_total_count, 0) ), sai.absent_female_count = COALESCE(summary_absence_table.absent_female_count, 0), sai.absent_male_count = COALESCE(summary_absence_table.absent_male_count, 0), sai.absent_total_count = COALESCE(summary_absence_table.absent_total_count, 0), sai.late_female_count = COALESCE(summary_absence_table.late_female_count, 0), sai.late_male_count = COALESCE(summary_absence_table.late_male_count, 0), sai.late_total_count = COALESCE(summary_absence_table.late_total_count, 0);", "day", "1", NULL, NULL, "1", CURRENT_TIMESTAMP);');
        $this->execute('DROP TRIGGER IF EXISTS trigger_institution_student_absence_details_delete;');
        $this->execute('DROP TRIGGER IF EXISTS trigger_institution_student_absence_details_insert;');
        $this->execute('DROP TRIGGER IF EXISTS trigger_institution_student_absence_details_update;');

    }

    public function down(): void
    {
        // Restore from backup
        $this->execute('DROP TABLE IF EXISTS `report_queries`');
        $this->execute('RENAME TABLE `zz_9549_report_queries` TO `report_queries`');

    }
}
