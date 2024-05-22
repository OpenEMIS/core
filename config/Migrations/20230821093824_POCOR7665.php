<?php

use Phinx\Migration\AbstractMigration;

class POCOR7665 extends AbstractMigration
{

    public function up()
    {
        $this->updateCreateZTable();
        $this->updateAddMappingFields();

    }

    private function updateCreateZTable()
    {
        try {
            $this->execute('CREATE TABLE `z_7665_import_mapping` LIKE `import_mapping`');
        } catch (\Exception $e) {

        }
        try {
            $this->execute('INSERT IGNORE INTO `z_7665_import_mapping` SELECT * FROM `import_mapping`');
        } catch (\Exception $e) {

        }
    }

    private function updateAddMappingFields()
    {
        $model = 'Institution.InstitutionAssets';
        $data = [
            [
                'model' => $model,
                'column_name' => 'code',
                'description' => '',
                'order' => 1,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => $model,
                'column_name' => 'description',
                'description' => '',
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => $model,
                'column_name' => 'asset_type_id',
                'description' => '(Optional)',
                'order' => 3,
                'is_optional' => 1,
                'foreign_key' => 3,
                'lookup_plugin' => 'FieldOption',
                'lookup_model' => 'AssetTypes',
                'lookup_column' => 'id'
            ],
            [
                'model' => $model,
                'column_name' => 'asset_make_id',
                'description' => '(Optional)',
                'order' => 4,
                'is_optional' => 1,
                'foreign_key' => 3,
                'lookup_plugin' => 'FieldOption',
                'lookup_model' => 'AssetMakes',
                'lookup_column' => 'id'
            ],
            [
                'model' => $model,
                'column_name' => 'asset_model_id',
                'description' => '(Optional)',
                'order' => 5,
                'is_optional' => 1,
                'foreign_key' => 3,
                'lookup_plugin' => 'FieldOption',
                'lookup_model' => 'AssetModels',
                'lookup_column' => 'id'
            ],
            [
                'model' => $model,
                'column_name' => 'serial_number',
                'description' => '(Optional)',
                'order' => 6,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => $model,
                'column_name' => 'purchase_order',
                'description' => '(Optional)',
                'order' => 7,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => $model,
                'column_name' => 'cost',
                'description' => 'Decimal (50, 2)',
                'order' => 8,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => $model,
                'column_name' => 'stocktake_date',
                'description' => '( DD/MM/YYYY )',
                'order' => 9,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => $model,
                'column_name' => 'lifespan',
                'description' => 'Integer',
                'order' => 10,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => $model,
                'column_name' => 'institution_room_id',
                'description' => '(Optional)',
                'order' => 11,
                'is_optional' => 1,
                'foreign_key' => 3,
                'lookup_plugin' => 'Institution',
                'lookup_model' => 'InstitutionRooms',
                'lookup_column' => 'code'
            ],
            [
                'model' => $model,
                'column_name' => 'user_id',
                'description' => '(Optional)',
                'order' => 12,
                'is_optional' => 1,
                'foreign_key' => 2,
                'lookup_plugin' => 'User',
                'lookup_model' => 'Users',
                'lookup_column' => 'id'
            ],
            [
                'model' => $model,
                'column_name' => 'depreciation',
                'description' => 'Decimal (50, 2)',
                'order' => 13,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => $model,
                'column_name' => 'purchase_date',
                'description' => '( DD/MM/YYYY )',
                'order' => 14,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => null,
                'lookup_model' => null,
                'lookup_column' => null
            ],
            [
                'model' => $model,
                'column_name' => 'accessibility',
                'description' => '',
                'order' => 15,
                'is_optional' => 0,
                'foreign_key' => 3,
                'lookup_plugin' => null,
                'lookup_model' => 'Accessibility',
                'lookup_column' => 'id'
            ],
            [
                'model' => $model,
                'column_name' => 'purpose',
                'description' => '',
                'order' => 16,
                'is_optional' => 0,
                'foreign_key' => 3,
                'lookup_plugin' => null,
                'lookup_model' => 'Purpose',
                'lookup_column' => 'id'
            ],
            [
                'model' => $model,
                'column_name' => 'asset_status_id',
                'description' => '',
                'order' => 17,
                'is_optional' => 0,
                'foreign_key' => 3,
                'lookup_plugin' => 'Institution',
                'lookup_model' => 'AssetStatuses',
                'lookup_column' => 'id'
            ],
            [
                'model' => $model,
                'column_name' => 'asset_condition_id',
                'description' => '',
                'order' => 18,
                'is_optional' => 0,
                'foreign_key' => 3,
                'lookup_plugin' => 'FieldOption',
                'lookup_model' => 'AssetConditions',
                'lookup_column' => 'id'
            ],
        ];
        $this->insert('import_mapping', $data);
    }

    public function down()
    {
//        $this->execute("DELETE FROM `import_mapping` WHERE `model` = 'Institution.InstitutionAssets'");
        $this->rollbackCreateZTable();

    }

    private function _rollbackCreateZTable()
    {
        try {
            $this->dropTable('import_mapping');
            $this->table('z_7665_import_mapping')->rename('import_mapping');
        } catch (\Exception $e) {

        }

    }

}
