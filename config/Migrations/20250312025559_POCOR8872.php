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

    /**
     * @return void
     */
    public function renameExternalDataSourceAttributes()
    {
//        $this->execute("UPDATE external_data_source_attributes SET
//                        external_data_source_type='OpenEMIS Core'
//                    WHERE external_data_source_type='OpenEMIS Identity'");
////        $this->execute("UPDATE external_data_source_attributes SET
////                        name='Infrastructure Land',
////                        label='Infrastructure Land',
////                        code='infrastructure_land'
////                    WHERE external_data_source_type='OpenEMIS Identity'
////                      AND code='infrastructure'");
//        $this->execute("UPDATE config_items SET
//                        type='Personal Data Completeness',
//                        code=CONCAT('personal_', `code`)
//                    WHERE type='User Data Completeness'");
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
        $password = (new DefaultPasswordHasher)->hash($password);

        $attributes = [
            // API Credentials
            ['api_url', 'api_url', 'https://demo.openemis.org/api/v5'],
            ['username', 'username', 'admin'],
            ['password', 'password', $password],
            ['api_key', 'api_key', 'apikeytest'],

            // Identity Mappings
            ['external_reference_mapping', 'external_reference_mapping', 'id'],
            ['openemis_no_mapping', 'openemis_no_mapping', 'openemis_no'],
            ['first_name_mapping', 'first_name_mapping', 'first_name'],
            ['middle_name_mapping', 'middle_name_mapping', 'middle_name'],
            ['last_name_mapping', 'last_name_mapping', 'last_name'],
            ['third_name_mapping', 'third_name_mapping', 'third_name'],
            ['date_of_birth_mapping', 'date_of_birth_mapping', 'date_of_birth'],
            ['gender_id_mapping', 'gender_id_mapping', 'gender_id'],
//            ['nationality_mapping', 'nationality_mapping', 'main_nationality.name'],
//            ['identity_number_mapping', 'identity_number_mapping', 'identity_number'],
//            ['identity_type_mapping', 'identity_type_mapping', 'main_identity_type.name'],


            // Address Mappings
//            ['emal_mapping', 'email_mapping', 'email'],
//            ['address_mapping', 'address_mapping', 'address'],
//            ['postal_mapping', 'postal_mapping', 'postal_code']
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
