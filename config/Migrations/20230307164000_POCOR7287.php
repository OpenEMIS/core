<?php

use Phinx\Migration\AbstractMigration;

/**
**/
class POCOR7287 extends AbstractMigration
{
    // commit
    public function up()
    {
        // Backup table
        $this->execute('CREATE TABLE `zz_7287_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_7287_config_items` SELECT * FROM `config_items`');

        $this->execute("INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('Allow OpenEMIS Registrations to Add New Students', 'NewStudent', 'Add New Student', 'New Student', '1', '', '0', '1', '1', 'Dropdown', 'completeness', NULL, NULL, '1', now())");
    }

    // rollback
    public function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_7287_config_items` TO `config_items`');

    }
}

?>