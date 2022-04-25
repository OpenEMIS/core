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

        $this->execute("
        UPDATE `import_mapping` SET `description` = 'OpenEMIS ID', `foreign_key` = '2', `lookup_plugin` = 'Security', `lookup_model` = 'Users', `lookup_column` = 'openemis_no' WHERE `import_mapping`.`model` = 'Institution.InstitutionMealStudents' AND `import_mapping`.`column_name` = 'OpenEMIS_ID'
        ");
        $this->execute("
        ALTER TABLE `institution_meal_programmes` CHANGE `date_received` `date_received` DATE NOT NULL
        ");
    }

    public function down()
    {   
	    // meal_received
        $this->execute('DROP TABLE IF EXISTS `meal_received`');
        $this->execute('RENAME TABLE `zz_6681_meal_received` TO `meal_received`');
		
    }
}
