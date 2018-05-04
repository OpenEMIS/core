<?php

use Phinx\Migration\AbstractMigration;

class POCOR4549 extends AbstractMigration
{
    public function up()
    {
        $this->execute('INSERT INTO `config_items` 
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            (1013, "Enable Staff Transfer", "enable_staff_transfer", "Staff Transfers", "Enable Staff Transfer", "", "-1", 1, 1, "", "", 1, CURRENT_DATE())');

        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            (1014, "Restrict Staff Transfer Between Same Sector", "restrict_staff_transfer_by_sector", "Staff Transfers", "Restrict Staff Transfer Between Same Sector", "", "0", 1, 1, "Dropdown", "yes_no", 1, CURRENT_DATE())');
    }

    public function down()
    {
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1013');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1014');
    }
}
