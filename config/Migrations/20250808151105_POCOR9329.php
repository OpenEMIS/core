<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9329 extends AbstractMigration
{
    public function up(): void
    {
        // Backup table
        $this->execute('CREATE TABLE `zz_9329_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_9329_import_mapping` SELECT * FROM `import_mapping`');

        // Delete only the target row
        $this->execute("
            DELETE FROM `import_mapping`
            WHERE `model` = 'Institution.Institutions'
              AND `column_name` = 'fax'
        ");
    }

    public function down(): void
    {
        // Restore table from backup
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_9329_import_mapping` TO `import_mapping`');
    }
}
