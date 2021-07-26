<?php
use Migrations\AbstractMigration;

class POCOR5177 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `zz_5177_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_5177_config_items` SELECT * FROM `config_items`');

        $record = [
            [
                'name' => 'Staff Behavior',
                'code' => 'staff_behavior',
                'type' => 'Delete Requests',
                'label' => 'Staff Behavior',
                'value' => 0,
                'default_value' => 0,
                'editable' => 1,
                'visible' => 1,
                'field_type' => '',
                'option_type' => '',
                'created_user_id' => 2,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Student Behavior',
                'code' => 'student_behavior',
                'type' => 'Delete Requests',
                'label' => 'Student Behavior',
                'value' => 0,
                'default_value' => 0,
                'editable' => 1,
                'visible' => 1,
                'field_type' => '',
                'option_type' => '',
                'created_user_id' => 2,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        //inserting record
        $this->insert('config_items', $record); 
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_5177_config_items` TO `config_items`');
    }
}
