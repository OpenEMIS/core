<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

// =============================================================================
// POCOR-8898 — CRITICAL DATA INTEGRITY WARNING
// =============================================================================
//
// This branch introduces STUDENT REPORT CARD ARCHIVING and refactors ALL
// existing archive features (Student Attendances, Staff Attendances, Student
// Assessments) from CakePHP Shells to CakePHP Commands.
//
// THE ARCHIVING PROCESS MOVES (not copies) records from live tables into
// archive tables, then DELETES them from the live tables. This is destructive
// and irreversible without the backups created here.
//
// TABLES AFFECTED BY ARCHIVING (all backed up in up()):
//
//   Feature: Student Attendances
//     institution_class_attendance_records
//     institution_student_absences
//     institution_student_absence_details
//     student_attendance_marked_records
//     student_attendance_mark_types
//
//   Feature: Staff Attendances
//     institution_staff_attendances
//     institution_staff_leave
//
//   Feature: Student Assessments
//     assessment_item_results
//
//   Feature: Student Report Cards  (NEW in this branch)
//     institution_students_report_cards
//
// WHY ALL THESE TABLES:
//   This branch changes the internal mechanism used to trigger archiving —
//   from CakePHP Shell (bin/cake ArchiveStudentAttendances) to CakePHP Command
//   (bin/cake archive_student_attendances). While the SQL logic is unchanged,
//   the refactor touches the dispatch path for ALL archive features. If a bug
//   was introduced in the Command layer, any of these features could misbehave
//   and corrupt or lose data. Backing up all affected tables here means down()
//   can restore a clean pre-POCOR-8898 state for all of them.
//
// DO NOT deploy to production without:
//   - A full database backup independent of this migration
//   - Sysadmin awareness that archiving is destructive and one-way
//   - Confirmation that no archive has been triggered since deployment
//     (if it has, see SYSADMIN RESTORE INSTRUCTIONS in down())
//
// =============================================================================

class POCOR8898 extends AbstractMigration
{
    // -------------------------------------------------------------------------
    // All tables to back up, grouped by archive feature.
    // Prefix for backup tables: z_8898_
    // -------------------------------------------------------------------------
    private array $archiveTables = [
        // Student Attendances — refactored Shell → Command
        'institution_class_attendance_records',
        'institution_student_absences',
        'institution_student_absence_details',
        'student_attendance_marked_records',
        'student_attendance_mark_types',
        // Staff Attendances — refactored Shell → Command
        'institution_staff_attendances',
        'institution_staff_leave',
        // Student Assessments — refactored Shell → Command
        'assessment_item_results',
        // Student Report Cards — NEW archiving feature in this branch
        'institution_students_report_cards',
    ]; //POCOR-8898

    private function backupTables(): void //POCOR-8898
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($this->archiveTables as $table) {
            $backup = 'z_8898_' . $table;
            if (!$this->hasTable($backup)) {
                $this->execute("CREATE TABLE `{$backup}` LIKE `{$table}`");
                $this->execute("INSERT INTO `{$backup}` SELECT * FROM `{$table}`");
            }
        }
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

