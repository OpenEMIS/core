<?php

use Phinx\Migration\AbstractMigration;

class POCOR4549 extends AbstractMigration
{
    public function up()
    {
        $this->execute('INSERT INTO `config_items` 
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            (1013, "Enable Staff Transfer By Types", "enable_staff_transfer_by_types", "Staff Transfers", "Enable Staff Transfer By Types", "", "-1", 1, 1, "", "", 1, CURRENT_DATE())');

        $this->execute('INSERT INTO `config_items` 
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            (1014, "Enable Staff Transfer By Sectors", "enable_staff_transfer_by_sectors", "Staff Transfers", "Enable Staff Transfer By Sectors", "", "-1", 1, 1, "", "", 1, CURRENT_DATE())');

        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            (1015, "Restrict Staff Transfer Between Same Type", "restrict_staff_transfer_by_type", "Staff Transfers", "Restrict Staff Transfer Between Same Type", "", "0", 1, 1, "Dropdown", "yes_no", 1, CURRENT_DATE())');

        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            (1016, "Restrict Staff Transfer Between Same Provider", "restrict_staff_transfer_by_provider", "Staff Transfers", "Restrict Staff Transfer Between Same Provider", "", "0", 1, 1, "Dropdown", "yes_no", 1, CURRENT_DATE())');
    }

    public function down()
    {
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1013');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1014');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1015');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1016');
    }
}
