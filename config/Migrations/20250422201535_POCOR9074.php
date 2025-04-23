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
        $this->execute("
            INSERT INTO field_options
                (id, name, category, table_name,
                 `order`, modified_by, modified,
                 created_by, created)
            VALUES
                (NULL, 'Infrastructure Land Types', 'Infrastructure', 'land_types',
                 93, null, null,
                 1, NOW());
        ");
    }
}
