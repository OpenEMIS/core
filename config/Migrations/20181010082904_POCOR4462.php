<?php
/**
 * MIGRATION POCOR4462 - To add base_url and token string to config items.
 *
 * PHP version 7.2
 *
 * @category  Migrations
 * @package   Migrations
 * @author    Ervin Kwan <ekwan@kordit.com>
 * @copyright 2018 KORDIT PTE LTD
 */
use Phinx\Migration\AbstractMigration;

class POCOR4462 extends AbstractMigration
{
    public function up()
    {
        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            (1030, "Base URL", "base_url", "Moodle API", "Base URL", "", "", 1, 1, "", "", 1, CURRENT_DATE())');

        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            (1031, "API Token", "api_token", "Moodle API", "API Token", "", "", 1, 1, "", "", 1, CURRENT_DATE())');

        //Using moodle api function name for `code`
        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            (1032, "Enable User Creation", "core_user_create_users", "Moodle API", "Enable User Creation", 0, 0, 1, 1, "Dropdown", "yes_no", 1, CURRENT_DATE())');
    }

    public function down()
    {
        $this->execute('DELETE FROM `config_items` WHERE `code` =  "api_token"');
        $this->execute('DELETE FROM `config_items` WHERE `code` = "base_url"');
        $this->execute('DELETE FROM `config_items` WHERE `code` = "core_user_create_users"');
    }
}
