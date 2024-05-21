<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR7367 extends AbstractMigration
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

        $this->execute('CREATE TABLE `zz_7367_institution_cases` LIKE `institution_cases`');
        $this->execute('INSERT INTO `zz_7367_institution_cases` SELECT * FROM `institution_cases`');

        $this->execute('CREATE TABLE `zz_7367_institution_case_records` LIKE `institution_case_records`');
        $this->execute('INSERT INTO `zz_7367_institution_case_records` SELECT * FROM `institution_case_records`');

        $this->execute('CREATE TABLE `zz_7367_institution_student_absences` LIKE `institution_student_absences`');
        $this->execute('INSERT INTO `zz_7367_institution_student_absences` SELECT * FROM `institution_student_absences`');

        $this->execute('CREATE TABLE `zz_7367_absence_types` LIKE `absence_types`');
        $this->execute('INSERT INTO `zz_7367_absence_types` SELECT * FROM `absence_types`');

        $this->execute("CREATE TABLE IF NOT EXISTS `institution_case_links` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `parent_case_id` int(11) NOT NULL,
            `child_case_id` int(11) NOT NULL,
            `created` datetime DEFAULT NULL,
             PRIMARY KEY (`id`)
          )  ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $institutionCasesTable = TableRegistry::get('institution_cases');
        $institutionCaseRecordsTable = TableRegistry::get('institution_case_records');
        $institutionStudentAbsencesTable = TableRegistry::get('institution_student_absences');
        $AbsenceTypeTable = TableRegistry::get('absence_types');
      
        $institutionCases = $institutionCasesTable->find()->toArray();  
        foreach($institutionCases as $institutionCase){
           $institutionCaseRecords = $institutionCaseRecordsTable->find('all')->where(['institution_case_id'=>$institutionCase->id])->toArray(); 
           
  
           $content='';
           foreach($institutionCaseRecords as $institutionCaseRecord) {
                if($institutionCaseRecord->feature == "StudentAttendances"){
                    $insStuAbsence = $institutionStudentAbsencesTable->find()->where(['id'=>$institutionCaseRecord->record_id])->first();
                    $AbsenceType = $AbsenceTypeTable->find()->where(['id'=>$insStuAbsence->absence_type_id])->first();
                    $content .= $AbsenceType->name .' - ('. date('d M Y', strtotime($insStuAbsence->date)).')'.',';
                }
            }
            $content = rtrim($content,',');
            
           $institutionCaseId =$institutionCase->id; 
            $this->execute("UPDATE `institution_cases` SET `description` = '$content' WHERE `id`= $institutionCaseId");
        }

    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_cases`');
        $this->execute('RENAME TABLE `zz_7367_institution_cases` TO `institution_cases`');

        $this->execute('DROP TABLE IF EXISTS `institution_case_records`');
        $this->execute('RENAME TABLE `zz_7367_institution_case_records` TO `institution_case_records`');

        $this->execute('DROP TABLE IF EXISTS `institution_student_absences`');
        $this->execute('RENAME TABLE `zz_7367_institution_student_absences` TO `institution_student_absences`');

        $this->execute('DROP TABLE IF EXISTS `absence_types`');
        $this->execute('RENAME TABLE `zz_7367_absence_types` TO `absence_types`');

        $this->execute('DROP TABLE IF EXISTS `institution_case_links`');
    }
}
