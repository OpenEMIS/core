<?php

use Phinx\Migration\AbstractMigration;

class POCOR4745 extends AbstractMigration
{
    public function up()
    {
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 268');
    
        $this->insert('security_functions', [
            'id' => 6014,
            'name' => 'Scholarships',
            'controller' => 'Reports',
            'module' => 'Reports',
            'category' => 'Reports',
            'parent_id' => -1,
            '_view' => 'Scholarships.index',
            '_add' => 'Scholarships.add',
            '_execute' => 'Scholarships.download',
            'order' => 269,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public function down()
    {
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 268');
        $this->execute('DELETE FROM security_functions WHERE id = 6014');
    }
}
