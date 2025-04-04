<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8951 extends AbstractMigration
{
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_8951_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_8951_config_items` SELECT * FROM `config_items`');
        $this->execute('CREATE TABLE `z_8951_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `z_8951_config_item_options` SELECT * FROM `config_item_options`');

        $this->execute('INSERT INTO `config_items` 
        (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
        VALUES 
        (NULL, "OpenEMIS Registration", "online_services", "Online Services", "Online Services", "1", "", "1", 0, 1, "Dropdown", "online_services", NULL, NULL, 1, CURRENT_TIMESTAMP)');


        $this->execute("INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES (NULL, 'online_services', 'Enabled', '1', '0', '1'), (NULL, 'online_services', 'Disabled', '0', '0', '1')");
       
    }

    public function down()
    {
        $this->execute('DROP TABLE `config_items`');
        $this->execute('RENAME TABLE IF EXISTS `z_8951_config_items` TO `config_items`');
        $this->execute('DROP TABLE `config_item_options`');
        $this->execute('RENAME TABLE IF EXISTS `z_8951_config_item_options` TO `config_item_options`');
        $this->execute('DROP TABLE IF EXISTS `z_8951_config_items`');
        $this->execute('DROP TABLE IF EXISTS `z_8951_config_item_options`');
    }

    
}
