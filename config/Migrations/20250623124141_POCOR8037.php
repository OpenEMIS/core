<?php

use Migrations\AbstractMigration;
use Cake\Log\Log;

class POCOR8037 extends AbstractMigration
{
    // Dependency chain: lands -> buildings -> floors -> rooms.
    protected $my_tables = [
        'institution_rooms' => 'room_status_id',
        'institution_floors' => 'floor_status_id',
        'institution_buildings' => 'building_status_id',
        'institution_lands' => 'land_status_id'
    ];

    public function up(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        Log::info("Disabled foreign key checks.");

        // Create backup tables
        foreach ($this->my_tables as $table => $statusField) {
            if (!$this->hasTable('z_8037_' . $table)) {
                $this->execute("CREATE TABLE `z_8037_$table` LIKE `$table`");
                $this->execute("INSERT IGNORE INTO `z_8037_$table` SELECT * FROM `$table`");
                Log::info("Created backup table for: $table");
            }
        }

        // Remove foreign key and index if exists, then remove column academic_period_id
        foreach ($this->my_tables as $table => $statusField) {
            $this->removeColumnWithConstraints($table, 'academic_period_id');
        }

        // Keep only the latest data for each code
        foreach ($this->my_tables as $table => $statusField) {
            $this->keepLatestData($table);
            Log::info("Kept only the latest data for table: $table");
        }

        // Update foreign key references so that any removed rows are re-mapped.
        // Process in dependency order so parent tables are updated first.
        $dependencyOrder = ['institution_lands', 'institution_buildings', 'institution_floors', 'institution_rooms'];
        foreach ($dependencyOrder as $table) {
            $this->updateReferences($table);
            Log::info("Updated foreign key references for table: $table");
        }

        // Update start_date and start_year in the kept records to the earliest start_date from the backup.
        foreach ($this->my_tables as $table => $statusField) {
            $this->updateStartDates($table);
            Log::info("Updated start dates for table: $table");
        }

        // Make end_date and end_year nullable
        foreach ($this->my_tables as $table => $statusField) {
            $this->makeColumnsNullable($table, ['end_date', 'end_year']);
            Log::info("Made end_date and end_year nullable for table: $table");
        }

        // Clear end_date and end_year if the infrastructure is active
        foreach ($this->my_tables as $table => $statusField) {
            $this->clearEndDateAndYear($table, $statusField);
            Log::info("Cleared end_date and end_year for active records in table: $table");
        }

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        Log::info("Re-enabled foreign key checks.");
    }

