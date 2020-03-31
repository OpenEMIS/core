<?php

use Cake\Utility\Text;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;

class POCOR4958 extends AbstractMigration
{
   
    public function up()
    {
       $this->execute('CREATE TABLE `zz_4958_institution_classes` LIKE `institution_classes`');
       $this->execute('INSERT INTO `zz_4958_institution_classes` SELECT * FROM `institution_classes`');
	   
	   $InstitutionClassesTable = TableRegistry::get('Institution.InstitutionClasses');
	   $InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
	   
	   $InstitutionClasses = $InstitutionClassesTable->find()
			->hydrate(false)
            ->toArray();
		$counter = 0;
		foreach($InstitutionClasses as $InstitutionClass){			
			$countMale = $InstitutionClassStudentsTable->getMaleCountByClass($InstitutionClass['id']);
            $countFemale = $InstitutionClassStudentsTable->getFemaleCountByClass($InstitutionClass['id']);	
			
			$InstitutionClassesTable->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $InstitutionClass['id']]);	
			
			$counter++;
			
			if($counter == 100){
				sleep(30);
				$counter = 0;
			}
			
		}		
    }

    public function down()
    {
      $this->execute('DROP TABLE IF EXISTS `institution_classes`');
      $this->execute('RENAME TABLE `zz_4958_institution_classes` TO `institution_classes`');  
    }
}
