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

        $configItemData = [
            [
                'name' => 'Rule to Calculate Daily Attendance/Absence',
                'code' => 'calculate_daily_attendance_absence',
                'type' => 'Student Report Card',
                'label' => 'Rule to Calculate Daily Attendance/Absence',
                'value' => 'Mark present if one or present records',
                'value_selection' => '',
                'default_value' => '',
                'editable' => '1',
                'visible' => '1',
                'field_type' => 'Dropdown',
                'option_type' => 'wizard',
                'modified_user_id' => 'NULL',
                'modified' => 'NULL',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Rule to Calculate Daily Attendance/Absence',
                'code' => 'calculate_daily_attendance_absence',
                'type' => 'Student Report Card',
                'label' => 'Rule to Calculate Daily Attendance/Absence',
                'value' => 'Mark absent if one or absent records.',
                'value_selection' => '',
                'default_value' => '',
                'editable' => '1',
                'visible' => '1',
                'field_type' => 'Dropdown',
                'option_type' => 'wizard',
                'modified_user_id' => 'NULL',
                'modified' => 'NULL',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('config_items', $configItemData);
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_7050_config_items` TO `config_items`');
    }
}
