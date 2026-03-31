<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8898 extends AbstractMigration
{
    //POCOR-8898: start
    private function backupTables(): void
    {
        if (!$this->hasTable('z_8898_security_functions')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_8898_security_functions` LIKE `security_functions`');
            $this->execute('INSERT INTO `z_8898_security_functions` SELECT * FROM `security_functions`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function restoreTable(): void
    {
        if ($this->hasTable('z_8898_security_functions')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `security_functions`');
            $this->execute('RENAME TABLE `z_8898_security_functions` TO `security_functions`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
    //POCOR-8898: end

    public function up(): void
    {
        $this->backupTables(); //POCOR-8898: backup always first

        //POCOR-8898: create archive table for institution_students_report_cards
        if (!$this->hasTable('institution_students_report_cards_archived')) {
            $this->execute('CREATE TABLE `institution_students_report_cards_archived` LIKE `institution_students_report_cards`'); //POCOR-8898
        }

        //POCOR-8898: insert security_function for Student Report Card Archive,
        //positioned after Student Assessment Archive — shift existing rows to make room
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

            //POCOR-8898: shift all sibling functions at order >= newOrder up by 1
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

    public function down(): void
    {
        //POCOR-8898: restore security_functions from backup (reverts order shifts too)
        $this->restoreTable(); //POCOR-8898

        //POCOR-8898: intentionally NOT dropping institution_students_report_cards_archived
        //If archiving was already performed, that table contains live data that cannot be recovered.
        //Dropping it here would cause permanent data loss (records moved out of source are gone).
        //A sysadmin must manually decide what to do with the archived data before removing the table.

        // -----------------------------------------------------------------------
        // POCOR-8898: SYSADMIN MANUAL RESTORE INSTRUCTIONS
        // -----------------------------------------------------------------------
        // If report card archiving was run before this rollback, the archived
        // records now live ONLY in institution_students_report_cards_archived.
        // They were removed from institution_students_report_cards during archiving.
        //
        // To restore them back to the live table, run the following SQL manually
        // AFTER this migration has been rolled back:
        //
        // Step 1 — Copy archived records back to the live table (safe to re-run):
        //   INSERT IGNORE INTO `institution_students_report_cards`
        //   SELECT * FROM `institution_students_report_cards_archived`;
        //
        // Step 2 — Verify the count matches before proceeding:
        //   SELECT COUNT(*) FROM `institution_students_report_cards_archived`;
        //   SELECT COUNT(*) FROM `institution_students_report_cards`
        //   WHERE (report_card_id, student_id, institution_id, academic_period_id, education_grade_id)
        //   IN (SELECT report_card_id, student_id, institution_id, academic_period_id, education_grade_id
        //       FROM `institution_students_report_cards_archived`);
        //
        // Step 3 — Only after confirming records are restored, drop the archive table:
        //   DROP TABLE `institution_students_report_cards_archived`;
        // -----------------------------------------------------------------------
    }
}
