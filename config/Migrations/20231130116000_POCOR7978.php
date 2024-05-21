<?php
use Migrations\AbstractMigration;

class POCOR7978 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7978_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_7978_config_items` SELECT * FROM `config_items`');


        // CREATE summary tables and INSERT new rows into report_queries table
        $this->execute('INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, "Ask if Refugee", "Ask if Refugee", "Add New Student", "Ask if Refugee", "0", "", "0", "1", "1", "Dropdown", "completeness", NULL, NULL, "1", CURRENT_TIMESTAMP)');
    }

    // rollback
    public function down()
    {

        // Restore table
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_7978_config_items` TO `config_items`');

    }
}
