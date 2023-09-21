<?php
use Migrations\AbstractMigration;

class POCOR2976 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_2976_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_2976_config_items` SELECT * FROM `config_items`');
        $configData = [
                'name' => 'Account will be locked after a number of unsuccessful login attempts',
                'code' => 'login_attempts',
                'type' => 'System',
                'label' => 'Account will be locked after a number of unsuccessful login attempts',
                'value' => 5,
                'value_selection' => '',
                'default_value' => 10,
                'editable' => 1,
                'visible' => 1,
                'field_type' => "",
                'option_type' => "",
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
                'modified' => null,
                'modified_user_id' => null
        ];
        $this->insert('config_items', $configData);
    }

    public function down()
    {
        $this->dropTable("config_items");
        $this->table("zz_2976_config_items")->rename("config_items");
    }
}
