<?php

use Phinx\Migration\AbstractMigration;

class POCOR4172 extends AbstractMigration
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
        $data = [
            'id' => 'f41e4262-92b1-11e7-83fc-525400b263eb',
            'module' => 'InstitutionClasses',
            'field' => 'secondary_staff_id',
            'module_name' => 'Institutions -> Classes',
            'field_name' => 'Secondary Teacher',
            'code' => NULL,
            'name' => NULL,
            'visible' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];

        $table = $this->table('labels');
        $table->insert($data);
        $table->saveData();
    }

    public function down()
    {
        $this->execute("DELETE FROM `labels` WHERE `id` = 'f41e4262-92b1-11e7-83fc-525400b263eb'");
    }
}
