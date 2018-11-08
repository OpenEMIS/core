<?php

use Phinx\Migration\AbstractMigration;

class POCOR4876 extends AbstractMigration
{
    public function up()
    {
        // backup the table
        $this->execute('CREATE TABLE `z_4876_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_4876_config_items` SELECT * FROM `config_items`');
        // end backup

        //Insert 3 rows into config item table for Staff Release.
        $this->insert('config_items', [
            'id' => 1020,
            'name' => 'Enable Staff Release By Types',
            'code' => 'staff_release_by_types',
            'type' => 'Staff Releases',
            'label' => 'Enable Staff Release By Types',
            'value' => "",
            'default_value' => '{"selection":"0"}',
            'editable' => 1,
            'visible' => 1,
            'field_type' => "",
            'option_type' => "",
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('config_items', [
            'id' => 1021,
            'name' => 'Enable Staff Release By Sectors',
            'code' => 'staff_release_by_sectors',
            'type' => 'Staff Releases',
            'label' => 'Enable Staff Release By Sectors',
            'value' => "",
            'default_value' => '{"selection":"0"}',
            'editable' => 1,
            'visible' => 1,
            'field_type' => "",
            'option_type' => "",
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('config_items', [
            'id' => 1022,
            'name' => 'Restrict Staff Release Between Same Type',
            'code' => 'restrict_staff_release_between_same_type',
            'type' => 'Staff Releases',
            'label' => 'Restrict Staff Release Between Same Type',
            'value' => "",
            'default_value' => "0",
            'editable' => 1,
            'visible' => 1,
            'field_type' => "Dropdown",
            'option_type' => "yes_no",
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->execute('DROP TABLE config_items');
        $this->table('z_4876_config_items')->rename('config_items');
    }
}
