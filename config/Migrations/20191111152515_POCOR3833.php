<?php

use Phinx\Migration\AbstractMigration;

class POCOR3833 extends AbstractMigration
{
    public function up()
    {
		//config items
        $this->execute('CREATE TABLE `z_3833_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_3833_config_items` SELECT * FROM `config_items`');
        
        $this->execute('ALTER TABLE `config_items` MODIFY id INT NOT NULL AUTO_INCREMENT');
		$this->execute("INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES('Configure Student Name','configure_student_name','Student Settings','Configure Student Name','0','0','1','1','Dropdown','name_type','2','2019-11-11 08:21:48','1','2019-11-08 15:32:13')");
		
		//config item options		
		$this->execute('CREATE TABLE `z_3833_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `z_3833_config_item_options` SELECT * FROM `config_item_options`');
		
		$this->execute('ALTER TABLE config_item_options MODIFY id INT NOT NULL AUTO_INCREMENT');
		$this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) VALUES('name_type','identity - first_name last_name','0','1','1')");
		$this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) VALUES('name_type','identity - full_name','1','2','1')");
    }

     public function down()
    {
        // config items
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `z_3833_config_items` TO `config_items`');
		
		// config item options
        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `z_3833_config_item_options` TO `config_items`');
	}
}
