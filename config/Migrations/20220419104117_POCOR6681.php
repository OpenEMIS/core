<?php
use Migrations\AbstractMigration;

class POCOR6681 extends AbstractMigration
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
        $this->execute('DROP TABLE IF EXISTS `zz_6681_meal_received`');
        $this->execute('CREATE TABLE `zz_6681_meal_received` LIKE `meal_received`');
        $this->execute('INSERT INTO `zz_6681_meal_received` SELECT * FROM `meal_received`');

        $checkDataExist = $this->query("SELECT * FROM meal_received WHERE code = 'none'");

        $data = $checkDataExist->fetchAll();
		if(empty($data)){
            $this->insert('meal_received', [
                'code' => 'None',
                'name' => 'None'
            ]);
        }
    }

    public function down()
    {   
	    // meal_received
        $this->execute('DROP TABLE IF EXISTS `meal_received`');
        $this->execute('RENAME TABLE `zz_6681_meal_received` TO `meal_received`');
		
    }
}