        // security_functions backed up separately — it is modified (not archived)
        if (!$this->hasTable('z_8898_security_functions')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_8898_security_functions` LIKE `security_functions`');
            $this->execute('INSERT INTO `z_8898_security_functions` SELECT * FROM `security_functions`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    } //POCOR-8898

    private function restoreTables(): void //POCOR-8898
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($this->archiveTables as $table) {
            $backup = 'z_8898_' . $table;
            if ($this->hasTable($backup)) {
                $this->execute("DROP TABLE IF EXISTS `{$table}`");
                $this->execute("RENAME TABLE `{$backup}` TO `{$table}`");
            }
        }
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

        // Restore security_functions
        if ($this->hasTable('z_8898_security_functions')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `security_functions`');
            $this->execute('RENAME TABLE `z_8898_security_functions` TO `security_functions`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    } //POCOR-8898

    // =========================================================================
    // UP
    // =========================================================================

    public function up(): void
    {
        // STEP 1 — Backup ALL archive-affected tables first (idempotent)
        $this->backupTables(); //POCOR-8898

        // STEP 2 — Create Student Report Cards archive table (idempotent guard)
        if (!$this->hasTable('institution_students_report_cards_archived')) { //POCOR-8898
            $this->execute('CREATE TABLE `institution_students_report_cards_archived` LIKE `institution_students_report_cards`'); //POCOR-8898
        }

        // STEP 3 — Insert security_function for Student Report Card Archive
        // Idempotency guard: skip if already present (handles up→down→up on same DB)
        $alreadyExists = $this->fetchRow(
            "SELECT id FROM `security_functions`
             WHERE `name` = 'Student Report Card Archive'
               AND `controller` = 'Institutions'
               AND `module` = 'Institutions'
               AND `category` = 'Students'
             LIMIT 1"
        ); //POCOR-8898

        if (!$alreadyExists) { //POCOR-8898
            $anchor = $this->fetchRow(
                "SELECT `order`, `parent_id` FROM `security_functions`
                 WHERE `name` = 'Student Assessment Archive'
                   AND `controller` = 'Institutions'
                   AND `module` = 'Institutions'
                   AND `category` = 'Students'
                 LIMIT 1"
            ); //POCOR-8898

            if ($anchor) { //POCOR-8898
                $newOrder = (int) $anchor['order'] + 1; //POCOR-8898
                $parentId = (int) $anchor['parent_id']; //POCOR-8898

                // Shift sibling functions at order >= newOrder up by 1 to make room
                $this->execute(
                    "UPDATE `security_functions`
                     SET `order` = `order` + 1
                     WHERE `parent_id` = {$parentId}
                       AND `category` = 'Students'
                       AND `order` >= {$newOrder}
                       AND `name` != 'Student Assessment Archive'"
                ); //POCOR-8898

                $this->execute(
                    "INSERT IGNORE INTO `security_functions`
                        (`id`, `name`, `controller`, `module`, `category`, `parent_id`,
                         `_view`, `_edit`, `_add`, `_delete`, `_execute`,
                         `order`, `visible`, `description`,
                         `modified_user_id`, `modified`, `created_user_id`, `created`)
                     VALUES
                        (NULL,
                         'Student Report Card Archive',
                         'Institutions', 'Institutions', 'Students', {$parentId},
                         'InstitutionStudentsReportCardsArchived.index | ReportCardArchives.index',
                         NULL, NULL, NULL, NULL,
                         {$newOrder}, 1, NULL,
                         2, NOW(), 1, NOW())"
                ); //POCOR-8898
            }
        }
    }

    // =========================================================================
    // DOWN
    // =========================================================================

    public function down(): void
    {
        // Restore security_functions and all archive-affected live tables
        // from their point-in-time backups taken at migration time.
        $this->restoreTables(); //POCOR-8898

        // Do NOT drop institution_students_report_cards_archived here.
        //
        // =====================================================================
        // POCOR-8898 — SYSADMIN MANUAL RESTORE INSTRUCTIONS
        // =====================================================================
        //
        // After this rollback, all live tables have been restored to their
        // state AT THE TIME this migration originally ran (before any archiving).
        //
        // IF any archiving was performed after the migration ran, those records
        // were MOVED out of the live tables into archive tables:
        //   institution_students_report_cards_archived
        //   (and equivalents for student/staff attendances, assessments if applicable)
        //
        // Those archived records are NOT in the backups restored above.
        // They now exist ONLY in the archive tables.
        //
        // -----------------------------------------------------------------------
        // TO RECOVER ARCHIVED REPORT CARD RECORDS:
        // -----------------------------------------------------------------------
        //
        // Step 1 — Check how many archived records exist:
        //   SELECT COUNT(*) FROM `institution_students_report_cards_archived`;
        //
        // Step 2 — Copy archived records back to the live table (safe to re-run):
        //   INSERT IGNORE INTO `institution_students_report_cards`
        //   SELECT * FROM `institution_students_report_cards_archived`;
        //
        // Step 3 — Verify count before dropping:
        //   SELECT COUNT(*) FROM `institution_students_report_cards`
        //   WHERE (report_card_id, student_id, institution_id, academic_period_id, education_grade_id)
        //   IN (SELECT report_card_id, student_id, institution_id, academic_period_id, education_grade_id
        //       FROM `institution_students_report_cards_archived`);
        //
        // Step 4 — Only after confirming records are restored, drop the archive:
        //   DROP TABLE `institution_students_report_cards_archived`;
        //
        // -----------------------------------------------------------------------
        // The same logic applies to other archive tables if those features were
        // triggered. Check institution_class_attendance_records_archived,
        // assessment_item_results_archived, etc. as appropriate.
        //
        // WARNING: Do NOT drop any archive table without first verifying its
        // records are safely present in the corresponding live table.
        // Premature deletion means permanent, unrecoverable data loss.
        // =====================================================================
    }
}
