<?php
use Migrations\AbstractMigration;

class POCOR6744 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6744_institution_report_cards` LIKE `institution_report_cards`');
        $this->execute('INSERT INTO `z_6744_institution_report_cards` SELECT * FROM `institution_report_cards`');

        $this->execute('CREATE TABLE `z_6744_institution_report_card_processes` LIKE `institution_report_card_processes`');
        $this->execute('INSERT INTO `z_6744_institution_report_card_processes` SELECT * FROM `institution_report_card_processes`'); 

        // Set foreign key checks to disabled
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        //alter foreign keys
        $this->execute("ALTER TABLE `institution_report_cards` DROP FOREIGN KEY `insti_repor_cards_fk_repor_card_id`; ALTER TABLE `institution_report_cards` ADD CONSTRAINT `insti_repor_cards_fk_repor_card_id` FOREIGN KEY (`report_card_id`) REFERENCES `openemis_core`.`profile_templates`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT");

        $this->execute("ALTER TABLE `institution_report_card_processes` DROP FOREIGN KEY `insti_repor_card_proce_fk_repor_card_id`; ALTER TABLE `institution_report_card_processes` ADD CONSTRAINT `insti_repor_card_proce_fk_repor_card_id` FOREIGN KEY (`report_card_id`) REFERENCES `openemis_core`.`profile_templates`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT");
        
        // Set foreign key checks to enabled
        $this->execute('SET SESSION FOREIGN_KEY_CHECKS=1;');
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_report_cards`');
        $this->execute('RENAME TABLE `z_6744_institution_report_cards` TO `institution_report_cards`');

        $this->execute('DROP TABLE IF EXISTS `institution_report_card_processes`');
        $this->execute('RENAME TABLE `z_6744_institution_report_card_processes` TO `institution_report_card_processes`');
    }
}
