<?php

use Phinx\Migration\AbstractMigration;

class POCOR4286 extends AbstractMigration
{
    public function up()
    {
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 287');

        $this->insert('security_functions', [
            'id' => 7058,
            'name' => 'Risks',
            'controller' => 'Directories',
            'module' => 'Directory',
            'category' => 'Students - Academic',
            'parent_id' => 7000,
            '_view' => 'StudentRisks.index|StudentRisks.view',
            '_edit' => null,
            '_add' => null,
            '_delete' => null,
            'order' => 288,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        $this->execute('UPDATE `security_functions` SET `_view` = "Nationalities.index|Nationalities.view" WHERE `id` = 5094');
    }


    public function down()
    { 
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 287');
        $this->execute('DELETE FROM security_functions WHERE id = 7058');

        $this->execute('UPDATE `security_functions` SET `_view` = "Nationalities.index|Identities.view" WHERE `id` = 5094');
    }
}
