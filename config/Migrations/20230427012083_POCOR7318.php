<?php
use Migrations\AbstractMigration;

class POCOR7318 extends AbstractMigration
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
        // Backup table
       $this->execute('CREATE TABLE `z_7318_assessment_grading_options` LIKE `assessment_grading_options`');
       $this->execute('INSERT INTO `z_7318_assessment_grading_options` SELECT * FROM `assessment_grading_options`');
       $this->execute('CREATE TABLE `z_7318_assessments` LIKE `assessments`');
       $this->execute('INSERT INTO `z_7318_assessments` SELECT * FROM `assessments`');

       $this->execute('ALTER TABLE `assessment_grading_options` ADD `point` decimal(6,2) NULL AFTER `max`');
       $this->execute('ALTER TABLE `assessments` ADD COLUMN `assessment_grading_type_id` int(11) NULL COMMENT "links to assessment_grading_types.id" AFTER `education_grade_id`');
    }

    // rollback
    public function down()
    {
        // Restore table
       $this->execute('DROP TABLE IF EXISTS `assessment_grading_options`');
       $this->execute('RENAME TABLE `zz_7318_assessment_grading_options` TO `assessment_grading_options`');
       $this->execute('DROP TABLE IF EXISTS `assessments`');
       $this->execute('RENAME TABLE `zz_7318_assessments` TO `assessments`');
    }
}
