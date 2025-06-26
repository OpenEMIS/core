<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Utility\Text;

class POCOR8872 extends AbstractMigration
{
    public function up()
    {


        $this->backupTables();
//
        $this->insertNewExternalDataSourceAttributes();
//
        $this->insertNewConfigItems();

    }

    // rollback

    /**
     * @return void
     */
    public function backupTables()
    {
        if(!$this->hasTable('z_8872_config_items')){
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_8872_config_items` LIKE `config_items`');
            $this->execute('INSERT INTO `z_8872_config_items` SELECT * FROM `config_items`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if(!$this->hasTable('z_8872_external_data_source_attributes')){
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_8872_external_data_source_attributes` LIKE `external_data_source_attributes`');
            $this->execute('INSERT INTO `z_8872_external_data_source_attributes` SELECT * FROM `external_data_source_attributes`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    public function down()
    {
        $this->restoreTable();
    }

    /**
     * @return void
     */
    public function restoreTable()
    {
        if ($this->hasTable('z_8872_config_items')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `config_items`');
            $this->execute('RENAME TABLE `z_8872_config_items` TO `config_items`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if ($this->hasTable('z_8872_external_data_source_attributes')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `external_data_source_attributes`');
            $this->execute('RENAME TABLE `z_8872_external_data_source_attributes` TO `external_data_source_attributes`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    function generateConfigData($name, $code, $type, $label) {
        return [
            'id' => NULL,
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'label' => $label,
            'value' => '1',
            'value_selection' => '0',
            'default_value' => '0',
            'editable' => '1',
            'visible' => '1',
            'field_type' => 'Dropdown',
            'option_type' => 'completeness',
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * @return void
     */
    public function insertNewConfigItems()
    {
        $table = $this->table('config_items');
        $data = [
            $this->generateConfigData(
                'OpenEMIS Core',
                'external_data_source_openemis_core',
                'External Data Source - Identity',
                'OpenEMIS Core'),
        ];
        $table->insert($data)->save();
    }

    public function insertNewExternalDataSourceAttributes()
    {
        $table = $this->table('external_data_source_attributes');
        $password = 'demo';
//        $password = (new DefaultPasswordHasher)->hash($password);

        $attributes = [
            // API Credentials
            ['api_url', 'api_url', 'https://demo.openemis.org/core/api/v5'],
            ['username', 'username', 'admin'],
            ['password', 'password', $password],
            ['api_key', 'api_key', 'apikeytest'],

        ];

        $data = array_map(fn($attr) => $this->generateExternalDataSourceAttribute('OpenEMIS Core', ...$attr), $attributes);

        $table->insert($data)->save();
    }

    private function generateExternalDataSourceAttribute($type, $field, $name, $value)
    {
        return [
            'id' => Text::uuid(),
            'external_data_source_type' => $type,
            'attribute_field' => $field,
            'attribute_name' => $name,
            'value' => $value,
            'created' => date('Y-m-d H:i:s'),
            'created_user_id' => 1,
        ];
    }

}
