<?php

use Phinx\Migration\AbstractMigration;

class POCOR4546 extends AbstractMigration
{
    public function up()
    {
        $this->execute('INSERT INTO `config_items` 
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            (1015, "Max Students Per Class", "max_students_per_class", "Student Settings", "Max Students Per Class", "", "0", 1, 1, "", "", 1, CURRENT_DATE())');

        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            (1016, "Max Students Per Subject", "max_students_per_subject", "Student Settings", "Max Students Per Subject", "", "0", 1, 1, "", "", 1, CURRENT_DATE())');
    }

    public function down()
    {
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1015');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1016');
    }
}
