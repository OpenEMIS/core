<?php

use Migrations\AbstractMigration;

class POCOR8911 extends AbstractMigration
{
    private $batchSize = 50000; // Set batch size

    public function up()
    {
        // Create a backup table
        $this->execute('CREATE TABLE `zz_8911_security_users` LIKE `security_users`');
        $this->execute('INSERT INTO `zz_8911_security_users` SELECT * FROM `security_users`');

        // Batch update emails
        $this->batchUpdateDuplicates('email');

        // Batch update mobile numbers
        $this->batchUpdateDuplicates('mobile_number');

        // Add unique constraints
        $this->execute('ALTER TABLE `security_users` ADD UNIQUE (`email`)');
        $this->execute('ALTER TABLE `security_users` ADD UNIQUE (`mobile_number`)');
    }

    public function down()
    {
        // Drop modified table and restore from backup
        $this->execute('DROP TABLE IF EXISTS `security_users`');
        $this->execute('RENAME TABLE `zz_8911_security_users` TO `security_users`');
    }

    private function batchUpdateDuplicates($column)
    {
        do {
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
            $affectedRows = $this->getAdapter()->fetchRow("SELECT ROW_COUNT() AS affected")['affected'];
        } while ($affectedRows > 0);
    }
}
