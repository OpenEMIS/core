<?php

use Phinx\Migration\AbstractMigration;

class POCOR5248 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_5248_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_5248_config_items` SELECT * FROM `config_items`');

        $this->execute('CREATE TABLE `z_5248_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `z_5248_config_item_options` SELECT * FROM `config_item_options`');

        $this->execute("INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) values('Allow out of academic year','allow_out_academic_year','Staff Leave','Staff Leave','1','0','1','1','Dropdown','allow_out_academic_year','2','".date('Y-m-d H:i:s')."','1','".date('Y-m-d H:i:s')."')");
        $this->execute("INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) values('Allow number of year','allow_no_year','Staff Leave','Staff Leave','2',NULL,'1','1','Dropdown','allow_no_year','2','".date('Y-m-d H:i:s')."','1','".date('Y-m-d H:i:s')."')");

        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('allow_out_academic_year','No','0','1','1')");
        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('allow_out_academic_year','Yes','1','2','1')");
        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('allow_no_year','2yrs','2','1','1')");
        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('allow_no_year','3yrs','3','2','1')");
        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('allow_no_year','4yrs','4','3','1')");
        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('allow_no_year','5yrs','5','4','1')");
    }

    public function down()
    {
        $this->dropTable("config_items");
        $this->table("z_5248_config_items")->rename("config_items");
        $this->dropTable("config_item_options");
        $this->table("z_5248_config_item_options")->rename("config_item_options");
    }
}
