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

        // security_functions - order currently 105
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 104');
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 51');

        $this->execute("UPDATE security_functions SET `category` = 'Details', `parent_id` = 8, `order` = 52 WHERE `id` = 3044");
    }

    public function down()
    {
        // asset_purposes
        $this->table('z_4678_asset_purposes')->rename('asset_purposes');

        // institution_assets
        $this->dropTable('institution_assets');
        $this->table('z_4678_institution_assets')->rename('institution_assets');

        // security_functions - set order back to 105
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 51');
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 104');

        $this->execute("UPDATE security_functions SET `category` = 'Assets', `parent_id` = 1000, `order` = 105 WHERE `id` = 3044");
    }
}
