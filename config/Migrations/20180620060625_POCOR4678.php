<?php

use Phinx\Migration\AbstractMigration;

class POCOR4678 extends AbstractMigration
{
    public function up()
    {
        // asset_purposes
        $this->table('asset_purposes')->rename('z_4678_asset_purposes');

        // institution_assets
        $table = $this->table('institution_assets');
        $table
            ->renameColumn('asset_purpose_id', 'purpose')
            ->changeColumn('purpose', 'integer', [
                'null' => false,
                'limit' => 1,
                'comment' => '0 -> Non-Teaching, 1 -> Teaching',
                'after' => 'accessibility'
            ])
            ->removeIndexByName('asset_purpose_id')
            ->save();
    }

    public function down()
    {
        // asset_purposes
        $this->table('z_4678_asset_purposes')->rename('asset_purposes');

        // institution_assets
        $table = $this->table('institution_assets');
        $table
            ->renameColumn('purpose', 'asset_purpose_id')
            ->changeColumn('asset_purpose_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to asset_purposes.id',
                'after' => 'asset_type_id'
            ])
            ->addIndex('asset_purpose_id')
            ->save();
    }
}
