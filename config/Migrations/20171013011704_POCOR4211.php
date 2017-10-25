<?php

use Phinx\Migration\AbstractMigration;

class POCOR4211 extends AbstractMigration
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
        $sql = "ALTER TABLE `training_sessions` ADD `area_id` INT NOT NULL COMMENT 'links to areas.id' AFTER `status_id`;";
        $this->execute($sql);

        //update all training session area to the highest as default
        $sql = "UPDATE `training_sessions`
                SET `area_id` = (
                    SELECT `id` FROM `areas`
                    WHERE `parent_id` IS NULL
                    LIMIT 1
                )";
        $this->execute($sql);
    }

    public function down()
    {
        $sql = "ALTER TABLE `training_sessions` DROP `area_id`";
        $this->execute($sql);
    }
}
