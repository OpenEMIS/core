<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Utility\Security;
use Cake\Utility\Hash;

class POCOR6288 extends AbstractMigration
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
        ini_set('memory_limit', '-1');
        // Backup table
        $this->execute('CREATE TABLE `zz_6288_institution_class_students` LIKE `institution_class_students`');
        $this->execute('INSERT INTO `zz_6288_institution_class_students` SELECT * FROM `institution_class_students`');

        //institution_class_students[START]

        $institution_class_students_result_data = $this->fetchAll("SELECT education_systems.academic_period_id,correct_grade.id AS correct_grade_id,institution_class_students.* FROM `institution_class_students`
        INNER JOIN education_grades wrong_grade ON wrong_grade.id = institution_class_students.education_grade_id
        INNER JOIN education_grades correct_grade ON correct_grade.code = wrong_grade.code
        INNER JOIN education_programmes ON correct_grade.education_programme_id = education_programmes.id
        INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
        INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
        INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
        AND education_systems.academic_period_id = institution_class_students.academic_period_id
        WHERE correct_grade.id != institution_class_students.education_grade_id");

        $institution_class_students = TableRegistry::get('institution_class_students');
        if(!empty($institution_class_students_result_data)){
            foreach ($institution_class_students_result_data as $text_key => $text_val) {
                $text_val_id = $text_val['id'];
                $textData = $institution_class_students
                                    ->find()
                                    ->where([
                                        $institution_class_students->aliasField('id') =>$text_val_id,
                                        $institution_class_students->aliasField('academic_period_id') =>$text_val['academic_period_id'],
                                        $institution_class_students->aliasField('institution_id') =>$text_val['institution_id'],
                                        $institution_class_students->aliasField('institution_class_id') =>$text_val['institution_class_id'],
                                        $institution_class_students->aliasField('education_grade_id') =>$text_val['correct_grade_id'],

                                    ])
                                    ->first();
                if(!empty($textData)){
                    $id = "'".$textData['id']."'";
                    $correct_grade_id = $text_val['correct_grade_id'];
                    $sql = "UPDATE `institution_class_students` SET `education_grade_id` = $correct_grade_id WHERE `id` = $id";
                    //echo $sql; die;        
                    $this->execute($sql);
                    
                }   
            }
        }

        //institution_class_students[END]
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_class_students`');
        $this->execute('RENAME TABLE `zz_6288_institution_class_students` TO `institution_class_students`');
    }
}
