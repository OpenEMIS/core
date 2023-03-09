<?php
use Migrations\AbstractMigration;

class POCOR6916 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_6916_report_cards`');
        $this->execute('CREATE TABLE `zz_6916_report_cards` LIKE `report_cards`');
        $this->execute('INSERT INTO `zz_6916_report_cards` SELECT * FROM `report_cards`');

        //insert new column
        $this->execute('ALTER TABLE `report_cards` ADD `pdf_page_number` INT NULL AFTER `excel_template`');
        $this->execute("ALTER TABLE `report_cards` CHANGE `pdf_page_number` `pdf_page_number` INT(11) NULL");
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `report_cards`');
        $this->execute('RENAME TABLE `zz_6916_report_cards` TO `report_cards`');
    }
}
