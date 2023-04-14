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
      //  $this->execute('CREATE TABLE `zz_7271_survey_forms_filters` LIKE `survey_forms_filters`');
        //$this->execute('INSERT INTO `zz_7271_survey_forms_filters` SELECT * FROM `survey_forms_filters`');

        //$this->execute('CREATE TABLE `zz_7271_survey_statuses` LIKE `survey_statuses`');
        //$this->execute('INSERT INTO `zz_7271_survey_statuses` SELECT * FROM `survey_statuses`');

       // $this->execute('ALTER TABLE `survey_statuses` ADD `survey_filter_id` INT(11) NOT NULL AFTER `survey_form_id`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `name` varchar(255) NOT NULL AFTER `id`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `custom_module_id` INT(11) NOT NULL AFTER `survey_form_id`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `institution_type_id` varchar(255) NOT NULL AFTER `custom_module_id`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `institution_provider_id` varchar(255) NOT NULL AFTER `institution_type_id`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `area_education_id` varchar(255) NOT NULL AFTER `institution_provider_id`');

        $this->execute('ALTER TABLE `survey_forms_filters` ADD `modified_user_id` INT(11) DEFAULT NULL AFTER `institution_provider_id`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `modified` datetime DEFAULT NULL AFTER `modified_user_id`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `created_user_id` INT(11) DEFAULT NULL AFTER `modified`');
        $this->execute('ALTER TABLE `survey_forms_filters` ADD `created` datetime DEFAULT NULL AFTER `created_user_id`');
        // drop survey_filter_id id
        $this->execute('ALTER TABLE `survey_forms_filters` DROP `survey_filter_id`');
        
    }

    public function down()
    {

       // $this->execute('DROP TABLE IF EXISTS `survey_forms_filters`');
        //$this->execute('RENAME TABLE `zz_7271_survey_forms_filters` TO `survey_forms_filters`');

        $this->execute('DROP TABLE IF EXISTS `survey_statuses`');
        //$this->execute('RENAME TABLE `zz_7271_survey_statuses` TO `survey_statuses`');

    }
}
