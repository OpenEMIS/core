<?php

use Phinx\Migration\AbstractMigration;

class POCOR2345 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('institution_classes');
        $table
            ->addColumn('secondary_staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'after' => 'staff_id'
            ])
            ->addIndex('secondary_staff_id')
            ->update();
    }

    public function down()
    {
        $table = $this->table('institution_classes');
        $table->removeColumn('secondary_staff_id')
              ->save();
    }
}
