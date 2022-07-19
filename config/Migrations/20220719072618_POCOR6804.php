<?php
use Migrations\AbstractMigration;

class POCOR6804 extends AbstractMigration
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
        // Backup table
        // $this->execute('CREATE TABLE `zz_6804_config_items` LIKE `config_items`');
        // $this->execute('INSERT INTO `zz_6804_config_items` SELECT * FROM `config_items`');
        // End

        $table = $this->table('config_items');
        $data = [
            [
                'name' => 'API Token',
                'code' => 'api_settings',
                'type' => 'API Settings',
                'label' => 'API Settings',
                'value' => 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiT3BlbkVNSVMgQ29yZSIsInR5cGUiOiJhcGlfc2V0dGluZ3MifQ.I9rsqLEsSOUH5eYhUhk0BSu-tZ0343NBchTaJRylPmFTma4xfbObNMK_XIA51pFiIX8pVhWnmrVcpnxHYFguOA',
                'value_selection' => '',
                'default_value' => 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiT3BlbkVNSVMgQ29yZSIsInR5cGUiOiJhcGlfc2V0dGluZ3MifQ.I9rsqLEsSOUH5eYhUhk0BSu-tZ0343NBchTaJRylPmFTma4xfbObNMK_XIA51pFiIX8pVhWnmrVcpnxHYFguOA',
                'editable' => '1',
                'visible' => '1',
                'field_type' => '',
                'option_type' => '',
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' =>  date('Y-m-d H:i:s')
            ],
        ];

        $table->insert($data)->save();
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_6804_config_items` TO `config_items`');
    }
}
