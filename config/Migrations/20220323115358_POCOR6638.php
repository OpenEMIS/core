<?php
use Migrations\AbstractMigration;

class POCOR6638 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_6638_institution_report_cards` LIKE `institution_report_cards`');
        $this->execute('INSERT INTO `z_6638_institution_report_cards` SELECT * FROM `institution_report_cards`');
		
		$this->execute("ALTER TABLE `institution_report_cards` CHANGE `report_card_id` `report_card_id` INT(11) NOT NULL COMMENT 'links to report_cards.id'");
		$this->execute("ALTER TABLE `institution_report_cards` CHANGE `institution_id` `institution_id` INT(11) NOT NULL COMMENT 'links to institutions.id'");
     }
	 
	 
	 // rollback
    public function down()
    {
       // meal_programmes
       $this->execute('DROP TABLE IF EXISTS `institution_report_cards`');
       $this->execute('RENAME TABLE `z_6638_institution_report_cards` TO `institution_report_cards`');
    }
	 
	 
	 
    
}
