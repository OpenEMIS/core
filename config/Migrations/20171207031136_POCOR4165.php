<?php
use Migrations\AbstractMigration;

class POCOR4165 extends AbstractMigration
{
     // commit
    public function up()
    {
        // security_functions
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= 153');

        // insert permission for label
        $table = $this->table('security_functions');
        $data = [
            'id' => 3041,
            'name' => 'Staff Body Mass',
            'controller' => 'StaffBodyMasses',
            'module' => 'Institutions',
            'category' => 'Staff - Health',
            'parent_id' => 3000,
            '_view' => 'index|view',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => 153,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table->insert($data);

        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= 293');
        $data = [
            'id' => 7055,
            'name' => 'Body Mass',
            'controller' => 'DirectoryBodyMasses',
            'module' => 'Directory',
            'category' => 'Health',
            'parent_id' => 7000,
            '_view' => 'index|view',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            'order' => 293,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table->insert($data);
        $table->saveData();
        // end security_functions
    }

    // rollback
    public function down()
    {
        $this->execute('DELETE FROM security_functions WHERE id = 3041');
        $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` >= 154');

        $this->execute('DELETE FROM security_functions WHERE id = 7055');
        $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` >= 294');
    }
}
