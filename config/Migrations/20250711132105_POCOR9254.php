<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9254 extends AbstractMigration
{
    public function up(): void
    {
        // Backup table
        $this->execute('CREATE TABLE `zz_9254_openemis_temps` LIKE `openemis_temps`');
        $this->execute('INSERT INTO `zz_9254_openemis_temps` SELECT * FROM `openemis_temps`');
        $this->execute('ALTER TABLE `openemis_temps` RENAME TO `security_users_openemis_no`');
    }

    public function down(): void
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `openemis_temps`');
        $this->execute('DROP TABLE IF EXISTS `security_users_openemis_no`');
        // Rename back the backup to the original
        $this->execute('RENAME TABLE `zz_9254_openemis_temps` TO `openemis_temps`');
    }
}