    private function removeColumnWithConstraints(string $table, string $column): void
    {
        // Check if the column exists
        $columnExists = $this->fetchRow("
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_NAME = '$table'
              AND COLUMN_NAME = '$column'
        ");

        if ($columnExists) {
            // Fetch foreign keys
//            $foreignKeys = $this->fetchAll("
//                SELECT CONSTRAINT_NAME
//                FROM information_schema.KEY_COLUMN_USAGE
//                WHERE TABLE_NAME = '$table'
//                  AND COLUMN_NAME = '$column'
//                  AND CONSTRAINT_NAME != 'PRIMARY'
//            ");
//
//            // Drop each foreign key if it exists
//            foreach ($foreignKeys as $foreignKey) {
//                $constraintName = $foreignKey['CONSTRAINT_NAME'];
//                $checkConstraint = $this->fetchRow("
//                    SELECT CONSTRAINT_NAME
//                    FROM information_schema.TABLE_CONSTRAINTS
//                    WHERE TABLE_NAME = '$table'
//                      AND CONSTRAINT_NAME = '$constraintName'
//                      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
//                ");
//
//                if (!empty($checkConstraint)) {
//                    $this->execute("ALTER TABLE `$table` DROP FOREIGN KEY `$constraintName`");
//                    Log::info("Dropped foreign key: $constraintName from table: $table");
//                } else {
//                    Log::warning("Foreign key does not exist: $constraintName in table: $table");
//                }
//            }

            // Drop indexes
            $indexes = $this->fetchAll("SHOW INDEX FROM `$table` WHERE Column_name = '$column'");
            foreach ($indexes as $index) {
                $this->execute("ALTER TABLE `$table` DROP INDEX `{$index['Key_name']}`");
                Log::info("Dropped index: {$index['Key_name']} from table: $table");
            }

            // Remove column
            $this->table($table)->removeColumn($column)->update();
            Log::info("Removed column: $column from table: $table");
        } else {
            Log::warning("Column does not exist: $column in table: $table. Skipping constraint and index removal.");
        }
    }

    private function keepLatestData(string $table): void
    {
        // Delete rows that are not the latest per code.
        // When created dates are equal (e.g., from copy-paste), the row with the later start_date is kept.
        $this->execute("
            DELETE t1 FROM `$table` t1
            INNER JOIN `$table` t2
                ON t1.code = t2.code
            WHERE t1.created < t2.created
               OR (t1.created = t2.created AND t1.start_date < t2.start_date)
        ");
    }

    /**
     * Update foreign key references pointing to records in $table.
     *
     * Uses the backup table to map old (removed) primary keys to the new one kept in $table,
     * then updates any referencing tables (including external ones).
     */
    private function updateReferences(string $table): void
    {
        $backupTable = "z_8037_$table";

        // Build a temporary mapping table of old_id to new_id based on code.
        $this->execute("DROP TEMPORARY TABLE IF EXISTS tmp_mapping_$table");
        $this->execute("
            CREATE TEMPORARY TABLE tmp_mapping_$table AS
            SELECT b.id AS old_id, t.id AS new_id
            FROM `$backupTable` b
            JOIN `$table` t ON b.code = t.code
            WHERE b.id <> t.id
        ");
        Log::info("Created temporary mapping table for: $table");

        // Find all foreign keys referencing this table and update them using the mapping.
        $foreignKeys = $this->fetchAll("
            SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME = '$table'
              AND TABLE_SCHEMA = DATABASE()
        ");

        foreach ($foreignKeys as $fk) {
            $refTable = $fk['TABLE_NAME'];
            $refColumn = $fk['COLUMN_NAME'];
            $this->execute("
                UPDATE `$refTable` r
                JOIN tmp_mapping_$table m ON r.`$refColumn` = m.old_id
                SET r.`$refColumn` = m.new_id
            ");
            Log::info("Updated references in table: $refTable for column: $refColumn");
        }
    }

    /**
     * Update the start_date and start_year in the deduplicated table to reflect
     * the earliest start_date recorded for each code in the backup table.
     */
    private function updateStartDates(string $table): void
    {
        $backupTable = "z_8037_$table";
        $this->execute("
            UPDATE `$table` t
            JOIN (
                SELECT code,
                       MIN(start_date) AS earliest_start_date,
                       YEAR(MIN(start_date)) AS earliest_start_year
                FROM `$backupTable`
                GROUP BY code
            ) sub ON t.code = sub.code
            SET t.start_date = sub.earliest_start_date,
                t.start_year = sub.earliest_start_year
        ");
    }

    private function makeColumnsNullable(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            // Use the column type from the backup table to prevent issues
            $columnType = $this->fetchRow("SHOW COLUMNS FROM `z_8037_$table` LIKE '$column'")['Type'];
            $this->execute("ALTER TABLE `$table` MODIFY `$column` $columnType NULL DEFAULT NULL");
            Log::info("Made column: $column nullable in table: $table");
        }
    }

    private function clearEndDateAndYear(string $table, string $statusField): void
    {
        $this->execute("UPDATE `$table` SET end_date = NULL, end_year = NULL WHERE $statusField = 1");
    }

    public function down(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        Log::info("Disabled foreign key checks for rollback.");

        // Restore data from backup tables if they exist
        foreach ($this->my_tables as $table => $statusField) {
            $this->restoreTableFromBackup($table, "z_8037_$table");
        }

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        Log::info("Re-enabled foreign key checks after rollback.");
    }

    private function restoreTableFromBackup(string $table, string $backupTable): void
    {
        if ($this->hasTable($backupTable)) {
            $this->execute("DROP TABLE IF EXISTS `$table`");
            $this->execute("RENAME TABLE `$backupTable` TO `$table`");
            Log::info("Restored table: $table from backup: $backupTable");
        }
    }
}
