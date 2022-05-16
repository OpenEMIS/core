<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

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
        //Back up
        //meal_received
        $this->execute('DROP TABLE IF EXISTS `zz_6681_meal_received`');
        $this->execute('CREATE TABLE `zz_6681_meal_received` LIKE `meal_received`');
        $this->execute('INSERT INTO `zz_6681_meal_received` SELECT * FROM `meal_received`');

        //Back up
        //import_mapping
        $this->execute('DROP TABLE IF EXISTS `zz_6681_import_mapping`');
        $this->execute('CREATE TABLE `zz_6681_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_6681_import_mapping` SELECT * FROM `import_mapping`');

        //Back up
        //institution_meal_programmes
        $this->execute('DROP TABLE IF EXISTS `zz_6681_institution_meal_programmes`');
        $this->execute('CREATE TABLE `zz_6681_institution_meal_programmes` LIKE `institution_meal_programmes`');
        $this->execute('INSERT INTO `zz_6681_institution_meal_programmes` SELECT * FROM `institution_meal_programmes`');

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

        // For point 2 validation POCOR-6681 delete existing records 
        $this->execute('TRUNCATE institution_meal_programmes');

        $MealProgrammes = TableRegistry::get('meal_programmes');
        $MealInstitutionProgrammes = TableRegistry::get('institution_meal_programmes');

        // Delete existing records For Export 
        $MealProgrammes = $MealProgrammes
                ->find('all')->select(['id'])
                ->toArray();
        if(!empty($MealProgrammes)){
            $MealProgrammesArr = [];
            foreach ($MealProgrammes as $mpkey => $mpval) {
                $MealProgrammesArr[] = $mpval['id'];
            }
            if(!empty($MealProgrammesArr)){
                $InstitutionProgrammes = $MealInstitutionProgrammes
                    ->find('all')->select(['id'])
                    ->where([
                        $MealInstitutionProgrammes->aliasField('meal_programmes_id NOT IN') => $MealProgrammesArr
                    ])->toArray();
                if(!empty($InstitutionProgrammes)){
                    foreach ($InstitutionProgrammes as $key => $Programmes) { 
                        $MealInstitutionProgrammes->delete($Programmes);
                    }
                }
            }
            
        }
    }

    public function down()
    {   
	    // meal_received
        $this->execute('DROP TABLE IF EXISTS `meal_received`');
        $this->execute('RENAME TABLE `zz_6681_meal_received` TO `meal_received`');

        $this->execute('DROP TABLE IF EXISTS `meal_programmes`');
        $this->execute('RENAME TABLE `zz_6681_meal_programmes` TO `meal_programmes`');

        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_6681_import_mapping` TO `import_mapping`');

        $this->execute('DROP TABLE IF EXISTS `institution_meal_programmes`');
        $this->execute('RENAME TABLE `zz_6681_institution_meal_programmes` TO `institution_meal_programmes`');
		
    }
}
