<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR7058 extends AbstractMigration
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
        // Creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_7058_institution_classes`');
        $this->execute('CREATE TABLE `zz_7058_institution_classes` LIKE `institution_classes`');
        $this->execute('INSERT INTO `zz_7058_institution_classes` SELECT * FROM `institution_classes`');
        
        $institutionClassesData = $this->fetchAll('SELECT * FROM `institution_classes`');
         $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
         $InstitutionClass = TableRegistry::get('Institution.InstitutionClasses');
         $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');

        foreach ($institutionClassesData as $row) {
            $grades = [];
            $classGradeData = $InstitutionClass->find()
            ->select(['grade_id' => $InstitutionClassGrades->aliasField('education_grade_id')])
            ->leftJoin([$InstitutionClassGrades->alias() => $InstitutionClassGrades->table()], [
                $InstitutionClass->aliasField('id = ') . $InstitutionClassGrades->aliasField('institution_class_id')
            ])
            ->where([
                $InstitutionClassGrades->aliasField('institution_class_id') => $row['id'],
                $InstitutionClass->aliasField('institution_id') => $row['institution_id'],
                $InstitutionClass->aliasField('academic_period_id') => $row['academic_period_id']
            ])
            ->toArray();
            if (!empty($classGradeData)) {
                foreach ($classGradeData as $data) {
                    $grades[] = $data->grade_id;
                }
            }
            $StudentStatuses = TableRegistry::get('student_statuses');
            $status = ['CURRENT','REPEATED','PROMOTED','GRADUATED'];
             $genderMale_id = 1;
             $genderfemale_id = 2;

            $totalMaleStudentRecord = $InstitutionClassStudents->find()
            
            ->innerJoin(
                            [$StudentStatuses->alias() => $StudentStatuses->table()],
                            [
                                $StudentStatuses->aliasField('code IN') =>$status,
                                $InstitutionClassStudents->aliasField('student_status_id = ') . $StudentStatuses->aliasField('id')
                            ]
                        )
            ->innerJoin(
                ['Users' => 'security_users'],
                [
                    'Users.id = '. $InstitutionClassStudents->aliasField('student_id')
                ]
                )
            ->where([
                $InstitutionClassStudents->aliasField('institution_class_id') => $row['id'],
                $InstitutionClassStudents->aliasField('institution_id') => $row['institution_id'],
                $InstitutionClassStudents->aliasField('academic_period_id') => $row['academic_period_id'],
                $InstitutionClassStudents->aliasField('education_grade_id IN') => $grades,
                $InstitutionClassStudents->Users->aliasField('gender_id') => $genderMale_id
            ]);
            
            $MaleCount = 0;
            if (!empty($totalMaleStudentRecord)) {
              $MaleCount = $totalMaleStudentRecord->count();
            } else {
                $MaleCount;
            }

            $totalFemaleStudentRecord = $InstitutionClassStudents->find()
            
            ->innerJoin(
                            [$StudentStatuses->alias() => $StudentStatuses->table()],
                            [
                                $StudentStatuses->aliasField('code IN') =>$status,
                                $InstitutionClassStudents->aliasField('student_status_id = ') . $StudentStatuses->aliasField('id')
                            ]
                        )
            ->innerJoin(
                ['Users' => 'security_users'],
                [
                    'Users.id = '. $InstitutionClassStudents->aliasField('student_id')
                ]
                )
            ->where([
                $InstitutionClassStudents->aliasField('institution_class_id') => $row['id'],
                $InstitutionClassStudents->aliasField('institution_id') => $row['institution_id'],
                $InstitutionClassStudents->aliasField('academic_period_id') => $row['academic_period_id'],
                $InstitutionClassStudents->aliasField('education_grade_id IN') => $grades,
                $InstitutionClassStudents->Users->aliasField('gender_id') => $genderfemale_id
            ]);
            
            $FemaleCount = 0;
            if (!empty($totalFemaleStudentRecord)) {
              $FemaleCount = $totalFemaleStudentRecord->count();
            } else {
                $FemaleCount;
            }

            $this->execute("UPDATE `institution_classes` SET `total_male_students` = " . $MaleCount . ", `total_female_students` = " . $FemaleCount . " WHERE `institution_classes`.`id` = " . $row['id'] . "");
        } 
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_classes`');
        $this->execute('RENAME TABLE `zz_7058_institution_classes` TO `institution_classes`');
    }
}
