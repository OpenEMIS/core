<?php

use Phinx\Migration\AbstractMigration;

class POCOR4045 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4045_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4045_security_functions` SELECT * FROM `security_functions`');

        $this->execute('UPDATE security_functions SET `order` = `order` + 2 WHERE `order` > 309');

       $data = [
            [
                'id' => 7069,
                'name' => 'Student Relation',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Guardians - Students',
                'parent_id' => 7000,
                '_view' => 'GuardianStudents.index|GuardianStudents.view',
                '_edit' => 'GuardianStudents.edit',
                '_add' => 'GuardianStudents.add',
                '_delete' => 'GuardianStudents.remove',
                'order' => 311,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 7070,
                'name' => 'Student Profile',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Guardians - Students',
                'parent_id' => 7000,
                '_view' => 'GuardianStudentUser.index|GuardianStudentUser.view',
                '_edit' => 'GuardianStudentUser.edit',
                '_add' => 'GuardianStudentUser.add',
                '_delete' => null,
                'order' => 310,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
        ];

        $this->insert('security_functions', $data);
    }

    public function down()
    {
        $this->dropTable('security_functions');
        $this->table('z_4045_security_functions')->rename('security_functions');
    }   
}
