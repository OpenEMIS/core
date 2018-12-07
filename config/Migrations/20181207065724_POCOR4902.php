<?php

use Phinx\Migration\AbstractMigration;

class POCOR4902 extends AbstractMigration
{

    public function up()
    {
        // Back up table
        $this->execute('CREATE TABLE `z_4902_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_4902_config_items` SELECT * FROM `config_items`');
        // end backup

        //Insert for School Landing Page configuration under Institution in config_items table
        $institutionConfigData = [
            'id' => 1023,
            'name' => 'Default School Landing Page',
            'code' => 'default_school_landing_page',
            'type' => 'Institution',
            'label' => 'Default School Landing Page',
            'value' => '1',
            'default_value' => '0',
            'editable' => 1,
            'visible' => 1,
            'field_type' => "Dropdown",
            'option_type' => "wizard",
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ];
        $this->insert('config_items', $institutionConfigData);
    }

    public function down()
    {
        // Restore backups
        $this->execute('DROP TABLE config_items');
        $this->table('z_4902_config_items')->rename('config_items');
    }

}
