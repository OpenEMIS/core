<?php

use Phinx\Migration\AbstractMigration;

class POCOR4546 extends AbstractMigration
{
    public function up()
    {
        $this->execute('INSERT INTO `config_items` 
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            (1017, "Maximum Students Per Class", "max_students_per_class", "Student Settings", "Maximum Students Per Class", "", "100", 1, 1, "", "", 1, CURRENT_DATE())');

        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            (1018, "Maximum Students Per Subject", "max_students_per_subject", "Student Settings", "Maximum Students Per Subject", "", "100", 1, 1, "", "", 1, CURRENT_DATE())');
    }

    public function down()
    {
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1017');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1018');
    }
}
