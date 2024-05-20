<?php
use Migrations\AbstractMigration;

class POCOR7050 extends AbstractMigration
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
        // Creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_7050_config_items`');
        $this->execute('CREATE TABLE `zz_7050_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_7050_config_items` SELECT * FROM `config_items`');

        // Creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_7050_config_item_options`');
        $this->execute('CREATE TABLE `zz_7050_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `zz_7050_config_item_options` SELECT * FROM `config_item_options`');

        $configItemData = [
            [
                'name' => 'Rule to Calculate Daily Attendance/Absence',
                'code' => 'calculate_daily_attendance',
                'type' => 'Student Report Card',
                'label' => 'Rule to Calculate Daily Attendance/Absence',
                'value' => '1',
                'value_selection' => '',
                'default_value' => '',
                'editable' => '1',
                'visible' => '1',
                'field_type' => 'Dropdown',
                'option_type' => 'calculate_daily_attendance',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
        ];

        $this->insert('config_items', $configItemData);

        $configitemOption = [
            [
                'option_type' => 'calculate_daily_attendance',
                'option' => 'Mark absent if one or absent records',
                'value' => '1',
                'order' => '1',
                'visible' => '1'
            ],
            [
                'option_type' => 'calculate_daily_attendance',
                'option' => 'Mark present if one or present records',
                'value' => '2',
                'order' => '2',
                'visible' => '1'
            ],
        ];

        $this->insert('config_item_options', $configitemOption);
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_7050_config_items` TO `config_items`');

        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `zz_7050_config_item_options` TO `config_item_options`');
    }
}
