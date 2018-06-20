<?php

use Phinx\Migration\AbstractMigration;

class POCOR4678 extends AbstractMigration
{
    public function up()
    {
        // asset_purposes
        $this->table('asset_purposes')->rename('z_4678_asset_purposes');

        // institution_assets
        $this->execute('CREATE TABLE `z_4678_institution_assets` LIKE `institution_assets`');
        $this->execute('INSERT INTO `z_4678_institution_assets` SELECT * FROM `institution_assets`');

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

        $this->execute('UPDATE `institution_assets` SET `purpose` = 1');
    }

    public function down()
    {
        // asset_purposes
        $this->table('z_4678_asset_purposes')->rename('asset_purposes');

        // institution_assets
        $this->dropTable('institution_assets');
        $this->table('z_4678_institution_assets')->rename('institution_assets');
    }
}
