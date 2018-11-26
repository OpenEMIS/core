<?php

use Phinx\Migration\AbstractMigration;

class POCOR4874 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4874_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `z_4874_import_mapping` SELECT * FROM `import_mapping`');

        $rows = [
            [
                'model' => 'Student.StudentGuardians',
                'column_name' => 'student_id',
                'description' => 'OpenEMIS ID (Student Id)' ,
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
                'description' => 'Relation',
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
                'description' => 'OpenEMIS ID (Guardian Id)' ,
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
    }
}
