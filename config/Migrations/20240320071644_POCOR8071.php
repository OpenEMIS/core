<?php
use Migrations\AbstractMigration;

class POCOR8071 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_8071_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_8071_config_items` SELECT * FROM `config_items`');


        // CREATE config_items tables and INSERT new rows into config_items table

        //for Infrastructure 
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Maximum institution infrastructure land size', 'Maximum_institution_infrastructure_land_size', 'Infrastructure', 'Maximum institution infrastructure land size', '', '', NULL, '0', '1', '', '', '2', CURRENT_TIMESTAMP, '2', CURRENT_TIMESTAMP)");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Maximum institution infrastructure building size', 'Maximum_institution_infrastructure_building_size', 'Infrastructure', 'Maximum institution infrastructure building size', '', '', NULL, '0', '1', '', '', '2', CURRENT_TIMESTAMP, '2', CURRENT_TIMESTAMP)");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Maximum institution infrastructure floor size', 'Maximum_institution_infrastructure_floor_size', 'Infrastructure', 'Maximum institution infrastructure floor size', '', '', NULL, '0', '1', '', '', '2', CURRENT_TIMESTAMP, '2', CURRENT_TIMESTAMP)");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Maximum institution infrastructure room size', 'Maximum_institution_infrastructure_room_size', 'Infrastructure', 'Maximum institution infrastructure room size', '', '', NULL, '0', '1', '', '', '2', CURRENT_TIMESTAMP, '2', CURRENT_TIMESTAMP)");

        //for Health
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Minimum Height', 'StudentMinimumHeight', 'Health', 'Minimum Height', '', '', NULL, '0', '1', '', '', '2', CURRENT_TIMESTAMP, '2', CURRENT_TIMESTAMP)");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Maximum Height', 'StudentMaximumHeight', 'Health', 'Maximum Height', '', '', NULL, '0', '1', '', '', '2', CURRENT_TIMESTAMP, '2', CURRENT_TIMESTAMP)");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Minimum Weight', 'StudentMinimumWeight', 'Health', 'Minimum Weight', '', '', NULL, '0', '1', '', '', '2', CURRENT_TIMESTAMP, '2', CURRENT_TIMESTAMP)");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Maximum Weight', 'StudentMaximumWeight', 'Health', 'Maximum Weight', '', '', NULL, '0', '1', '', '', '2', CURRENT_TIMESTAMP, '2', CURRENT_TIMESTAMP)");

        //for Add new staff
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Staff Minimum Age', 'StaffMinimumAge', 'Add New Staff', 'Staff Minimum Age', '', '', '0', '0', '1', '', '', '2', CURRENT_TIMESTAMP, '2', CURRENT_TIMESTAMP)");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Staff Maximum Age', 'StaffMaximumAge', 'Add New Staff', 'Staff Maximum Age', '', '', '0', '0', '1', '', '', '2', CURRENT_TIMESTAMP, '2', CURRENT_TIMESTAMP)");
        
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_8071_config_items` TO `config_items`');

    }
}
