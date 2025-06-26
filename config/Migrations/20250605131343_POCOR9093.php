<?php

use Migrations\AbstractMigration;
use Cake\Log\Log;

class POCOR9093 extends AbstractMigration
{
    public function up(): void
    {
//        return;
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // Backup tables before any changes
        $this->backupTable('summary_student_attendances', 'z_9093_summary_student_attendances');
        $this->backupTable('report_queries', 'z_9093_report_queries');

        // Apply indexes
        $table = $this->table('summary_student_attendances');

        if (!$table->hasIndex(['institution_id'])) {
            $table->addIndex(['institution_id'], ['name' => 'idx_institution_id']);
        }
        if (!$table->hasIndex(['academic_period_id'])) {
            $table->addIndex(['academic_period_id'], ['name' => 'idx_academic_period_id']);
        }
        if (!$table->hasIndex(['education_grade_id'])) {
            $table->addIndex(['education_grade_id'], ['name' => 'idx_education_grade_id']);
        }
        if (!$table->hasIndex(['attendance_date'])) {
            $table->addIndex(['attendance_date'], ['name' => 'idx_attendance_date']);
        }
        if (!$table->hasIndex(['class_id'])) {
            $table->addIndex(['class_id'], ['name' => 'idx_class_id']);
        }
        if (!$table->hasIndex(['period_id'])) {
            $table->addIndex(['period_id'], ['name' => 'idx_period_id']);
        }
        if (!$table->hasIndex(['subject_id'])) {
            $table->addIndex(['subject_id'], ['name' => 'idx_subject_id']);
        }

        $table->update();

        // Replace table reference in report_queries
        $rows = $this->fetchAll("SELECT id, name, query_sql FROM `report_queries` WHERE `name` LIKE '%report_student_attendance_summary%'");
        Log::info(print_r($rows, true));
        foreach ($rows as $row) {
            Log::info(print_r($row, true));
            $querySql = str_replace('report_student_attendance_summary', 'summary_student_attendances', $row['query_sql']);
            $nameSql = str_replace('report_student_attendance_summary', 'summary_student_attendances', $row['name']);
            $up = $this->execute(
                "UPDATE report_queries SET query_sql = :sql, name = :name WHERE id = :id",
                ['sql' => $querySql, 'name' => $nameSql, 'id' => $row['id']]
            );
//            Log::info(print_r($up, true));
        }

        $rows = $this->fetchAll("SELECT id, name, query_sql FROM report_queries WHERE name LIKE '%summary_student_attendances%'");

        foreach ($rows as $row) {
            Log::info(print_r($row, true));
        }

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // Restore both modified tables
        $this->restoreTable('summary_student_attendances', 'z_9093_summary_student_attendances');
        $this->restoreTable('report_queries', 'z_9093_report_queries');

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function backupTable(string $original, string $backup): void
    {
        if (!$this->hasTable($backup)) {
            $this->execute("CREATE TABLE `$backup` LIKE `$original`");
            $this->execute("INSERT INTO `$backup` SELECT * FROM `$original`");
            Log::info("Backed up `$original` to `$backup`");
        }
    }

    private function restoreTable(string $original, string $backup): void
    {
        if ($this->hasTable($backup)) {
            $this->execute("DROP TABLE IF EXISTS `$original`");
            $this->execute("CREATE TABLE `$original` LIKE `$backup`");
            $this->execute("INSERT INTO `$original` SELECT * FROM `$backup`");
            Log::info("Restored `$original` from `$backup`");
        }
    }
}
