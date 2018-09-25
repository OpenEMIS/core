<?php

use Phinx\Migration\AbstractMigration;

class POCOR4689 extends AbstractMigration
{
    public function up()
    {
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 1036');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= ' . $order);
        $data = [
            'id' => 1085,
            'name' => 'Import Student Body Masses',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Students',
            'parent_id' => 1012,
            '_execute' => 'ImportStudentBodyMasses.add|ImportStudentBodyMasses.template|ImportStudentBodyMasses.results|ImportStudentBodyMasses.downloadFailed|ImportStudentBodyMasses.downloadPassed',
            'order' => $order,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $table = $this->table('security_functions');
        $table->insert($data);
        $table->saveData();

        $rows = [
            [
                'model' => 'User.UserBodyMasses',
                'column_name' => 'security_user_id',
                'description' => 'OpenEMIS ID' ,
                'order' => 1,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Security',
                'lookup_model' => 'Users',                
                'lookup_column' => 'openemis_no'
            ],
            [
                'model' => 'User.UserBodyMasses',
                'column_name' => 'academic_period_id',
                'description' => 'Code',
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'AcademicPeriod',
                'lookup_model' => 'AcademicPeriods',
                'lookup_column' => 'code'
            ],
            [
                'model' => 'User.UserBodyMasses',
                'column_name' => 'date',
                'description' => '( DD/MM/YYYY )' ,
                'order' => 3,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'User.UserBodyMasses',
                'column_name' => 'height',
                'description' => '(cm)' ,
                'order' => 4,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'User.UserBodyMasses',
                'column_name' => 'weight',
                'description' => '(kg)' ,
                'order' => 5,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'User.UserBodyMasses',
                'column_name' => 'comment',
                'description' => '(Optional)' ,
                'order' => 6,
                'is_optional' => 1,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],                        
        ];
        $this->table('import_mapping')->insert($rows)->save();                          
    }

    public function down()
    { 
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 1085');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` >= ' . $order);
        $this->execute('DELETE FROM security_functions WHERE id = 1085');     
        $this->execute("DELETE FROM `import_mapping` WHERE `model` ='User.UserBodyMasses'");
    }
}
