<?php

use Phinx\Migration\AbstractMigration;

class POCOR5186 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('student_behaviours');
        $table
            ->addColumn('assignee_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'after' => 'student_behaviour_category_id'
            ])
            ->addIndex('assignee_id')
            ->update();
    }

    public function down()
    {
        $table = $this->table('student_behaviours');
        $table->removeColumn('assignee_id')
              ->save();
    }
}
