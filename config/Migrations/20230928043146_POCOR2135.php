<?php
use Migrations\AbstractMigration;

class POCOR2135 extends AbstractMigration
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
        // custom module
        $this->execute('CREATE TABLE `zz_2135_custom_modules` LIKE `custom_modules`');
        $this->execute('INSERT INTO `zz_2135_custom_modules` SELECT * FROM `custom_modules`');
        $data = [
            'code' => 'Institution > Staff',
            'model' => 'Staff.StaffSurveys',
            'name' => 'Institution > Staffs > Survey',
            'visible' => 1,
            'parent_id' => 0, 
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s'),
            'modified' => null,
            'modified_user_id' => null
        ];
        $this->insert('custom_modules', $data);
        // // custom field types
        $this->execute('CREATE TABLE `zz_2135_custom_field_types` LIKE `custom_field_types`');
        $this->execute('INSERT INTO `zz_2135_custom_field_types` SELECT * FROM `custom_field_types`');
        $data1 = [
            'code' => 'STAFF_LIST',
            'name' => 'Staff List',
            'value' => 'text_value',
            'description' => "",
            'format' => 'OpenEMIS_Institution',
            'is_mandatory' => 0,
            'is_unique' => 0,
            'visible' => 1
        ];
        $this->insert('custom_field_types', $data1);
        //institution_staff_survey
        $this->execute("CREATE TABLE `institution_staff_surveys` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
            `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
            `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
            `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
            `survey_form_id` int(11) NOT NULL COMMENT 'links to survey_forms.id',
            `parent_form_id` int(11) NOT NULL COMMENT 'links to institution_surveys.survey_form_id',
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`),
             FOREIGN KEY (`academic_period_id`) REFERENCES `academic_periods` (`id`),
             FOREIGN KEY (`status_id`) REFERENCES `workflow_steps` (`id`),
             FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`),
             FOREIGN KEY (`staff_id`) REFERENCES `security_users` (`id`),
             FOREIGN KEY (`survey_form_id`) REFERENCES `survey_forms` (`id`),
             FOREIGN KEY (`parent_form_id`) REFERENCES `institution_surveys` (`id`)
            )ENGINE=InnoDB DEFAULT CHARSET=utf8 ");

        //institution_staff_survey_answers
        $this->execute("CREATE TABLE `institution_staff_survey_answers` (
            `id` char(36) NOT NULL,
            `text_value` varchar(250)  DEFAULT NULL,
            `number_value` int(11) DEFAULT NULL,
            `decimal_value` varchar(25)  DEFAULT NULL,
            `textarea_value` text  DEFAULT NULL,
            `date_value` date DEFAULT NULL,
            `time_value` time DEFAULT NULL,
            `file` longblob DEFAULT NULL,
            `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id',
            `parent_survey_question_id` int(11) DEFAULT NULL COMMENT 'links to survey questions',
            `institution_staff_survey_id` int(11) NOT NULL COMMENT 'links to institution_staff_surveys.id',
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`),
             FOREIGN KEY (`survey_question_id`) REFERENCES `survey_questions` (`id`) ,
             FOREIGN KEY (`institution_staff_survey_id`) REFERENCES `institution_staff_surveys` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ");

       // institution_staff_survey_table_cells
        $this->execute("CREATE TABLE `institution_staff_survey_table_cells` (
            `text_value` varchar(250)  DEFAULT NULL,
            `number_value` int(11) DEFAULT NULL,
            `decimal_value` varchar(25)  DEFAULT NULL,
            `survey_question_id` int(11) NOT NULL COMMENT 'links to survey_questions.id',
            `survey_table_column_id` int(11) NOT NULL COMMENT 'links to survey_table_columns.id',
            `survey_table_row_id` int(11) NOT NULL COMMENT 'links to survey_table_rows.id',
            `institution_staff_survey_id` int(11) NOT NULL COMMENT 'links to institution_staff_surveys.id',
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
            PRIMARY KEY (`survey_question_id`,`survey_table_column_id`,`survey_table_row_id`,`institution_staff_survey_id`),
            FOREIGN KEY (`institution_staff_survey_id`) REFERENCES `institution_staff_surveys` (`id`),
            FOREIGN KEY (`survey_question_id`) REFERENCES `survey_questions` (`id`),
            FOREIGN KEY (`survey_table_column_id`) REFERENCES `survey_table_columns` (`id`),
            FOREIGN KEY (`survey_table_row_id`) REFERENCES `survey_table_rows` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    }
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `zz_2135_custom_modules`');
        $this->execute('RENAME TABLE `zz_2135_custom_modules` TO `custom_modules`');

        $this->execute('DROP TABLE IF EXISTS `zz_2135_custom_field_types`');
        $this->execute('RENAME TABLE `zz_2135_custom_field_types` TO `custom_field_types`');

        $this->execute('DROP TABLE IF EXISTS `institution_staff_survey_table_cells`');
        $this->execute('DROP TABLE IF EXISTS `institution_staff_survey_answers`');
        $this->execute('DROP TABLE IF EXISTS `institution_staff_survey`');
    }
}
