<?php
use Migrations\AbstractMigration;

class POCOR6808 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6808_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_6808_config_items` SELECT * FROM `config_items`');

        /** Inserting new row*/
        $rowData = [
            [   
                'name' => 'API Webhook Logging',
                'code' => 'api_webhook_logging',
                'type' => 'API Settings',
                'label' => 'API Webhook Logging',
                'value' => 1,
                'default_value' => 0,
                'field_type' => 'Dropdown',
                'option_type'=> 'completeness',
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
        $this->execute('RENAME TABLE `zz_6808_config_items` TO `config_items`');
    }
}
