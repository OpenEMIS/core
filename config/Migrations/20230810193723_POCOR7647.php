<?php

use Phinx\Migration\AbstractMigration;

class POCOR7647 extends AbstractMigration
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
        $this->updateCreateBackUpTable();
        $this->execute("UPDATE `field_options` SET `category` = 'Textbook' WHERE `name` like 'Textbook%'");
    }

    public function down()
    {
        $this->rollbackCreateBackUpTable();
    }

    private function updateCreateBackUpTable()
    {
        try {
            $this->execute('CREATE TABLE `z_7647_field_options` LIKE `field_options`');
        } catch (\Exception $e) {

        }
        try {
            $this->execute('INSERT IGNORE INTO `z_7647_field_options` SELECT * FROM `field_options`');
        } catch (\Exception $e) {

        }
    }

    private function rollbackCreateBackUpTable()
    {
        try {
            $this->execute('DROP TABLE IF EXISTS `field_options`');
        } catch (\Exception $e) {

        }
        try {
            $this->execute('RENAME TABLE `z_7647_field_options` TO `field_options`');
        } catch (\Exception $e) {

        }
    }
}
