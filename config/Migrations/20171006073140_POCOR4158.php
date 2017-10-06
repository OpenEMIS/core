<?php

use Phinx\Migration\AbstractMigration;

class POCOR4158 extends AbstractMigration
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
        // security_functions
        $sql = "UPDATE `security_functions` 
                SET `_execute` = 'Positions.excel' 
                WHERE `name` = 'Positions'
                AND `controller` = 'Institutions'
                AND `module` = 'Institutions'
                AND `category` = 'Staff'";

        $this->execute($sql);
    }

    public function down()
    {
        // security_functions
        $sql = "UPDATE `security_functions` 
                SET `_execute` = NULL
                WHERE `name` = 'Positions'
                AND `controller` = 'Institutions'
                AND `module` = 'Institutions'
                AND `category` = 'Staff'";

        $this->execute($sql);
    }
}
