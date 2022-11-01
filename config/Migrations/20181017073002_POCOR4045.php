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
                '_add' => null,
                '_delete' => null,
                'order' => 310,
                'visible' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
        ];

        $this->insert('security_functions', $data);

        $this->execute("UPDATE `security_functions` SET `order` = 155 WHERE `id` = 2029");
        $this->execute("UPDATE `security_functions` SET `order` = 119 WHERE `id` = 2010");
        $this->execute("UPDATE `security_functions` SET `order` = 156 WHERE `id` = 2049");
        $this->execute("UPDATE `security_functions` SET `order` = 157 WHERE `id` = 2056");
        $this->execute("UPDATE `security_functions` SET `order` = 158 WHERE `id` = 2050");
        $this->execute("UPDATE `security_functions` SET `order` = 159 WHERE `id` = 2051");
        $this->execute("UPDATE `security_functions` SET `order` = 160 WHERE `id` = 2052");
        $this->execute("UPDATE `security_functions` SET `order` = 161 WHERE `id` = 2053");
        $this->execute("UPDATE `security_functions` SET `order` = 162 WHERE `id` = 2055");
        $this->execute("UPDATE `security_functions` SET `order` = 163 WHERE `id` = 2054");
        $this->execute("UPDATE `security_functions` SET `_add` = null WHERE `id` = 7047");      

    }

    public function down()
    {
        $this->dropTable('security_functions');
        $this->table('z_4045_security_functions')->rename('security_functions');
    }   
}
