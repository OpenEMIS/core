<?php

use Phinx\Migration\AbstractMigration;

class POCOR4822 extends AbstractMigration
{
    public function up()
    {   
        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            (1019, "Latitude And Longitude", "latitude_longitude", "Institution", "Latitude And Longitude", "1", "0", 1, 1, "Dropdown", "wizard", 1, CURRENT_DATE())');        
    }

    public function down()
    {
        $this->execute('DELETE FROM `config_items` WHERE `id` =  1019');
    }
}
