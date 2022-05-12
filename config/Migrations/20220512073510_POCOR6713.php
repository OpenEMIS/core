<?php
use Migrations\AbstractMigration;

class POCOR6713 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6713_meal_programmes` LIKE `meal_programmes`');
        $this->execute('INSERT INTO `zz_6713_meal_programmes` SELECT * FROM `meal_programmes`');

        $this->execute("ALTER TABLE `meal_programmes` CHANGE `amount` `amount` DECIMAL(5,2) NOT NULL;");
    }

    public function down()
    {   
	    // meal_received
        $this->execute('DROP TABLE IF EXISTS `meal_programmes`');
        $this->execute('RENAME TABLE `zz_6713_meal_programmes` TO `meal_programmes`');
		
    }
}
