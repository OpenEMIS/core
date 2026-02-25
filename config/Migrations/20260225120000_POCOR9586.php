<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9586 extends AbstractMigration
{
    public function up()
    {
        $this->backupTables();
        $this->disableTriggers();
        $this->addUniqueKeyAndCleanupDuplicates();
        $this->enableTriggers();
    }

    public function down()
    {
        $this->disableTriggers();
        $this->restoreTable();
        $this->enableTriggers();
    }

    private function backupTables()
    {
        $tables = ['summary_area_institution_grade_attendances'];

        foreach ($tables as $table) {
            $backup = 'z_9586_' . $table;
            if (!$this->hasTable($backup)) {
                $this->execute("CREATE TABLE `$backup` LIKE `$table`");
                $this->execute("INSERT INTO `$backup` SELECT * FROM `$table`");
            }
        }
    }

    private function restoreTable()
    {
        $tables = ['summary_area_institution_grade_attendances'];

        foreach ($tables as $table) {
            $backup = 'z_9586_' . $table;
            if ($this->hasTable($backup)) {
                $this->execute("DROP TABLE IF EXISTS `$table`");
                $this->execute("RENAME TABLE `$backup` TO `$table`");
            }
        }
    }

    private function disableTriggers()
    {
        // Disable the trigger on the summary table to allow the DDL operation to complete faster
        $this->execute('DROP TRIGGER IF EXISTS trigger_summary_area_institution_grade_attendances_update;');
    }

    private function enableTriggers()
    {
        // Recreate the trigger after schema changes
        $this->execute(<<<'SQL'
CREATE TRIGGER trigger_summary_area_institution_grade_attendances_update
BEFORE UPDATE ON summary_area_institution_grade_attendances
FOR EACH ROW
BEGIN
    SET NEW.present_total_count = NEW.present_female_count + NEW.present_male_count,
        NEW.absent_total_count = NEW.absent_female_count + NEW.absent_male_count,
        NEW.late_total_count = NEW.late_female_count + NEW.late_male_count;
END;
SQL
        );
    }

    private function addUniqueKeyAndCleanupDuplicates()
    {
        $tmp = 'tmp_9586_dedup';
        $this->execute("DROP TABLE IF EXISTS `$tmp`");
        // Create temp table with the unique key already in place
        $this->execute("CREATE TABLE `$tmp` LIKE `summary_area_institution_grade_attendances`");
        $this->execute("ALTER TABLE `$tmp` ADD UNIQUE KEY `uq_sai_ap_inst_grade_date`
        (academic_period_id, institution_id, education_grade_id, attendance_date)");
        // Copy rows — duplicates silently skipped
        $this->execute("INSERT IGNORE INTO `$tmp` SELECT * FROM `summary_area_institution_grade_attendances`");
        $this->execute("DROP TABLE `summary_area_institution_grade_attendances`");
        $this->execute("RENAME TABLE `$tmp` TO `summary_area_institution_grade_attendances`");
    }
}
