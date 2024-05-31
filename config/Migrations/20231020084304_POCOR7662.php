<?php
use Migrations\AbstractMigration;

class POCOR7662 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7662_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_7662_config_items` SELECT * FROM `config_items`');

        $this->execute('CREATE TABLE `zz_7662_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `zz_7662_config_item_options` SELECT * FROM `config_item_options`');

        $this->execute('INSERT INTO `config_items` 
            (`name`, `code`, `type`, `label`, `value`, `value_selection`,`default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            ("Default delivery status", "DefaultDeliveryStatus", "Meals", "Default delivery status", "Received", "1", "0", 1, 1, "Dropdown", "meal_type", 1, CURRENT_DATE())');


        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('meal_type','Received','Received','1','1')");
        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('meal_type','Not Received','Not Received','2','1')");
        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('meal_type','None','None','3','1')");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_7662_config_items` TO `config_items`');
        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `zz_7662_config_item_options` TO `config_item_options`');
    }
}
