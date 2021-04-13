<?php
use Migrations\AbstractMigration;
use Cake\I18n\Date;

class POCORt5947 extends AbstractMigration
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
        //$institutionClassStudents = $this->fetchAll('SELECT * FROM `institution_class_students`');
        $assessmentItemResults = $this->fetchAll('SELECT * FROM `assessment_item_results`');

        if(!empty($assessmentItemResults)) {
            $daaa = [];
            foreach ($assessmentItemResults as $key => $value) {
                $assessmentCreated = date('Y-m-d', strtotime($value['created']));
                $institutionStudents = $this->fetchAll('SELECT * FROM `institution_students` WHERE `modified` >= "'.$assessmentCreated.'" AND `created` <= "'.$assessmentCreated.'" AND student_id = "'.$value['student_id'].'"');

                $institutionClassStudents = $this->fetchAll('SELECT * FROM `institution_class_students` WHERE `student_status_id` = "'.$institutionStudents[0]['student_status_id'].'" AND student_id = "'.$institutionStudents[0]['student_id'].'"');
                if(empty($institutionStudents)){

                    $enrolledStudent = $this->fetchAll('SELECT * FROM `institution_students` WHERE `student_status_id` = "1" AND student_id = "'.$value['student_id'].'"');
                   
                        $institutionClassStudents = $this->fetchAll('SELECT * FROM `institution_class_students` WHERE student_id = "'.$enrolledStudent[0]['student_id'].'"');
                        $daaa[] = $institutionClassStudents[0]['student_id'];

                }
                 $daaa[] = $institutionClassStudents[0]['student_id'];


               // $institutionClass = $this->fetchAll('SELECT * FROM `institution_students` WHERE `created` >= "'.$assessmentCreated.'" AND student_id = "'.$value['student_id'].'"');

                /*if ($institutionStudents[0]) {
                    $institutionClassStudents = $this->fetchAll('SELECT * FROM `institution_class_students` WHERE `student_status_id` = "'.$institutionStudents[0]['student_status_id'].'" AND student_id = "'.$institutionStudents[0]['student_id'].'"');


                    if (empty($institutionClassStudents)) {
                         $institutionClassStudents = $this->fetchAll('SELECT * FROM `institution_class_students` WHERE student_id = "'.$value['student_id'].'"');
                        $daaa[] = $institutionClassStudents[0]['student_id'];
                    }
                    $daaa[] = $institutionClassStudents[0]['student_id'];

                    // $this->execute("UPDATE assessment_item_results
                    // SET `institution_classes_id` = '".$institutionClassStudents[0]['institution_class_id']."'
                    // WHERE `student_id` = '".$value['student_id']."' AND `created` = '".$assessmentCreated."'
                    // ");
                }
                
                else{
                    $enrolledStudent = $this->fetchAll('SELECT * FROM `institution_students` WHERE `student_status_id` = "1" AND student_id = "'.$value['student_id'].'"');
                   
                        $enrolledClassStud = $this->fetchAll('SELECT * FROM `institution_class_students` WHERE student_id = "'.$enrolledStudent[0]['student_id'].'"');
                        
                        // if (empty($enrolledClassStud)) {
                        //  $enrolledClassStud = $this->fetchAll('SELECT * FROM `institution_class_students` WHERE student_id = "'.$value['student_id'].'"');
                        //     $daaa[] = $enrolledClassStud[0]['student_id'];
                        // }

                        $daaa[] = $enrolledClassStud[0]['student_id'];
                    

                    
                }*/

                //$daaa[] = "rrr";
               
            }
            echo "<pre>"; print_r($daaa); die();
        }

        /*
        if(!empty($institutionClassStudents)) {
            foreach ($institutionClassStudents as $key => $value) {

              $this->execute("UPDATE assessment_item_results
                SET `institution_classes_id` = '".$value['institution_class_id']."'
                WHERE `student_id` = '".$value['student_id']."' AND `institution_id` = '".$value['institution_id']."' AND `academic_period_id` = '".$value['academic_period_id']."' AND `education_grade_id` = '".$value['education_grade_id']. "'");
                }
        }*/
    }
}