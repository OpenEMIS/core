<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9629 extends AbstractMigration
{
    public function up(): void
    {
        // Backup table
        $this->execute('CREATE TABLE `zz_9629_report_cards` LIKE `report_cards`');
        $this->execute('INSERT INTO `zz_9629_report_cards` SELECT * FROM `report_cards`');

        // Add new columns
        $this->execute("
            ALTER TABLE `report_cards` 
            ADD COLUMN `regenerate_gpa` INT(11) DEFAULT 0 AFTER `overall_result`,
            ADD COLUMN `regenerate_cumulative_gpa` INT(11) DEFAULT 0 AFTER `regenerate_gpa`
        ");
    }

    public function down(): void
    {
        // Restore from backup
        $this->execute('DROP TABLE IF EXISTS `report_cards`');
        $this->execute('RENAME TABLE `zz_9629_report_cards` TO `report_cards`');
    }
}