<?php
declare(strict_types=1);

use Migrations\AbstractMigration;


class POCOR8898 extends AbstractMigration
{
        private array $archiveTables = [
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
                         'ReportCardStatuses.index',
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

        // Drop the archive table to allow clean re-testing
        if ($this->hasTable('institution_students_report_cards_archived')) { //POCOR-8898
            $this->execute('DROP TABLE IF EXISTS `institution_students_report_cards_archived`'); //POCOR-8898
        }

            }
}
