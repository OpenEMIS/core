<?php
use Migrations\AbstractMigration;

/**
 * POCOR-9908: Institutions > Classes showed more students than Institutions > Students
 * and the Institution dashboard for the same grade (e.g. Form 3: 153 vs 149).
 *
 * institution_classes.total_male_students / total_female_students counted every
 * CURRENT class row, while Students / dashboard (POCOR-9410, POCOR-9845) only count
 * students whose account is active (security_users.status = 1) and who hold a CURRENT
 * enrolment in institution_students. The counters were also only recalculated when a
 * class row was saved, so deactivating (or locking) a user never reduced them.
 *
 * Counting rule (reporter-approved; mirrors
 * InstitutionClassStudentsTable::getEnrolledCountByClass() - keep in sync):
 *  - Current academic period: class row CURRENT + active account + matching CURRENT
 *    enrolment in institution_students.
 *  - Past academic periods: class row CURRENT, PROMOTED, GRADUATED or REPEATED;
 *    account status ignored, so later deactivation/locking never reduces history.
 *
 * This migration:
 *  1. Recalculates the counters for ALL classes with that rule, backing up only the
 *     rows that change to zz_9908_institution_classes (restored by down()).
 *  2. security_users trigger: a status/gender change refreshes the counters of that
 *     student's classes in the CURRENT period only (past periods are never touched).
 *  3. academic_periods trigger: when a period's `current` flag changes (set via
 *     updateAll in AcademicPeriodsTable, which bypasses PHP hooks), its classes are
 *     recalculated so the outgoing year switches to the historical rule and the
 *     incoming year to the current rule.
 */
class POCOR9908 extends AbstractMigration
{
    /**
     * Correlated count for one class (alias `c`) and one gender, per the POCOR-9908 rule.
     */
    private function countSql(int $genderId): string
    {
        return "(
            SELECT COUNT(DISTINCT cs.student_id)
            FROM institution_class_students cs
            INNER JOIN student_statuses css ON css.id = cs.student_status_id
            INNER JOIN security_users u ON u.id = cs.student_id AND u.gender_id = {$genderId}
            INNER JOIN academic_periods cap ON cap.id = c.academic_period_id
            WHERE cs.institution_class_id = c.id
            AND (
                (
                    cap.current = 1
                    AND css.code = 'CURRENT'
                    AND u.status = 1
                    AND EXISTS (
                        SELECT 1 FROM institution_students s
                        INNER JOIN student_statuses ss ON ss.id = s.student_status_id AND ss.code = 'CURRENT'
                        WHERE s.student_id = cs.student_id
                        AND s.institution_id = cs.institution_id
                        AND s.academic_period_id = cs.academic_period_id
                        AND s.education_grade_id = cs.education_grade_id
                    )
                )
                OR (
                    NOT (cap.current <=> 1)
                    AND css.code IN ('CURRENT', 'PROMOTED', 'GRADUATED', 'REPEATED')
                )
            )
        )";
    }

    public function up()
    {
        $male = $this->countSql(1);
        $female = $this->countSql(2);

        // 1. Stage recalculated counts for all classes.
        // Explicit CREATE + INSERT ... SELECT rather than CREATE TABLE ... SELECT, which
        // is rejected on servers with enforce_gtid_consistency=ON (e.g. RDS with GTID).
        $this->execute('DROP TABLE IF EXISTS `zz_9908_class_counts_new`');
        $this->execute('CREATE TABLE `zz_9908_class_counts_new` (
            `id` INT NOT NULL PRIMARY KEY,
            `total_male_students` INT NOT NULL,
            `total_female_students` INT NOT NULL
        )');
        $this->execute("
            INSERT INTO `zz_9908_class_counts_new` (id, total_male_students, total_female_students)
            SELECT c.id, {$male}, {$female}
            FROM institution_classes c
        ");

        // Back up only the rows that will change, so down() can restore them.
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9908_institution_classes` (
            `id` INT NOT NULL PRIMARY KEY,
            `total_male_students` INT NULL,
            `total_female_students` INT NULL
        )');
        $this->execute('
            INSERT IGNORE INTO `zz_9908_institution_classes` (id, total_male_students, total_female_students)
            SELECT c.id, c.total_male_students, c.total_female_students
            FROM institution_classes c
            INNER JOIN zz_9908_class_counts_new n ON n.id = c.id
            WHERE NOT (c.total_male_students <=> n.total_male_students)
               OR NOT (c.total_female_students <=> n.total_female_students)
        ');

        $this->execute('
            UPDATE institution_classes c
            INNER JOIN zz_9908_class_counts_new n ON n.id = c.id
            SET c.total_male_students = n.total_male_students,
                c.total_female_students = n.total_female_students
            WHERE NOT (c.total_male_students <=> n.total_male_students)
               OR NOT (c.total_female_students <=> n.total_female_students)
        ');
        $this->execute('DROP TABLE IF EXISTS `zz_9908_class_counts_new`');

        // 2. Account status / gender change -> refresh that student's CURRENT-period classes only.
        $this->execute('DROP TRIGGER IF EXISTS trigger_security_users_class_counts_update');
        $this->execute("
            CREATE TRIGGER trigger_security_users_class_counts_update
            AFTER UPDATE ON security_users
            FOR EACH ROW
            BEGIN
                IF NOT (OLD.status <=> NEW.status) OR NOT (OLD.gender_id <=> NEW.gender_id) THEN
                    UPDATE institution_classes c
                    SET c.total_male_students = {$male},
                        c.total_female_students = {$female}
                    WHERE c.id IN (
                        SELECT x.institution_class_id
                        FROM institution_class_students x
                        INNER JOIN academic_periods xap ON xap.id = x.academic_period_id AND xap.current = 1
                        WHERE x.student_id = NEW.id
                    );
                END IF;
            END
        ");

        // 3. Current academic period switched -> recalculate that period's classes.
        $this->execute('DROP TRIGGER IF EXISTS trigger_academic_periods_class_counts_update');
        $this->execute("
            CREATE TRIGGER trigger_academic_periods_class_counts_update
            AFTER UPDATE ON academic_periods
            FOR EACH ROW
            BEGIN
                IF NOT (OLD.current <=> NEW.current) THEN
                    UPDATE institution_classes c
                    SET c.total_male_students = {$male},
                        c.total_female_students = {$female}
                    WHERE c.academic_period_id = NEW.id;
                END IF;
            END
        ");
    }

    public function down()
    {
        $this->execute('DROP TRIGGER IF EXISTS trigger_security_users_class_counts_update');
        $this->execute('DROP TRIGGER IF EXISTS trigger_academic_periods_class_counts_update');

        $this->execute('
            UPDATE institution_classes c
            INNER JOIN `zz_9908_institution_classes` bak ON bak.id = c.id
            SET c.total_male_students = bak.total_male_students,
                c.total_female_students = bak.total_female_students
        ');
        $this->execute('DROP TABLE IF EXISTS `zz_9908_institution_classes`');
    }
}
