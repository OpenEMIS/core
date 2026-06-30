<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9738 extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9738_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_9738_config_items` SELECT * FROM `config_items`');

        $exists = $this->fetchRow("SELECT `id` FROM `config_items` WHERE `code` = 'password_rotation'");
        if (empty($exists)) {
            $this->execute("INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
                ('Password Rotation', 'password_rotation', 'Password', 'Password Rotation', '0', '', '0', '0', '1', 'Dropdown', 'completeness', NULL, NULL, '1', CURRENT_TIMESTAMP)");
        }
    }

    public function down(): void
    {
        $this->execute("DELETE FROM `config_items` WHERE `code` = 'password_rotation'");
        $this->execute('DROP TABLE IF EXISTS `zz_9738_config_items`');
    }
}
