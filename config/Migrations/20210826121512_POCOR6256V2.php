<?php
use Migrations\AbstractMigration;

class POCOR6256V2 extends AbstractMigration
{
    public function up()
    {
        /** START: report_card_processes table changes */
        $this->execute('CREATE TABLE `zz_6256_v2_report_card_processes` LIKE `report_card_processes`');
        $this->execute('INSERT INTO `zz_6256_v2_report_card_processes` SELECT * FROM `report_card_processes`');
        $this->execute('ALTER TABLE `report_card_processes` DROP PRIMARY KEY, ADD PRIMARY KEY (`report_card_id`, `institution_class_id`,`student_id`) USING BTREE');
        /** END: report_card_processes table changes */
    }

    //rollback
    public function down()
    {
        /** START: report_card_processes table changes */
        $this->execute('DROP TABLE IF EXISTS `report_card_processes`');
        $this->execute('RENAME TABLE `zz_6256_report_card_processes` TO `report_card_processes`');
        /** END: report_card_processes table changes */
    }
}
