<?php

use Phinx\Migration\AbstractMigration;

class POCOR3890 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {
        //area_administratives
        //backup the table
        $this->execute('CREATE TABLE IF NOT EXISTS `z_3890_area_administratives` LIKE `area_administratives`');
        $this->execute('INSERT INTO `z_3890_area_administratives` 
                        SELECT * FROM `area_administratives`');

        //ensure only one main country
        $this->execute('UPDATE `area_administratives` SET `is_main_country` = 0');
        $this->execute('UPDATE `area_administratives` 
                        SET `is_main_country` = 1
                        WHERE `parent_id` IN (
                            SELECT `id` AS `WorldId` FROM `z_3890_area_administratives` WHERE `parent_id` IS NULL
                        )
                        ORDER BY `order`
                        LIMIT 1');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `area_administratives`');
        $this->execute('RENAME TABLE `z_3890_area_administratives` TO `area_administratives`');
    }
}
