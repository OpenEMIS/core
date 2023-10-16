<?php
use Migrations\AbstractMigration;

class POCOR5676 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_5676_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_5676_config_items` SELECT * FROM `config_items`');

        //config_items
        $data = [
            [
                'name' => 'Automated Student Withdrawal Enabled',
                'code' => 'automated_student_withdrawal',
                'type' => 'Automated Student Withdrawal',
                'label' => 'Automated Student Withdrawal Enabled',
                'value' => '0',
                'default_value' => '0',
                'editable' => '1',
                'visible' => '1',
                'field_type' => 'Dropdown',
                'option_type' => 'yes_no',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '2017-03-20 11:43:54',
            ],
            [
                'name' => 'Automated Student Withdrawal after continous number of days absent',
                'code' => 'automated_student_days_absent',
                'type' => 'Automated Student Withdrawal',
                'label' => 'Automated Student Withdrawal after continous number of days absent',
                'value' => '40',
                'default_value' => '0',
                'editable' => '1',
                'visible' => '1',
                'field_type' => '',
                'option_type' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '2017-03-20 11:43:54',
            ],
            [
                'name' => 'Automated Student Withdrawal Reasons',
                'code' => 'student_withdraw_reasons',
                'type' => 'Automated Student Withdrawal',
                'label' => 'Automated Student Withdrawal Reasons',
                'value' => '669',
                'default_value' => '669',
                'editable' => '1',
                'visible' => '1',
                'field_type' => 'Dropdown',
                'option_type' => 'database:Student.StudentWithdrawReasons',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '2017-03-20 11:43:54',
            ],
            [
                'name' => 'Automated Student Withdrawal last run',
                'code' => 'date_time_format',
                'type' => 'Automated Student Withdrawal',
                'label' => 'Automated Student Withdrawal last run',
                'value' => '22-02-2021',
                'default_value' => '5',
                'editable' => '1',
                'visible' => '1',
                'field_type' => '',
                'option_type' => '',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '2017-03-20 11:43:54',
            ],
        ];

        $this->insert('config_items', $data);  
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_5676_config_items` TO `config_items`');
    }
}
