<?php

use Phinx\Migration\AbstractMigration;

class POCOR4763 extends AbstractMigration
{
    public function up()
    {
        $users = $this->table('scholarships');
        $users->addColumn('duration', 'integer', ['limit' => 2, 'null' => true, 'default' => null, 'after' => 'total_amount'])
              ->save();
    }

    public function down()
    {
        $table = $this->table('scholarships');
        $table->removeColumn('duration')
              ->save();      
    }    
}
