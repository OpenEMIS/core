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
        $this->execute('CREATE TABLE `z_7156_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_7156_config_items` SELECT * FROM `config_items`');
        //Create table `security_user_codes`
        $table = $this->table('security_user_codes', [
                'collation' => 'utf8_general_ci',
                'comment' =>  'This table will contain the OTP of the security users when login by two-factor authentication is enabled'
            ]);
        $table->addColumn('security_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('verification_otp', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->save();

        $this->execute("ALTER TABLE `security_user_codes` ADD INDEX(`security_user_id`)");
        
        //INSERT data `Two Factor Authentication` in config_items table
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Two Factor Authentication (Email)', 'two_factor_authentication', 'Authentication', 'Two Factor Authentication', '1', '', '0', '1', '1', 'Dropdown', 'completeness', '1', '".date('Y-m-d H:i:s')."', '1', '".date('Y-m-d H:i:s')."')"); 
        //INSERT data in report_queries table for cron (Karl Provided)
        $this->execute("INSERT INTO `report_queries` (`name`, `query_sql`, `frequency`, `status`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('clear_otp', 'DELETE FROM security_user_codes WHERE created < DATE_SUB(NOW(), INTERVAL `1` HOUR);', 'hour', 1, NULL, NULL, 1, '".date('Y-m-d H:i:s')."')");    
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `z_7156_config_items` TO `config_items`');
        $this->execute('DROP TABLE security_user_codes');
    }
}
