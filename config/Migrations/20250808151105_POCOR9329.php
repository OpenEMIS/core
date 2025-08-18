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

        // Get fax order dynamically 
        $faxOrder = $this->fetchRow("
            SELECT `order` FROM `import_mapping`
            WHERE `model` = 'Institution.Institutions'
              AND `column_name` = 'fax'
        ");

        if ($faxOrder) {
            $orderNum = (int) $faxOrder['order'];

            // Delete fax row
            $this->execute("
                DELETE FROM `import_mapping`
                WHERE `model` = 'Institution.Institutions'
                  AND `column_name` = 'fax'
            ");

            // Shift all rows after fax order by -1
            $this->execute("
                UPDATE `import_mapping`
                SET `order` = `order` - 1
                WHERE `model` = 'Institution.Institutions'
                  AND `order` > {$orderNum}
            ");
        }
    }

    public function down(): void
    {
        // Restore from backup
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_9329_import_mapping` TO `import_mapping`');

        // Drop backup table if it somehow still exists (cleanup)
        $this->execute('DROP TABLE IF EXISTS `zz_9329_import_mapping`');
    }
}
