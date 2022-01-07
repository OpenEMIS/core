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
        $this->execute("ALTER TABLE `institution_class_students` ADD INDEX `created` (`created`);");
        $this->execute("ALTER TABLE `institution_class_students` ADD INDEX `modified` (`modified`);");

        $this->execute("ALTER TABLE `assessment_item_results` DROP PRIMARY KEY, ADD primary key (`student_id`,`assessment_id`,`education_subject_id`,`education_grade_id`,`academic_period_id`,`assessment_period_id`,`institution_classes_id`), ADD UNIQUE INDEX (`student_id`,`assessment_id`,`education_subject_id`,`education_grade_id`,`academic_period_id`,`assessment_period_id`,`institution_classes_id`)");

        // For assessment_item_results.created BETWEEN institution_class_students.created and institution_class_students.modified
        $this->execute("UPDATE assessment_item_results INNER JOIN institution_class_students 
            ON institution_class_students.student_id = assessment_item_results.student_id
            AND institution_class_students.education_grade_id = assessment_item_results.education_grade_id
            AND institution_class_students.academic_period_id = assessment_item_results.academic_period_id
            AND institution_class_students.institution_id = assessment_item_results.institution_id
            AND assessment_item_results.created BETWEEN institution_class_students.created AND institution_class_students.modified
            SET assessment_item_results.institution_classes_id = institution_class_students.institution_class_id");        

        // For institution_class_students.modified IS NULL
        $this->execute("UPDATE assessment_item_results INNER JOIN institution_class_students 
            ON institution_class_students.student_id = assessment_item_results.student_id
            AND institution_class_students.education_grade_id = assessment_item_results.education_grade_id
            AND institution_class_students.academic_period_id = assessment_item_results.academic_period_id
            AND institution_class_students.institution_id = assessment_item_results.institution_id
            AND assessment_item_results.created >= institution_class_students.created
            SET assessment_item_results.institution_classes_id = institution_class_students.institution_class_id 
            WHERE institution_class_students.modified IS NULL 
            OR (institution_class_students.modified IS NOT NULL AND assessment_item_results.institution_classes_id = 0)");   


        // For institution_classes that are not updated from previous queries
        $this->execute("UPDATE assessment_item_results INNER JOIN institution_class_students 
            ON institution_class_students.student_id = assessment_item_results.student_id
            AND institution_class_students.education_grade_id = assessment_item_results.education_grade_id
            AND institution_class_students.academic_period_id = assessment_item_results.academic_period_id
            AND institution_class_students.institution_id = assessment_item_results.institution_id
            SET assessment_item_results.institution_classes_id = institution_class_students.institution_class_id 
            WHERE assessment_item_results.institution_classes_id = 0");   


    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `assessment_item_results`');
        $this->execute('RENAME TABLE `z_5947_assessment_item_results` TO `assessment_item_results`');
    }
}
