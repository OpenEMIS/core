<?php

use Phinx\Migration\AbstractMigration;

class POCOR4130 extends AbstractMigration
{
    // commit
    public function up()
    {
        // update the order
        $updateOrder = 'UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= 158';
        $this->execute($updateOrder);

        // insert permission for label
        $table = $this->table('security_functions');

        $data = [
            'id' => 5078,
            'name' => 'Labels',
            'controller' => 'Labels',
            'module' => 'Administration',
            'category' => 'Labels',
            'parent_id' => 5000,
            '_view' => 'index|view',
            '_edit' => 'edit',
            '_add' => NULL,
            '_delete' => NULL,
            'order' => 158,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ];

        $table->insert($data);
        $table->saveData();
    }

    // rollback
    public function down()
    {
        // remove permission for label
        $this->execute('DELETE FROM security_functions WHERE id = 5078');

        // update the order back
        $updateOrder = 'UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` >= 159';
        $this->execute($updateOrder);
    }
}
