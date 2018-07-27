<?php

use Phinx\Migration\AbstractMigration;

class POCOR4642 extends AbstractMigration
{
    public function up()
    {
        $this->insert('api_securities', [
            'id' => 1002,
            'name' => 'Classes',
            'model' => 'Institution.InstitutionClasses',
            'index' => 1,
            'view' => 1,
            'add' => 0,
            'edit' => 0,
            'delete' => 0,
            'execute' => 0
        ]);    
    }

    public function down()
    {
        $this->execute('DELETE FROM api_securities WHERE id = 1002');
    }
}
