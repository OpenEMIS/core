<?php
use Migrations\AbstractMigration;

class POCOR7963 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7963_institution_survey_answers` LIKE `institution_survey_answers`');
        $this->execute('INSERT INTO `zz_7963_institution_survey_answers` SELECT * FROM `institution_survey_answers`');
        $this->execute('CREATE TABLE `zz_7963_institution_repeater_survey_answers` LIKE `institution_repeater_survey_answers`');
        $this->execute('INSERT INTO `zz_7963_institution_repeater_survey_answers` SELECT * FROM `institution_repeater_survey_answers`');


        // CREATE summary tables and INSERT new rows into report_queries table
        $this->execute('ALTER TABLE `institution_survey_answers` CHANGE `number_value` `number_value` VARCHAR(255) NULL DEFAULT NULL');
        $this->execute('ALTER TABLE `institution_repeater_survey_answers` CHANGE `number_value` `number_value` VARCHAR(255) NULL DEFAULT NULL');
    }
         
    // rollback
    public function down()
    {
        
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `institution_survey_answers`');
        $this->execute('RENAME TABLE `zz_7963_institution_survey_answers` TO `institution_survey_answers`');
        $this->execute('DROP TABLE IF EXISTS `institution_repeater_survey_answers`');
        $this->execute('RENAME TABLE `zz_7963_institution_repeater_survey_answers` TO `institution_repeater_survey_answers`');

    }
}
