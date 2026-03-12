<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9599 extends AbstractMigration
{
    public function up(): void
    {
        // Backup table
        $this->execute('CREATE TABLE `zz_9599_assessment_periods` LIKE `assessment_periods`');
        $this->execute('INSERT INTO `zz_9599_assessment_periods` SELECT * FROM `assessment_periods`');

        // Update column precision to prevent rounding of weight values
        $this->execute('ALTER TABLE `assessment_periods` 
                        MODIFY `weight` DECIMAL(6,5) NULL DEFAULT 0.00000');
    }

    public function down(): void
    {
        // Revert column precision to original definition
        $this->execute('ALTER TABLE `assessment_periods` 
                        MODIFY `weight` DECIMAL(6,2) NULL DEFAULT 0.00');

        // Drop backup table
        $this->execute('DROP TABLE IF EXISTS `zz_9599_assessment_periods`');
    }
}