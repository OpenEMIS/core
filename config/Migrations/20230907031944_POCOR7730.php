<?php
use Migrations\AbstractMigration;

class POCOR7730 extends AbstractMigration
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
        //Backup institution_student_survey_answers table
        $this->execute('CREATE TABLE `zz_7730_institution_student_survey_answers` LIKE `institution_student_survey_answers`');
        $this->execute('INSERT INTO `zz_7730_institution_student_survey_answers` SELECT * FROM `institution_student_survey_answers`');
        //Update institution_student_survey_answers
        $this->execute("ALTER TABLE `institution_student_survey_answers` ADD `parent_survey_question_id` INT NULL DEFAULT NULL COMMENT 'links to survey questions' AFTER `survey_question_id`;");
        
    }
    public function down()
    {
        // Field Options
        $this->execute('DROP TABLE IF EXISTS `institution_student_survey_answers`');
        $this->execute('RENAME TABLE `zz_7730_institution_student_survey_answers` TO `institution_student_survey_answers`');
    }
}
