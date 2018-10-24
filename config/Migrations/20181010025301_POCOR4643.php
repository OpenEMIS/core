<?php
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Text;

class POCOR4643 extends AbstractMigration
{
    public function up()
    {
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 73');

        // Bulk Student Admission - Setup
        $this->insert('security_functions', [
            'id' => 1088,
            'name' => 'Bulk Student Admission',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Students',
            'parent_id' => 1000,
            '_view' => null,
            '_edit' => null,
            '_add' => null,
            '_delete' => null,
            '_execute' => 'BulkStudentAdmission.edit|BulkStudentAdmission.reconfirm',
            'order' => 74,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public function down()
    {
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 73');
        $this->execute('DELETE FROM security_functions WHERE id = 1088');
    }
}
