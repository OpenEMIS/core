<?php

use Phinx\Migration\AbstractMigration;

class POCOR4047 extends AbstractMigration
{
    // commit
    public function up()
    {
        $data = [
            'id' => '4',
            'code' => 'CHANGE_OF_START_DATE',
            'name' => 'Change of Start Date'
        ];

        $table = $this->table('staff_change_types');
        $table->insert($data);
        $table->saveData();
    }

    // rollback
    public function down()
    {
        $this->execute('DELETE FROM staff_change_types WHERE `id` = 4');
    }
}
