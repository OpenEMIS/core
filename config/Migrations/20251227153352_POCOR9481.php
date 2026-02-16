<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Utility\Text;

class POCOR9481 extends AbstractMigration
{
    private $seychellesIdentityID = 0;
//    private $seychellesNationalityID = 0;
    public function up()
    {


        $this->backupTables();

        $this->insertNewExternalDataSourceAttributes();

        $this->insertIdentitySeychelles();

        $this->insertNewConfigItems();

        $this->insertNationalitySeychelles();   // <-- Add this
    }


    // rollback

    /**
     * @return void
     */
    public function backupTables()
    {
        if(!$this->hasTable('z_9481_config_items')){
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_9481_config_items` LIKE `config_items`');
            $this->execute('INSERT INTO `z_9481_config_items` SELECT * FROM `config_items`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if(!$this->hasTable('z_9481_external_data_source_attributes')){
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_9481_external_data_source_attributes` LIKE `external_data_source_attributes`');
            $this->execute('INSERT INTO `z_9481_external_data_source_attributes` SELECT * FROM `external_data_source_attributes`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if(!$this->hasTable('z_9481_identity_types')){
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_9481_identity_types` LIKE `identity_types`');
            $this->execute('INSERT INTO `z_9481_identity_types` SELECT * FROM `identity_types`');
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
        if ($this->hasTable('z_9481_config_items')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `config_items`');
            $this->execute('RENAME TABLE `z_9481_config_items` TO `config_items`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if ($this->hasTable('z_9481_external_data_source_attributes')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `external_data_source_attributes`');
            $this->execute('RENAME TABLE `z_9481_external_data_source_attributes` TO `external_data_source_attributes`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
        if ($this->hasTable('z_9481_identity_types')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `identity_types`');
            $this->execute('RENAME TABLE `z_9481_identity_types` TO `identity_types`');
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
                'Seychelles Civil Status',
                'external_data_source_seychelles_c_s',
                'External Data Source - Identity',
                'Seychelles Civil Status'),
        ];
        $table->insert($data)->save();
    }

    public function insertNewExternalDataSourceAttributes()
    {
        $table = $this->table('external_data_source_attributes');


        $attributes = [
            // API Credentials
            ['date_of_birth_mapping', 'date_of_birth_mapping', 'dob'],
            ['gender_mapping', 'gender_mapping', 'sex'],
            ['nationality_mapping', 'nationality_mapping', 'nationality'],

        ];

        $data = array_map(fn($attr) => $this->generateExternalDataSourceAttribute('Seychelles Civil Status', ...$attr), $attributes);

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

    public function insertIdentitySeychelles()
    {
        $identityName = 'National Identity Number (NIN)';

        // 1. Check if already exists
        $existing = $this->fetchRow("
        SELECT id FROM identity_types WHERE name = '{$identityName}'
    ");

        if ($existing) {
            // If present — store and exit
            $this->seychellesIdentityID = $existing['id'];
            return;
        }

        // 2. Get max order and max id
        $orderRow = $this->fetchRow("SELECT MAX(`order`) AS max_order FROM identity_types");
        $idRow    = $this->fetchRow("SELECT MAX(`id`) AS max_id FROM identity_types");

        $maxOrder = $orderRow['max_order'] ?? 0;
        $maxId    = $idRow['max_id'] ?? 0;

        $newId = $maxId + 1;

        // 3. Build insert record
        $data = [
            'id'                 => $newId,
            'name'               => $identityName,
            'validation_pattern' => null,
            'order'              => $maxOrder + 1,
            'visible'            => 1,
            'editable'           => 1,
            'default'            => 0,
            'international_code' => null,
            'national_code'      => null,
            'modified_user_id'   => null,
            'modified'           => null,
            'created_user_id'    => 1,
            'created'            => date('Y-m-d H:i:s')
        ];

        // 4. Insert new identity type
        $table = $this->table('identity_types');
        $table->insert($data)->save();

        // 5. Store for later use
        $this->seychellesIdentityID = $newId;
    }


    private function insertNationalitySeychelles()
    {
        $nationalityName = 'Seychellois';

        // 1. Check if already exists
        $existing = $this->fetchRow("
        SELECT id FROM nationalities WHERE name = '{$nationalityName}'
    ");

        if ($existing) {
            // Already present, nothing to do
            return;
        }

        // 2. Get identity_type_id for the newly inserted NIN identity
        $identityRow = $this->fetchRow("
        SELECT id FROM identity_types WHERE name = 'National Identity Number (NIN)'
    ");

        if (!$identityRow) {
            throw new \RuntimeException("Identity type 'National Identity Number (NIN)' not found — cannot create nationality.");
        }

        $identityTypeId = $identityRow['id'];

        // 3. Get external validation config id
        $configRow = $this->fetchRow("
        SELECT id FROM config_items WHERE code = 'external_data_source_seychelles_c_s'
    ");

        if (!$configRow) {
            throw new \RuntimeException("Config item 'external_data_source_seychelles_c_s' not found — cannot create nationality.");
        }

        $externalValidationId = $configRow['id'];

        // 4. Get order + new id
        $orderRow = $this->fetchRow("SELECT MAX(`order`) AS max_order FROM nationalities");
        $idRow    = $this->fetchRow("SELECT MAX(`id`) AS max_id FROM nationalities");

        $maxOrder = $orderRow['max_order'] ?? 0;
        $maxId    = $idRow['max_id'] ?? 0;

        $newNationalityId = $maxId + 1;

        // 5. Insert nationality record
        $data = [
            'id'                 => $newNationalityId,
            'name'               => $nationalityName,
            'order'              => $maxOrder + 1,
            'visible'            => 1, // should be visible in dropdown
            'editable'           => 1,
            'identity_type_id'   => $identityTypeId,
            'default'            => 0,
            'international_code' => null,
            'national_code'      => null,
            'external_validation'=> $externalValidationId,
            'is_refugee'         => 0,
            'modified_user_id'   => null,
            'modified'           => null,
            'created_user_id'    => 1,
            'created'            => date('Y-m-d H:i:s')
        ];

        $table = $this->table('nationalities');
        $table->insert($data)->save();

        // Store for reference if needed
//        $this->seychellesNationalityID = $newNationalityId;
    }


}
