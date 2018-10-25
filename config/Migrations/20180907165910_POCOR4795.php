<?php

use Phinx\Migration\AbstractMigration;

class POCOR4795 extends AbstractMigration
{
    // commit
    public function up()
    {
        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 266');
        $this->insert('security_functions', [
            'id' => 6015,
            'name' => 'Directory',
            'controller' => 'Reports',
            'module' => 'Reports',
            'category' => 'Reports',
            'parent_id' => -1,
            '_view' => 'Directory.index',
            '_add' => 'Directory.add',
            '_execute' => 'Directory.download',
            'order' => 267,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    // rollback
    public function down()
    {
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 266');
        $this->execute('DELETE FROM security_functions WHERE `id` = 6015');
    }
}
