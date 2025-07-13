<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9253 extends AbstractMigration
{
    public function up(): void
    {
        // Step 1: Backup existing institutions table
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9253_institutions` LIKE `institutions`');
        $this->execute('INSERT INTO `zz_9253_institutions` SELECT * FROM `institutions`');

        // Step 2: Remove `fax` column safely
        $this->execute('ALTER TABLE `institutions` DROP COLUMN `fax`');
    }

    public function down(): void
    {
        // Step 1: Drop modified table if exists
        $this->execute('DROP TABLE IF EXISTS `institutions`');

        // Step 2: Restore original table from backup
        $this->execute('RENAME TABLE `zz_9253_institutions` TO `institutions`');
    }
}
