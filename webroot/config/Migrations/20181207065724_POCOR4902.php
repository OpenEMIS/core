<?php

use Phinx\Migration\AbstractMigration;

class POCOR4902 extends AbstractMigration
{

    public function up()
    {
        // Back up table
        $this->execute('CREATE TABLE `z_4902_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_4902_config_items` SELECT * FROM `config_items`');
        $this->execute('CREATE TABLE `z_4902_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `z_4902_config_item_options` SELECT * FROM `config_item_options`');
        // end backup

        $institutionConfigOptionData = [
            [
                'id' => 39,
                'option_type' => 'school_landing',
                'option' => 'Dashboard',
                'value' => '0',
                'order' => 1,
                'visible' => 1
            ],
            [
                'id' => 38,
                'option_type' => 'school_landing',
                'option' => 'Overview',
                'value' => '1',
                'order' => 2,
                'visible' => 1
            ]
        ];

        $this->insert('config_item_options', $institutionConfigOptionData);

         // Insert for School Landing Page configuration under Institution in config_items table
        $institutionConfigData = [
            'id' => 1023,
            'name' => 'Default School Landing Page',
            'code' => 'default_school_landing_page',
            'type' => 'Institution',
            'label' => 'Default School Landing Page',
            'value' => '',
            'default_value' => '0',
            'editable' => 1,
            'visible' => 1,
            'field_type' => "Dropdown",
            'option_type' => "school_landing",
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s'),
        ];

        $this->insert('config_items', $institutionConfigData);
    }

    public function down()
    {
        // Restore backups
        $this->execute('DROP TABLE config_items');
        $this->table('z_4902_config_items')->rename('config_items');
        $this->execute('DROP TABLE config_item_options');
        $this->table('z_4902_config_item_options')->rename('config_item_options');
    }

}
