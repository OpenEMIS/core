<?php

use Phinx\Migration\AbstractMigration;

class POCOR4004 extends AbstractMigration
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
        $this->execute("UPDATE security_functions 
                        SET `_execute` = 'StudentAdmission.edit|StudentAdmission.view|StudentAdmission.execute' 
                        WHERE `id` = 1028");
    }

    public function down()
    {
        // security_functions
        $this->execute("UPDATE security_functions 
                        SET `_execute` = 'StudentAdmission.edit|StudentAdmission.view' 
                        WHERE `id` = 1028");
    }
}
