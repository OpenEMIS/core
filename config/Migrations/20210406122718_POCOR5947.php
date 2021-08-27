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
        $this->execute('CREATE TABLE `z_5947_assessment_item_results` LIKE `assessment_item_results`');
        $this->execute('INSERT INTO `z_5947_assessment_item_results` SELECT * FROM `assessment_item_results`');

        $this->execute('ALTER TABLE `assessment_item_results` ADD `institution_classes_id` INT(11) NOT NULL AFTER `institution_id`');

        $this->execute("ALTER TABLE `assessment_item_results` ADD INDEX `created` (`created`);");
        $this->execute("ALTER TABLE `institution_subject_students` ADD INDEX `created` (`created`);");
        $this->execute("ALTER TABLE `institution_subject_students` ADD INDEX `modified` (`modified`);");

        $this->execute("ALTER TABLE `assessment_item_results` DROP PRIMARY KEY, ADD primary key (`student_id`,`assessment_id`,`education_subject_id`,`education_grade_id`,`academic_period_id`,`assessment_period_id`,`institution_classes_id`), ADD UNIQUE INDEX (`student_id`,`assessment_id`,`education_subject_id`,`education_grade_id`,`academic_period_id`,`assessment_period_id`,`institution_classes_id`)");

        $institutionSubjectStudents = $this->fetchAll('SELECT * FROM `institution_subject_students` WHERE (`modified` IS NOT NULL AND `student_status_id` != 1) Group By `student_id` ');
        $resultArr = [];
        if (!empty($institutionSubjectStudents)) {
            foreach ($institutionSubjectStudents as $value) {
                $assessment_item_results =  $this->fetchAll("SELECT * FROM `assessment_item_results` WHERE assessment_item_results.student_id = '".$value['student_id']. "'");
                if(!empty($assessment_item_results)){
                    $resultArr = $this->fetchAll("SELECT institution_subject_students.id as subject_Id,institution_subject_students.total_mark as subject_total_mark,institution_subject_students.student_id as subject_student_id,institution_subject_students.institution_subject_id as subject_institution_subject_id,institution_subject_students.institution_class_id as subject_institution_class_id,institution_subject_students.institution_id as subject_institution_id,institution_subject_students.academic_period_id as subject_academic_period_id,institution_subject_students.education_subject_id as subject_education_subject_id,institution_subject_students.education_grade_id as subject_education_grade_id,institution_subject_students.student_status_id as subject_student_status_id,institution_subject_students.modified_user_id as subject_modified_user_id,institution_subject_students.modified as subject_modified,institution_subject_students.created_user_id as subject_created_user_id,assessment_item_results.id as assessmentId,assessment_item_results.marks as assessment_marks,assessment_item_results.assessment_grading_option_id as assessment_assessment_grading_option_id,assessment_item_results.student_id as assessment_student_id,assessment_item_results.assessment_id as assessment_assessment_id,assessment_item_results.education_subject_id as assessment_education_subject_id,assessment_item_results.education_grade_id as assessment_education_grade_id,assessment_item_results.academic_period_id as assessment_academic_period_id,assessment_item_results.assessment_period_id as assessment_assessment_period_id,assessment_item_results.institution_id as assessment_institution_id,assessment_item_results.institution_classes_id as assessment_institution_classes_id,assessment_item_results.modified_user_id as assessment_modified_user_id,assessment_item_results.modified as assessment_modified,assessment_item_results.created_user_id as assessment_created_user_id,assessment_item_results.created as assessment_created FROM `assessment_item_results` INNER JOIN institution_subject_students ON institution_subject_students.student_id = assessment_item_results.student_id AND institution_subject_students.academic_period_id = assessment_item_results.academic_period_id AND institution_subject_students.education_subject_id = assessment_item_results.education_subject_id AND institution_subject_students.institution_id = assessment_item_results.institution_id AND institution_subject_students.education_grade_id = assessment_item_results.education_grade_id AND assessment_item_results.created BETWEEN institution_subject_students.created AND institution_subject_students.modified WHERE assessment_item_results.student_id = '".$value['student_id']. "' ");

                    if(!empty($resultArr)){
                            foreach ($resultArr as $result) {
                                if($result['assessment_assessment_grading_option_id'] != null || $result['assessment_assessment_grading_option_id'] != ''){
                                    $this->execute("UPDATE assessment_item_results
                                        SET `institution_classes_id` = '".$result['subject_institution_class_id']."'
                                        WHERE `student_id` = '".$result['subject_student_id']."' AND `institution_id` = '".$result['subject_institution_id']."' AND `academic_period_id` = '".$result['subject_academic_period_id']."' AND `education_grade_id` = '".$result['subject_education_grade_id']. "' AND `education_subject_id` = '".$result['subject_education_subject_id']."' AND `assessment_grading_option_id` = '".$result['assessment_assessment_grading_option_id']."' AND `assessment_period_id` = '".$result['assessment_assessment_period_id']."' ");
                                }else{
                                    $this->execute("UPDATE assessment_item_results
                                        SET `institution_classes_id` = '".$result['subject_institution_class_id']."'
                                        WHERE `student_id` = '".$result['subject_student_id']."' AND `institution_id` = '".$result['subject_institution_id']."' AND `academic_period_id` = '".$result['subject_academic_period_id']."' AND `education_grade_id` = '".$result['subject_education_grade_id']. "' AND `education_subject_id` = '".$result['subject_education_subject_id']."' AND `assessment_period_id` = '".$result['assessment_assessment_period_id']."' ");
                                }
                            }
                        }
                        unset($resultArr);
                }
            }
        }

        $institutionSubjectStudentIsNull = $this->fetchAll('SELECT * FROM `institution_subject_students` WHERE ((`modified` IS NULL OR `modified` IS NOT NULL) AND `student_status_id` = 1) Group By `student_id` ');
        $resultArr1 = [];
        $assessment_item_results1 = [];
        if (!empty($institutionSubjectStudentIsNull)) {
            foreach ($institutionSubjectStudentIsNull as $value) {

                $assessment_item_results1 =  $this->fetchAll("SELECT * FROM `assessment_item_results` WHERE assessment_item_results.student_id = '".$value['student_id']. "'");
                if(!empty($assessment_item_results1)){
                    $resultArr1  = $this->fetchAll("SELECT institution_subject_students.id as subject_Id,institution_subject_students.total_mark as subject_total_mark,institution_subject_students.student_id as subject_student_id,institution_subject_students.institution_subject_id as subject_institution_subject_id,institution_subject_students.institution_class_id as subject_institution_class_id,institution_subject_students.institution_id as subject_institution_id,institution_subject_students.academic_period_id as subject_academic_period_id,institution_subject_students.education_subject_id as subject_education_subject_id,institution_subject_students.education_grade_id as subject_education_grade_id,institution_subject_students.student_status_id as subject_student_status_id,institution_subject_students.modified_user_id as subject_modified_user_id,institution_subject_students.modified as subject_modified,institution_subject_students.created_user_id as subject_created_user_id,assessment_item_results.id as assessmentId,assessment_item_results.marks as assessment_marks,assessment_item_results.assessment_grading_option_id as assessment_assessment_grading_option_id,assessment_item_results.student_id as assessment_student_id,assessment_item_results.assessment_id as assessment_assessment_id,assessment_item_results.education_subject_id as assessment_education_subject_id,assessment_item_results.education_grade_id as assessment_education_grade_id,assessment_item_results.academic_period_id as assessment_academic_period_id,assessment_item_results.assessment_period_id as assessment_assessment_period_id,assessment_item_results.institution_id as assessment_institution_id,assessment_item_results.institution_classes_id as assessment_institution_classes_id,assessment_item_results.modified_user_id as assessment_modified_user_id,assessment_item_results.modified as assessment_modified,assessment_item_results.created_user_id as assessment_created_user_id,assessment_item_results.created as assessment_created FROM `assessment_item_results` INNER JOIN institution_subject_students ON institution_subject_students.student_id = assessment_item_results.student_id AND institution_subject_students.academic_period_id = assessment_item_results.academic_period_id AND institution_subject_students.education_subject_id = assessment_item_results.education_subject_id AND institution_subject_students.institution_id = assessment_item_results.institution_id AND institution_subject_students.education_grade_id = assessment_item_results.education_grade_id AND assessment_item_results.created BETWEEN institution_subject_students.created AND institution_subject_students.modified WHERE institution_subject_students.student_status_id = 1 AND assessment_item_results.student_id = '".$value['student_id']. "'");
                    if(!empty($resultArr1)){
                        foreach ($resultArr1 as $result1) {
                            if($result1['assessment_assessment_grading_option_id'] != null || $result1['assessment_assessment_grading_option_id'] != ''){
                                $this->execute("UPDATE assessment_item_results
                                    SET `institution_classes_id` = '".$result1['subject_institution_class_id']."'
                                    WHERE `student_id` = '".$result1['subject_student_id']."' AND `institution_id` = '".$result1['subject_institution_id']."' AND `academic_period_id` = '".$result1['subject_academic_period_id']."' AND `education_grade_id` = '".$result1['subject_education_grade_id']. "' AND `education_subject_id` = '".$result1['subject_education_subject_id']."' AND `assessment_grading_option_id` = '".$result1['assessment_assessment_grading_option_id']."' AND `assessment_period_id` = '".$result1['assessment_assessment_period_id']."' ");
                            }else{
                                $this->execute("UPDATE assessment_item_results
                                    SET `institution_classes_id` = '".$result1['subject_institution_class_id']."'
                                    WHERE `student_id` = '".$result1['subject_student_id']."' AND `institution_id` = '".$result1['subject_institution_id']."' AND `academic_period_id` = '".$result1['subject_academic_period_id']."' AND `education_grade_id` = '".$result1['subject_education_grade_id']. "' AND `education_subject_id` = '".$result1['subject_education_subject_id']."' AND `assessment_period_id` = '".$result1['assessment_assessment_period_id']."' ");
                            }
                        }
                    }
                    unset($resultArr1);
                }

            }
        }
    
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `assessment_item_results`');
        $this->execute('RENAME TABLE `z_5947_assessment_item_results` TO `assessment_item_results`');
    }
}