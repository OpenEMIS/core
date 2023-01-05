<?php
use Migrations\AbstractMigration;

class POCOR7156 extends AbstractMigration
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
        
        //POCOR-7156
        //INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Two Factor Authentication (Email)', 'two_factor_authentication', 'Authentication', 'Two Factor Authentication', '1', '', '1', '1', '1', 'Dropdown', 'yes_no', '1', '2014-04-24 12:58:00', '1', '2013-08-20 14:46:02'); 

        //ALTER TABLE `system_user_otps` ADD INDEX(`security_user_id`); 

        
    }
}
