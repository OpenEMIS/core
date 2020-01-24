<?php

use Phinx\Migration\AbstractMigration;

class POCOR5051 extends AbstractMigration
{
    // commit
    public function up()
    {
        // backup the table
        $this->execute('CREATE TABLE `z_5051_report_cards` LIKE `report_cards`');
        $this->execute('INSERT INTO `z_5051_report_cards` SELECT * FROM `report_cards`');
        $this->execute('ALTER TABLE `report_cards` ADD COLUMN generate_start_date datetime default NULL AFTER end_date');   
        $this->execute('ALTER TABLE `report_cards` ADD COLUMN generate_end_date datetime default NULL AFTER generate_start_date');
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `report_cards`');
        $this->execute('RENAME TABLE `z_5051_report_cards` TO `report_cards`'); 
    }
}
