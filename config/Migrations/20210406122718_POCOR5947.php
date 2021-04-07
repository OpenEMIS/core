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

        $this->execute('ALTER TABLE `assessment_item_results` ADD `institution_classes_id` INT(11) NOT NULL AFTER `institution_id`, ADD `student_status_id` INT(11) NULL DEFAULT NULL AFTER `institution_classes_id`');

        $this->execute("ALTER TABLE `assessment_item_results` DROP PRIMARY KEY, ADD primary key (`student_id`,`assessment_id`,`education_subject_id`,`education_grade_id`,`academic_period_id`,`assessment_period_id`,`institution_classes_id`), ADD UNIQUE INDEX (`student_id`,`assessment_id`,`education_subject_id`,`education_grade_id`,`academic_period_id`,`assessment_period_id`,`institution_classes_id`)");

        $institutionClassStudents = $this->fetchAll('SELECT * FROM `institution_class_students`');

        if(!empty($institutionClassStudents)) {
            foreach ($institutionClassStudents as $key => $value) {

              $this->execute("UPDATE assessment_item_results
                SET `institution_classes_id` = '".$value['institution_class_id']."'
                WHERE `student_id` = '".$value['student_id']."' AND `institution_id` = '".$value['institution_id']."' AND `academic_period_id` = '".$value['academic_period_id']."' AND `education_grade_id` = '".$value['education_grade_id']. "'");
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
