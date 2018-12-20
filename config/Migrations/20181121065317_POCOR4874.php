<?php

use Phinx\Migration\AbstractMigration;

class POCOR4874 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4874_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_4874_import_mapping` SELECT * FROM `import_mapping`');
        $this->execute('CREATE TABLE `z_4874_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4874_security_functions` SELECT * FROM `security_functions`');

        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `id` = 1036');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= ' . $order);
        $data = [
            'id' => 1091,
            'name' => 'Import Student Guardians',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Students',
            'parent_id' => 1012,
            '_execute' => 'ImportStudentGuardians.add|ImportStudentGuardians.template|ImportStudentGuardians.results|ImportStudentGuardians.downloadFailed|ImportStudentGuardians.downloadPassed',
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
                'model' => 'Student.StudentGuardians',
                'column_name' => 'student_id',
                'description' => 'Student OpenEMIS ID',
                'order' => 1,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Security',
                'lookup_model' => 'Users',
                'lookup_column' => 'openemis_no'
            ],
            [
                'model' => 'Student.StudentGuardians',
                'column_name' => 'guardian_relation_id',
                'description' => 'Code',
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Student',
                'lookup_model' => 'GuardianRelations',
                'lookup_column' => 'id'
            ],
            [
                'model' => 'Student.StudentGuardians',
                'column_name' => 'guardian_id',
                'description' => 'Guardian OpenEMIS ID',
                'order' => 3,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Security',
                'lookup_model' => 'Users',
                'lookup_column' => 'openemis_no'
            ]
        ];
        $this->table('import_mapping')->insert($rows)->save();
    }

    public function down()
    {
        $this->execute('DROP TABLE import_mapping');
        $this->table('z_4874_import_mapping')->rename('import_mapping');
        $this->execute('DROP TABLE security_functions');
        $this->table('z_4874_security_functions')->rename('security_functions');
    }
}
