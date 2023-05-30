<?php
use Phinx\Migration\AbstractMigration;
  /**
 * POCOR-7289
 * adding homeroom teacher
**/
class POCOR7402 extends AbstractMigration
{

    public function up()
    {
       // Backup table
       $this->execute('CREATE TABLE `zz_7402_config_items` LIKE `config_items`');
       $this->execute('INSERT INTO `zz_7402_config_items` SELECT * FROM `config_items`');
       
        $data=[
            [
                'name' => 'Display Address Area Level',
                'code' => 'address_area_level',
                'type' => 'Add New Student',
                'label' => 'Display Address Area Level',
                'value' => '1',
                'value_selection'=>'1',
                'default_value' => '1',
                'editable' => '1',
                'visible' => '1',
                'field_type' => 'Dropdown',
                'option_type' => 'database:Area.AreaAdministrativeLevels',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Display Birthplace Area Level',
                'code' => 'birthplace_area_level',
                'type' => 'Add New Student',
                'label' => 'Display Birthplace Area Level',
                'value' => '1',
                'value_selection'=>'1',
                'default_value' => '1',
                'editable' => '1',
                'visible' => '1',
                'field_type' => 'Dropdown',
                'option_type' => 'database:Area.AreaAdministrativeLevels',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
          ];
        $this->insert('config_items', $data);
    }

    public function down(){

        // Restore table
        $this->execute('DROP TABLE IF EXISTS `zz_7402_config_items`');
        $this->execute('RENAME TABLE `zz_7402_config_items` TO `config_items`');

    }
}
?>