<?php
use Migrations\AbstractMigration;

class POCOR6782 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `zz_6782_report_card_processes` LIKE `report_card_processes`');
        $this->execute('INSERT INTO `zz_6782_report_card_processes` SELECT * FROM `report_card_processes`');

        /** UPDATE OpenEMIS Core report_queries */
        $this->execute('ALTER TABLE `report_card_processes` ADD `modified` DATETIME NULL DEFAULT NULL AFTER `academic_period_id`;');
        
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `report_card_processes`');
        $this->execute('RENAME TABLE `zz_6782_report_card_processes` TO `report_card_processes`');
    }
}
