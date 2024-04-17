<?php

use Phinx\Migration\AbstractMigration;

class POCOR8203 extends AbstractMigration
{
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_8203_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8203_security_functions` SELECT * FROM `security_functions`');

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 140');
        //insert data in security function
        $record = [
            [
                'name' => 'Curriculars Students', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Academic', 'parent_id' => 8,'_view' => 'InstitutionCurricularStudents.index|InstitutionCurricularStudents.view', '_edit' => 'InstitutionCurricularStudents.edit', '_add' => 'InstitutionCurricularStudents.add', '_delete' => 'InstitutionCurricularStudents.remove', '_execute' => NULL, 'order' => 141, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->insert('security_functions', $record);
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8203_security_functions` TO `security_functions`');
    }
}
