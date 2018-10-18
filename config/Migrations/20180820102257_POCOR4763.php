<?php

use Phinx\Migration\AbstractMigration;

class POCOR4763 extends AbstractMigration
{
    public function up()
    {
        $this->table('scholarships')->rename('z_4763_scholarships');
        $this->execute('CREATE TABLE `scholarships` LIKE `z_4763_scholarships`');
        $this->execute('INSERT INTO `scholarships` SELECT * FROM `z_4763_scholarships`');
        $users = $this->table('scholarships');
        $users->addColumn('duration', 'integer', ['limit' => 2, 'null' => false, 'default' => null, 'after' => 'total_amount'])
              ->save();
    }

    public function down()
    {
        $this->dropTable('scholarships');
        $this->table('z_4763_scholarships')->rename('scholarships');    
    }    
}
