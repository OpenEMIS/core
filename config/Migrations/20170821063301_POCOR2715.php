<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class POCOR2715 extends AbstractMigration
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
    public function change()
    {
        $table = $this->table('institutions');
        $table->addColumn('logo_name', 'string', [
                    'after' => 'latitude',
                    'limit' => 250,
                    'default' => NULL, 
                    'null' => true
                ])
            ->addColumn('logo_content', 'blob', [
                'after' => 'logo_name',
                'limit' => MysqlAdapter::BLOB_REGULAR,
                'default' => NULL, 
                'null' => true

            ])
            ->update();
    }
}
