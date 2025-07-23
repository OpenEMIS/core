<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR5199 extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_5199_institutions` LIKE `institutions`');
        $this->execute('INSERT INTO `zz_5199_institutions` SELECT * FROM `institutions`');

        $table = $this->table('institutions');
        $table->addColumn('vision', 'string', [
            'limit' => 250,
            'null' => true,
            'default' => null,
            'after' => 'institution_gender_id' 
        ]);
        $table->addColumn('mission', 'string', [
            'limit' => 250,
            'null' => true,
            'default' => null,
            'after' => 'vision'
        ]);
        $table->update();
    }

    public function down(): void
    {
        // Step 1: Drop modified table
        $this->execute('DROP TABLE IF EXISTS `institutions`');

        // Step 2: Restore original from backup
        $this->execute('RENAME TABLE `zz_5199_institutions` TO `institutions`');
    }
}
