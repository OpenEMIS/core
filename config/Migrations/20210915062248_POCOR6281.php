<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Utility\Security;
use Cake\Utility\Hash;

class POCOR6281 extends AbstractMigration
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
        //back up table
        $this->execute('CREATE TABLE `zz_6281_institution_grades` LIKE `institution_grades`');
        $this->execute('INSERT INTO `zz_6281_institution_grades` SELECT * FROM `institution_grades`');

        // Create tables
        $this->execute("CREATE TABLE IF NOT EXISTS `data_management_copy` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `from_academic_period` int(11),
            `to_academic_period` int(11) ,
            `features` varchar(200) COLLATE utf8mb4_unicode_ci ,
            `created_user_id` int(11),
            `created` date ,
            PRIMARY KEY (`id`)
          )");

        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $EducationLevels = TableRegistry::get('Education.EducationLevels');
        $EducationCycles = TableRegistry::get('Education.EducationCycles');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');

        $InstitutionGradesdata = $InstitutionGrades
                ->find('all')
                ->toArray();
            
        if(!empty($InstitutionGradesdata)){
            foreach($InstitutionGradesdata AS $InstitutionGradesValue){
                $EducationGradesData = $EducationGrades
                                    ->find()
                                    ->where([$EducationGrades->aliasField('id') =>$InstitutionGradesValue['education_grade_id']])
                                    ->All()
                                    ->toArray();
                
                $EducationProgrammesData = $EducationProgrammes
                                    ->find()
                                    ->where([$EducationProgrammes->aliasField('id') =>$EducationGradesData[0]['education_programme_id']])
                                    ->All()
                                    ->toArray();
    
                $EducationCyclesData = $EducationCycles
                                    ->find()
                                    ->where([$EducationCycles->aliasField('id') =>$EducationProgrammesData[0]['education_cycle_id']])
                                    ->All()
                                    ->toArray();
                
                $EducationLevelsData = $EducationLevels
                ->find()
                ->where(['id' => $EducationCyclesData[0]['education_level_id']])
                ->toArray();
    
                $EducationSystemsData = $EducationSystems
                ->find()
                ->where(['id' => $EducationLevelsData[0]['education_system_id']])
                ->first();
    
                $AcademicPeriodsData = $AcademicPeriods
                        ->find()
                        ->select(['start_date', 'start_year'])
                        ->where(['id' => $EducationSystemsData['academic_period_id']])
                        ->first();

                if(!empty($AcademicPeriodsData)){
                    $InstitutionGrades->updateAll(
                        ['start_date' => $AcademicPeriodsData['start_date'], 'start_year' => $AcademicPeriodsData['start_year']],    //field
                        ['education_grade_id' => $InstitutionGradesValue['education_grade_id'], 'institution_id'=> $InstitutionGradesValue['institution_id']] //condition
                    );
                }
            }
        }
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_grades`');
        $this->execute('RENAME TABLE `zz_6281_institution_grades` TO `institution_grades`');
        $this->execute('DROP TABLE IF EXISTS `data_management_copy`');
        
    }
}
