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
    public function up()
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
                'limit' => '4294967295',
                'default' => NULL, 
                'null' => true
            ])
            ->save();

        $this->insert('labels', [
            'id' => '898ba5cf-87b3-11e7-ae22-525400b263eb',
            'module' => 'Institutions',
            'field' => 'logo_content',
            'module_name' => 'Institutions',
            'field_name' => 'Logo',
            'code' => NULL,
            'name' => NULL,
            'visible' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2017-08-23 00:00:00'
        ]);
    }

    // rollback
    public function down()
    {
        $table = $this->table('institutions');
        $table->removeColumn('logo_name')
            ->removeColumn('logo_content')
            ->save();

        $this->execute("DELETE FROM `labels` WHERE id = '898ba5cf-87b3-11e7-ae22-525400b263eb'");
    }
}
