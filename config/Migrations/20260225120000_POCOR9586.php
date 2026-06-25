<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9586 extends AbstractMigration
{
    public function up()
    {
        $this->backupTables();

        $this->addUniqueKeyAndCleanupDuplicates();

    }

    public function down()
    {

        $this->restoreTable();

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
