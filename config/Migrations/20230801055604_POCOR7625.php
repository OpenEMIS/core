<?php

use Phinx\Migration\AbstractMigration;

class Pocor7625 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        /** backup */
        $this->execute('CREATE TABLE `zz_7625_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_7625_config_items` SELECT * FROM `config_items`');

        /** Inserting new row*/
        $rowData = [
            [
                'name' => 'Archiving Hides Academic Period',
                'code' => 'archiving_hides_academic_period',
                'type' => 'System',
                'label' => 'Archiving Hides Academic Period',
                'value' => 1,
                'value_selection' => '',
                'default_value' => 0,
                'field_type' => 'Dropdown',
                'option_type'=> 'yes_no',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('config_items', $rowData);

        $rowData = [
            [
                'name' => 'Archiving Disables Academic Period',
                'code' => 'archiving_disables_academic_period',
                'type' => 'System',
                'label' => 'Archiving Disables Academic Period',
                'value' => 1,
                'value_selection' => '',
                'default_value' => 0,
                'field_type' => 'Dropdown',
                'option_type'=> 'yes_no',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('config_items', $rowData);
    }

    /** rollback */
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_7625_config_items` TO `config_items`');
    }
}

