<?php


use Phinx\Migration\AbstractMigration;

class POCOR8969 extends AbstractMigration
{

    public function up(): void
    {
        $this->updateCreateZTable();
        $this->updateDateFields();

    }

    private function updateCreateZTable(): void
    {
        if (!$this->hasTable('z_8969_report_cards')) {
            $this->execute('CREATE TABLE `z_8969_report_cards` LIKE `report_cards`');
            $this->execute('INSERT IGNORE INTO `z_8969_report_cards` SELECT * FROM `report_cards`');
        }
    }

    private function updateDateFields(): void
    {


        $this->query("ALTER TABLE report_cards
MODIFY generate_start_date DATE,
MODIFY generate_end_date DATE;
");

    }

    public function down(): void
    {
        if ($this->hasTable('z_8969_report_cards')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `report_cards`');
            $this->execute('RENAME TABLE `z_8969_report_cards` TO `report_cards`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
