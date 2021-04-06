<?php
use Migrations\AbstractMigration;

class POCOR5947 extends AbstractMigration
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

        $institutionClassStudents = $this->fetchAll('SELECT * FROM `institution_class_students`');

        if(!empty($institutionClassStudents)) {
            foreach ($institutionClassStudents as $key => $value) {

              $this->execute("UPDATE assessment_item_results
                SET `institution_classes_id` = '".$value['institution_class_id']."'
                WHERE `student_id` = '".$value['student_id']."' AND `institution_id` = '".$value['institution_id']."' AND `academic_period_id` = '".$value['academic_period_id']."' AND `education_grade_id` = '".$value['education_grade_id']. "'");
                }
        }

    }
}
