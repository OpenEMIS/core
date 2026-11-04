<?php
use Migrations\AbstractMigration;

/**
 * POCOR-9892: only "Absence - Excused" is supposed to let a user pick a reason,
 * but the save path (institution.student.attendances.svc.js's saveAbsences())
 * defaulted `student_absence_reason_id` to the first configured reason regardless
 * of absence type, polluting some Late/Unexcused records with a reason that was
 * never actually selectable. The grid happens to hide this (its reason cell only
 * renders for EXCUSED), but the Attendance export has no such guard and prints
 * the raw value - confirmed live: 3 LATE and 1 UNEXCUSED record had a reason set.
 *
 * This corrects the already-saved bad rows. The frontend save path is also fixed
 * in code, but `institution_student_absence_details` is written from at least 8
 * separate CakePHP Table classes plus a completely separate Laravel API
 * (api/app/Repositories/AttendanceRepository.php, raw Eloquent insert - no
 * CakePHP hook can intercept it). A DB trigger is the only mechanism that
 * guarantees this can never recur regardless of which code path writes.
 *
 * Known follow-ups, intentionally not handled here (see code review on
 * POCOR-9892): ImportStudentAttendancesTable.php and the Laravel API's
 * StudentRepository.php both pass a client-supplied reason straight through
 * for non-Excused rows with no validation - these triggers now silently null
 * that value rather than the caller being told. Also, already-archived
 * records (see StudentAttendancesTable.php's ArchiveConnections usage) are
 * not touched by this migration or these triggers.
 */
class POCOR9892 extends AbstractMigration
{
    public function up()
    {
        // Only backs up the rows this migration actually changes (not the whole
        // table) - down() still works unchanged, since it matches on the full
        // composite key and simply finds fewer backup rows to restore.
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9892_institution_student_absence_details` LIKE `institution_student_absence_details`');
        $this->execute("
            INSERT INTO `zz_9892_institution_student_absence_details`
            SELECT d.* FROM `institution_student_absence_details` d
            INNER JOIN `absence_types` t ON t.id = d.absence_type_id
            WHERE t.code != 'EXCUSED' AND d.student_absence_reason_id IS NOT NULL
        ");

        $this->execute('
            UPDATE `institution_student_absence_details` d
            INNER JOIN `absence_types` t ON t.id = d.absence_type_id
            SET d.student_absence_reason_id = NULL
            WHERE t.code != \'EXCUSED\' AND d.student_absence_reason_id IS NOT NULL
        ');

        $this->execute('DROP TRIGGER IF EXISTS trigger_institution_student_absence_details_reason_insert');
        $this->execute('DROP TRIGGER IF EXISTS trigger_institution_student_absence_details_reason_update');

        // IFNULL so a NULL or unrecognised absence_type_id is also treated as
        // "not EXCUSED" and nulls the reason - only a confirmed EXCUSED type
        // preserves it.
        $this->execute("
            CREATE TRIGGER trigger_institution_student_absence_details_reason_insert
            BEFORE INSERT ON institution_student_absence_details
            FOR EACH ROW
            BEGIN
                IF IFNULL((SELECT code FROM absence_types WHERE id = NEW.absence_type_id), '') != 'EXCUSED' THEN
                    SET NEW.student_absence_reason_id = NULL;
                END IF;
            END
        ");

        $this->execute("
            CREATE TRIGGER trigger_institution_student_absence_details_reason_update
            BEFORE UPDATE ON institution_student_absence_details
            FOR EACH ROW
            BEGIN
                IF IFNULL((SELECT code FROM absence_types WHERE id = NEW.absence_type_id), '') != 'EXCUSED' THEN
                    SET NEW.student_absence_reason_id = NULL;
                END IF;
            END
        ");
    }

    public function down()
    {
        $this->execute('DROP TRIGGER IF EXISTS trigger_institution_student_absence_details_reason_insert');
        $this->execute('DROP TRIGGER IF EXISTS trigger_institution_student_absence_details_reason_update');

        $this->execute('
            UPDATE `institution_student_absence_details` d
            INNER JOIN `zz_9892_institution_student_absence_details` bak ON bak.student_id = d.student_id
                AND bak.institution_id = d.institution_id
                AND bak.academic_period_id = d.academic_period_id
                AND bak.institution_class_id = d.institution_class_id
                AND bak.date = d.date
                AND bak.period = d.period
                AND bak.subject_id = d.subject_id
            SET d.student_absence_reason_id = bak.student_absence_reason_id
        ');

        $this->execute('DROP TABLE IF EXISTS `zz_9892_institution_student_absence_details`');
    }
}
