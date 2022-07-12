<?php
use Migrations\AbstractMigration;

class POCOR5672 extends AbstractMigration
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

        // $this->execute('CREATE TABLE `zz_5672_config_items` LIKE `config_items`');
        // $this->execute('INSERT INTO `zz_5672_config_items` SELECT * FROM `config_items`');

        $table = $this->table('config_items');
        $data = [
            [
                'id' => NULL,
                'name' => 'Redirect to Guardians',
                'code' => 'RedirectToGuardian',
                'type' => 'Add New Student',
                'label' => 'Redirect to Guardians',
                'value' => '0',
                'value_selection' => '',
                'default_value' => '0',
                'editable' => '1',
                'visible' => '1',
                'field_type' => 'Dropdown',
                'option_type' => 'completeness',
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
        ];

        $table->insert($data)->save();
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_5672_config_items` TO `config_items`');
    }
}

