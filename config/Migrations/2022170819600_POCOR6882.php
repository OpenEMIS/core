<?php

use Phinx\Migration\AbstractMigration;

class POCOR6882 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('meal_programmes');
        $table
            ->addColumn('area_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'after' => 'implementer'
            ])
            ->addIndex('area_id')
            ->update();
    }

    public function down()
    {
        $table = $this->table('meal_programmes');
        $table->removeColumn('area_id')
              ->save();
    }
}
