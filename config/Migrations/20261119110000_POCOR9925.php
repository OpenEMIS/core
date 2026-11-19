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
 */
class POCOR9925 extends AbstractMigration
{
    private const CONFIG_BACKUP_TABLES = ['config_items', 'config_item_options'];

    public function up(): void
    {
        $this->backupConfigTables();

        // 1) Per-programme toggle
        if (!$this->table('education_programmes')->hasColumn('limit_student_admission_first_grade')) {
            $this->execute("ALTER TABLE `education_programmes`
                ADD COLUMN `limit_student_admission_first_grade` TINYINT(1) NOT NULL DEFAULT 0
                COMMENT '1=enabled,0=disabled' AFTER `same_grade_promotion`");
        }

        // 2) Delete the obsolete POCOR-9385 system-wide setting
        $this->execute("DELETE FROM `config_items` WHERE `code` = 'restrict_student_creation'");
        $this->execute("DELETE FROM `config_item_options` WHERE `option_type` = 'student_creation_toggle'");
    }

    public function down(): void
    {
        if ($this->table('education_programmes')->hasColumn('limit_student_admission_first_grade')) {
            $this->execute('ALTER TABLE `education_programmes` DROP COLUMN `limit_student_admission_first_grade`');
        }
        $this->restoreConfigTables();
    }

    private function backupConfigTables(): void
    {
        foreach (self::CONFIG_BACKUP_TABLES as $table) {
            $backup = 'z_9925_' . $table;
            if (!$this->hasTable($backup)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("CREATE TABLE `{$backup}` LIKE `{$table}`");
                $this->execute("INSERT INTO `{$backup}` SELECT * FROM `{$table}`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    private function restoreConfigTables(): void
    {
        foreach (self::CONFIG_BACKUP_TABLES as $table) {
            $backup = 'z_9925_' . $table;
            if ($this->hasTable($backup)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("DROP TABLE IF EXISTS `{$table}`");
                $this->execute("RENAME TABLE `{$backup}` TO `{$table}`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }
}
