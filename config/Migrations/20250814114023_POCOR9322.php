<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9322 extends AbstractMigration
{
    public function up(): void
    {
        // Backup table
        $this->execute('CREATE TABLE `zz_9322_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_9322_import_mapping` SELECT * FROM `import_mapping`');

        // Update description and is_optional for username/password
        $this->execute("
            UPDATE `import_mapping`
            SET description = CONCAT(IFNULL(description, column_name), '*'),
                is_optional = 0
            WHERE LOWER(column_name) IN ('username', 'password')
              AND LOWER(model) = 'user.users'
        ");

    }

    public function down(): void
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        // Rename backup table back
        $this->execute('RENAME TABLE `zz_9322_import_mapping` TO `import_mapping`');
    }
}
