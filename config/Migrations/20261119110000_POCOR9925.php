<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * POCOR-9925: "Limit Student Admission to First Grade" moves from a single system-wide
 * config item (Administration > System Configurations > Add New Student, code
 * `restrict_student_creation`, added by POCOR-9385) to a per-programme setting on
 * Administration > Education Structure > Programmes.
 *
 *  - education_programmes.limit_student_admission_first_grade (0 = Disabled, 1 = Enabled)
 *  - Excluded Security Roles are out of scope (reporter confirmed); applies to ALL users, super admin included (no bypass).
 *
 * The old POCOR-9385 config row and its dropdown options are deleted; nothing is carried
 * over. All programmes start as Disabled (column default 0).
 *
 * Only the deleted rows are backed up (z_9925_*), so down() re-inserts just those rows and
 * never overwrites config changes made after this migration ran.
 */
class POCOR9925 extends AbstractMigration
{
    private const BACKUP_CONFIG_ITEMS = 'z_9925_config_items';
    private const BACKUP_CONFIG_ITEM_OPTIONS = 'z_9925_config_item_options';

    private const CONFIG_ITEMS_WHERE = "`code` = 'restrict_student_creation'";
    private const CONFIG_ITEM_OPTIONS_WHERE = "`option_type` = 'student_creation_toggle'";

    public function up(): void
    {
        // 1) Back up only the rows that are about to be deleted
        $this->backupRows('config_items', self::BACKUP_CONFIG_ITEMS, self::CONFIG_ITEMS_WHERE);
        $this->backupRows('config_item_options', self::BACKUP_CONFIG_ITEM_OPTIONS, self::CONFIG_ITEM_OPTIONS_WHERE);

        // 2) Per-programme toggle
        if (!$this->table('education_programmes')->hasColumn('limit_student_admission_first_grade')) {
            $this->execute("ALTER TABLE `education_programmes`
                ADD COLUMN `limit_student_admission_first_grade` TINYINT(1) NOT NULL DEFAULT 0
                COMMENT '1=enabled,0=disabled' AFTER `same_grade_promotion`");
        }

        // 3) Delete the obsolete POCOR-9385 system-wide setting
        $this->execute('DELETE FROM `config_items` WHERE ' . self::CONFIG_ITEMS_WHERE);
        $this->execute('DELETE FROM `config_item_options` WHERE ' . self::CONFIG_ITEM_OPTIONS_WHERE);
    }

    public function down(): void
    {
        if ($this->table('education_programmes')->hasColumn('limit_student_admission_first_grade')) {
            $this->execute('ALTER TABLE `education_programmes` DROP COLUMN `limit_student_admission_first_grade`');
        }

        // Re-insert only the deleted rows; all other config rows are left untouched
        $this->restoreRows('config_item_options', self::BACKUP_CONFIG_ITEM_OPTIONS);
        $this->restoreRows('config_items', self::BACKUP_CONFIG_ITEMS);
    }

    private function backupRows(string $table, string $backup, string $where): void
    {
        $this->execute("DROP TABLE IF EXISTS `{$backup}`");
        $this->execute("CREATE TABLE `{$backup}` LIKE `{$table}`");
        $this->execute("INSERT INTO `{$backup}` SELECT * FROM `{$table}` WHERE {$where}");
    }

    private function restoreRows(string $table, string $backup): void
    {
        if ($this->hasTable($backup)) {
            $this->execute("INSERT IGNORE INTO `{$table}` SELECT * FROM `{$backup}`");
            $this->execute("DROP TABLE `{$backup}`");
        }
    }
}
