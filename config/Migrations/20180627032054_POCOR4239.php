<?php

use Phinx\Migration\AbstractMigration;

class POCOR4239 extends AbstractMigration
{
    public function up()
    {
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 145');
        $this->insert('security_functions', [
            'id' => 3049,
            'name' => 'Transport',
            'controller' => 'Students',
            'module' => 'Institutions',
            'category' => 'Students - General',
            'parent_id' => 2000,
            '_view' => 'StudentTransport.index|StudentTransport.view',
            '_edit' => null,
            '_add' => null,
            '_delete' => null,
            'order' => 146,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public function down()
    { 
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 145');
        $this->execute('DELETE FROM security_functions WHERE id = 3049');
    }
}

