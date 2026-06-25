<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9201 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
         // Backup table
         $this->execute('CREATE TABLE `zz_9201_report_cards` LIKE `report_cards`');
         $this->execute('INSERT INTO `zz_9201_report_cards` SELECT * FROM `report_cards`');
        $this->execute('ALTER TABLE `report_cards` ADD COLUMN `overall_result` int(11) NULL AFTER `education_grade_id`');
    }

    // rollback
    public function down()
    {
        
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `report_cards`');
        $this->execute('RENAME TABLE `zz_9201_report_cards` TO `report_cards`');

    }
}
