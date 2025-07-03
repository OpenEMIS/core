<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9156 extends AbstractMigration
{
    public function up(): void
    {
        // Step 1: Backup original table
        $this->execute('CREATE TABLE `zz_9156_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_9156_import_mapping` SELECT * FROM `import_mapping`');

        // Step 2: Shift up all orders >= 2 (excluding 'username') by +1 to make space for 'password'
        $this->execute("
            UPDATE import_mapping
            SET `order` = `order` + 1
            WHERE model = 'User.Users'
              AND column_name != 'username'
              AND `order` >= 2
        ");

        // Step 3: Insert 'password' at order = 2
        $this->execute("
            INSERT INTO import_mapping (
                model,
                column_name,
                description,
                `order`,
                is_optional,
                foreign_key,
                lookup_plugin,
                lookup_model,
                lookup_column
            )
            VALUES (
                'User.Users',
                'password',
                NULL,
                2,
                0,
                0,
                NULL,
                NULL,
                NULL
            )
        ");
    }

    public function down(): void
    {
        $this->execute('DELETE FROM `import_mapping`');
        $this->execute('INSERT INTO `import_mapping` SELECT * FROM `zz_9156_import_mapping`');

        // Step 5: Drop backup table
        $this->execute('DROP TABLE IF EXISTS `zz_9156_import_mapping`');
    }
}
