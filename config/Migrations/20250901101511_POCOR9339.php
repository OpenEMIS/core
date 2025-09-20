<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9339 extends AbstractMigration
{
    public function up(): void
    {
        // Step 1: Backup existing institutions table
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9339_institution_contact_persons` LIKE `institution_contact_persons`');
        $this->execute('INSERT INTO `zz_9339_institution_contact_persons` SELECT * FROM `institution_contact_persons`');

        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9339_examination_centres` LIKE `examination_centres`');
        $this->execute('INSERT INTO `zz_9339_examination_centres` SELECT * FROM `examination_centres`');

        // Step 2: Remove `fax` column safely
        $this->execute('ALTER TABLE `institution_contact_persons` DROP COLUMN `fax`');
        $this->execute('ALTER TABLE `examination_centres` DROP COLUMN `fax`');
    }

    public function down(): void
    {
        // Step 1: Drop modified table if exists
        $this->execute('DROP TABLE IF EXISTS `institution_contact_persons`');
        $this->execute('DROP TABLE IF EXISTS `examination_centres`');

        // Step 2: Restore original table from backup
        $this->execute('RENAME TABLE `zz_9339_institution_contact_persons` TO `institution_contact_persons`');
        $this->execute('RENAME TABLE `zz_9339_examination_centres` TO `examination_centres`');
    } 
}
