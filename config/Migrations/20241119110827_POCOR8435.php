<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8435 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        //backup
        //education_grades_subjects
        $this->execute('CREATE TABLE IF NOT EXISTS `z_8435_education_grades_subjects` LIKE `education_grades_subjects`');
        $this->execute('INSERT INTO `z_8435_education_grades_subjects` SELECT * FROM `education_grades_subjects`');
        $this->execute("ALTER TABLE `education_grades_subjects` ADD `requirement` VARCHAR(100) NOT NULL AFTER `education_subject_id`");
        $this->execute("ALTER TABLE `education_grades_subjects` ADD `result_type` VARCHAR(100) NOT NULL AFTER `requirement`");

        //outcome_templates
        $this->execute('CREATE TABLE IF NOT EXISTS `z_8435_outcome_templates` LIKE `outcome_templates`');
        $this->execute('INSERT INTO `z_8435_outcome_templates` SELECT * FROM `outcome_templates`');
        $this->execute("ALTER TABLE `outcome_templates` ADD `outcome_grading_type_id` INT NULL AFTER `education_grade_id`, 
        ADD CONSTRAINT `fk_outcome_grading_type_id` FOREIGN KEY (`outcome_grading_type_id`) REFERENCES `outcome_grading_types`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE");
        
        //institution_subject_students
        $this->execute('CREATE TABLE IF NOT EXISTS `z_8435_institution_subject_students` LIKE `institution_subject_students`');
        $this->execute('INSERT INTO `z_8435_institution_subject_students` SELECT * FROM `institution_subject_students`');
        $this->execute("ALTER TABLE `institution_subject_students` ADD `outcome_result` VARCHAR(100) NULL DEFAULT NULL AFTER `total_mark`");
    }
    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `education_grades_subjects`');
        $this->execute('RENAME TABLE `z_8435_education_grades_subjects` TO `education_grades_subjects`');

        $this->execute('DROP TABLE IF EXISTS `outcome_templates`');
        $this->execute('RENAME TABLE `z_8435_outcome_templates` TO `outcome_templates`');

        $this->execute('DROP TABLE IF EXISTS `institution_subject_students`');
        $this->execute('RENAME TABLE `z_8435_institution_subject_students` TO `institution_subject_students`');

    }
}
