<?php

use Migrations\AbstractMigration;
use Cake\Log\Log;

class POCOR8911 extends AbstractMigration
{
    private $batchSize = 50000; // Set batch size

    public function up()
    {
        // Create a backup table
        $this->execute('CREATE TABLE `zz_8911_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `zz_8911_security_users` SELECT * FROM `security_users`');

        // Batch update emails (to remove duplicates)
        $this->batchUpdateDuplicates('email');

        // Batch update mobile numbers (to remove duplicates)
        $this->batchUpdateDuplicates('mobile_number');

        // Add UNIQUE constraints (ensures no duplicates and also acts as an index)
        $this->execute('ALTER TABLE `security_users` ADD UNIQUE INDEX `unique_email` (`email`)');
        $this->execute('ALTER TABLE `security_users` ADD UNIQUE INDEX `unique_mobile` (`mobile_number`)');

        Log::write('debug', 'Unique constraints added on email and mobile_number');
    }

    public function down()
    {
        // Remove unique constraints
        $this->execute('ALTER TABLE `security_users` DROP INDEX `unique_email`');
        $this->execute('ALTER TABLE `security_users` DROP INDEX `unique_mobile`');

        Log::write('debug', 'Unique constraints removed on email and mobile_number');

        // Archive new records added after migration
        $this->execute('CREATE TABLE `archived_new_users` AS 
            SELECT * FROM `security_users` 
            WHERE id NOT IN (SELECT id FROM `zz_8911_security_users`)');

        // Drop modified table and restore from backup
        $this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `zz_8911_security_users` TO `security_users`');

        // Merge archived new records if any exist
        $this->execute('INSERT IGNORE INTO `security_users` 
            SELECT * FROM `archived_new_users`');
        $this->execute('DROP TABLE `archived_new_users`');

        Log::write('debug', 'Restored security_users table from backup');
    }

    private function batchUpdateDuplicates($column)
    {
        // Get the database connection
        $connection = \Cake\Datasource\ConnectionManager::get('default');

        do {
            // Start the transaction
            $connection->begin();
            try {
                $this->execute("
                    UPDATE security_users 
                    SET $column = NULL
                    WHERE id IN (
                        SELECT id FROM (
                            SELECT id FROM security_users 
                            WHERE $column IN (
                                SELECT $column FROM security_users 
                                GROUP BY $column HAVING COUNT($column) > 1
                            )
                            AND id NOT IN (
                                SELECT MIN(id) FROM security_users GROUP BY $column HAVING COUNT($column) > 1
                            )
                            LIMIT {$this->batchSize}
                        ) AS batch
                    )
                ");

                $affectedRows = $connection->execute("SELECT ROW_COUNT() AS affected")->fetch('assoc')['affected'];

                // Log batch update results
                Log::write('debug', "$affectedRows rows updated for column: $column");

                // Commit transaction
                $connection->commit();
            } catch (\Exception $e) {
                // Rollback on error
                $connection->rollback();
                Log::write('error', "Batch update failed for $column: " . $e->getMessage());
                break; // Exit the loop on failure
            }
        } while ($affectedRows > 0);
    }

}
