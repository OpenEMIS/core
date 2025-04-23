<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Utility\Text;

class POCOR9074 extends AbstractMigration
{
    public function up()
    {
        $this->backupTables();
        $this->insertFieldOptionLandType();
    }

    public function down()
    {
        $this->restoreTable();
    }

    /**
     * @return void
     */
    public function backupTables()
    {
        if (!$this->hasTable('z_9074_field_options')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('CREATE TABLE `z_9074_field_options` LIKE `field_options`');
            $this->execute('INSERT INTO `z_9074_field_options` SELECT * FROM `field_options`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * @return void
     */
    public function restoreTable()
    {
        if ($this->hasTable('z_9074_field_options')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `field_options`');
            $this->execute('RENAME TABLE `z_9074_field_options` TO `field_options`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * @return void
     */
    public function insertFieldOptionLandType()
    {
        //
        $order = $this->fetchRow("SELECT `order` FROM `field_options` ORDER BY `id` DESC LIMIT 1");
        $this->execute("
            INSERT INTO field_options
                (id, name, category, table_name,
                 `order`, modified_by, modified,
                 created_by, created)
            VALUES
                (NULL, 'Infrastructure Land Types', 'Infrastructure', 'land_types',
                 $order[0] + 1, null, null,
                 1, NOW());
        ");
        $this->execute("
            INSERT INTO field_options
                (id, name, category, table_name,
                 `order`, modified_by, modified,
                 created_by, created)
            VALUES
                (NULL, 'Infrastructure Building Types', 'Infrastructure', 'building_types',
                 $order[0] + 2, null, null,
                 1, NOW());
        ");
        $this->execute("
            INSERT INTO field_options
                (id, name, category, table_name,
                 `order`, modified_by, modified,
                 created_by, created)
            VALUES
                (NULL, 'Infrastructure Floor Types', 'Infrastructure', 'floor_types',
                 $order[0] + 3, null, null,
                 1, NOW());
        ");
        $this->execute("
            INSERT INTO field_options
                (id, name, category, table_name,
                 `order`, modified_by, modified,
                 created_by, created)
            VALUES
                (NULL, 'Infrastructure Room Types', 'Infrastructure', 'room_types',
                 $order[0] + 4, null, null,
                 1, NOW());
        ");
    }
}
