<?php
use Migrations\AbstractMigration;

class POCOR7739 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7739_academic_periods` LIKE `academic_periods`');
        $this->execute('INSERT INTO `zz_7739_academic_periods` SELECT * FROM `academic_periods`');
        //Update institution_student_survey_answers
        $this->execute("UPDATE IGNORE academic_periods SET visible = '0' WHERE academic_period_level_id = '-1';");

    }
    public function down()
    {
        // Field Options
        $this->execute('DROP TABLE IF EXISTS `academic_periods`');
        $this->execute('RENAME TABLE `zz_7739_academic_periods` TO `academic_periods`');
    }
}
