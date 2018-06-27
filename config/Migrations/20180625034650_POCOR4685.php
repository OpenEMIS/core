<?php

use Phinx\Migration\AbstractMigration;

class POCOR4685 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('institution_class_students');
        $table
            ->addColumn('next_institution_class_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'after' => 'academic_period_id'
            ])
            ->addIndex('next_institution_class_id')
            ->update();
    }

    public function down()
    {
        $table = $this->table('institution_class_students');
        $table->removeColumn('next_institution_class_id')
              ->save();
    }
}
