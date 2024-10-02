<?php

use Migrations\AbstractMigration;
use Cake\Utility\Text;

class POCOR8386 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_8386_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_8386_config_items` SELECT * FROM `config_items`');
        $this->execute('CREATE TABLE `z_8386_external_data_source_attributes` LIKE `external_data_source_attributes`');
        $this->execute('INSERT INTO `z_8386_external_data_source_attributes` SELECT * FROM `external_data_source_attributes`');

        $this->execute('INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("","Moodle", "external_source", "External Data Source - LMS", "Moodle", "", "", "", "0", "1", "", "", NULL, NULL, "1", CURRENT_TIMESTAMP)');

        $this->execute('INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ("","Status", "external_source_status", "External Data Source - LMS", "Status", "1", "0", "0", 1,1,"Dropdown", "yes_no", 0, NULL, "1", CURRENT_TIMESTAMP)');

        $this->execute('INSERT INTO `external_data_source_attributes` 
            (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `modified`, `modified_user_id`, `created_user_id`, `created`) 
            VALUES ("' . Text::uuid() . '", "External Data Source - LMS", "api_token", "api_token", "", 0, NULL, "1", CURRENT_TIMESTAMP)');

        $this->execute('INSERT INTO `external_data_source_attributes` 
            (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `modified`, `modified_user_id`, `created_user_id`, `created`) 
            VALUES ("' . Text::uuid() . '", "External Data Source - LMS", "base_url", "base_url", "", 0, NULL, "1", CURRENT_TIMESTAMP)');

        $this->execute('INSERT INTO `external_data_source_attributes` 
            (`id`, `external_data_source_type`, `attribute_field`, `attribute_name`, `value`, `modified`, `modified_user_id`, `created_user_id`, `created`) 
            VALUES ("' . Text::uuid() . '", "External Data Source - LMS", "enable_user_creation", "enable_user_creation", "", 0, NULL, "1", CURRENT_TIMESTAMP)');

        // Delete Record
        $this->execute('DELETE FROM config_items WHERE type = "Moodle API"');
        
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('DROP TABLE IF EXISTS `external_data_source_attributes`');
        $this->execute('RENAME TABLE `z_8386_config_items` TO `config_items`');
        $this->execute('RENAME TABLE `z_8386_external_data_source_attributes` TO `external_data_source_attributes`');
    }

        
}
