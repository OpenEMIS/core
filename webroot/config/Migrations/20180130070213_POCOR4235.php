<?php

use Phinx\Migration\AbstractMigration;

class POCOR4235 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {
        $this->execute('INSERT INTO `config_items` 
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            (1006, "Identities", "GuardianIdentities", "Add New Guardian", "Identities", "0", "0", 1, 1, "Dropdown", "wizard", 1, CURRENT_DATE())');

        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            (1007, "Nationalities", "GuardianNationalities", "Add New Guardian", "Nationalities", "0", "0", 1, 1, "Dropdown", "wizard", 1, CURRENT_DATE())');

        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            (1008, "Identities", "OtherIdentities", "Add New Other", "Identities", "0", "0", 1, 1, "Dropdown", "wizard", 1, CURRENT_DATE())');

        $this->execute('INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            (1009, "Nationalities", "OtherNationalities", "Add New Other", "Nationalities", "0", "0", 1, 1, "Dropdown", "wizard", 1, CURRENT_DATE())');
    }

    public function down()
    {
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1006');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1007');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1008');
        $this->execute('DELETE FROM `config_items` WHERE `id` = 1009');
    }
}
