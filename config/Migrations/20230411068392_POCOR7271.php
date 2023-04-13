<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR7271 extends AbstractMigration
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
        // Backup tables
        $this->execute('CREATE TABLE `zz_7271_survey_forms_filters` LIKE `survey_forms_filters`');
        $this->execute('INSERT INTO `zz_7271_survey_forms_filters` SELECT * FROM `survey_forms_filters`');

        $this->execute('CREATE TABLE `zz_7271_survey_statuses` LIKE `survey_statuses`');
        $this->execute('INSERT INTO `zz_7271_survey_statuses` SELECT * FROM `survey_statuses`');

        $this->execute('ALTER TABLE `survey_statuses` ADD `survey_filter_id` INT NOT NULL AFTER `survey_form_id`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `name` INT NOT NULL AFTER `survey_form_id`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `custom_module_id` INT NOT NULL AFTER `name`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `modified_user_id` INT DEFAULT NULL AFTER `name`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `modified` datetime DEFAULT NULL AFTER `modified_user_id`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `created_user_id` INT DEFAULT NULL AFTER `modified`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `created` datetime DEFAULT NULL AFTER `created_user_id`');

        // create survey_filters table
       $this->execute('CREATE TABLE `survey_filters` (
                      `id` int(11) NOT NULL,
                      `survey_filter_id` int(11) NOT NULL,
                      `institution_type_id` int(11) NOT NULL,
                      `institution_provider_id` int(11) NOT NULL,
                      `area_education_id` int(11) NOT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) DEFAULT NULL,
                      `created` datetime DEFAULT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1');
        $this->execute('INSERT INTO `survey_filters` SELECT * FROM `survey_filters`');

        // drop survey_filter_id id
        $this->execute('ALTER TABLE `survey_forms_filters` DROP `survey_filter_id`');
        
    }

    public function down()
    {

        $this->execute('DROP TABLE IF EXISTS `survey_forms_filters`');
        $this->execute('RENAME TABLE `zz_7271_survey_forms_filters` TO `survey_forms_filters`');

        $this->execute('DROP TABLE IF EXISTS `survey_statuses`');
        $this->execute('RENAME TABLE `zz_7271_survey_statuses` TO `survey_statuses`');

    }
}
