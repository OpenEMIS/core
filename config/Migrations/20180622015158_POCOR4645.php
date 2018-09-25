<?php

use Phinx\Migration\AbstractMigration;

class POCOR4645 extends AbstractMigration
{
    public function up()
    {
        $this->execute('UPDATE `import_mapping` SET `column_name` = "institution_class_id" WHERE `model` = "Institution.Students" AND `column_name` = "class"');

        $this->execute('UPDATE `import_mapping` SET `model` = "Institution.StudentAdmission" WHERE `model` = "Institution.Students"');

        $data = [
            [
                'model' => 'Institution.StudentAdmission',
                'column_name' => 'status_id',
                'description' => '',
                'order' => 6,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Workflow',
                'lookup_model' => 'WorkflowSteps',
                'lookup_column' => 'id'
            ]
        ];

        $this->insert('import_mapping', $data);

        $this->execute('
            UPDATE `security_functions`
            SET `name` = "Import Student Admission",
                `_execute` = "ImportStudentAdmission.add|ImportStudentAdmission.template|ImportStudentAdmission.results|ImportStudentAdmission.downloadFailed|ImportStudentAdmission.downloadPassed"
            WHERE `id` = 1035
        ');
    }

    public function down()
    {
        $this->execute("DELETE FROM `import_mapping` WHERE `model` = 'Institution.StudentAdmission' AND `column_name` = 'status_id'");
        
        $this->execute('UPDATE `import_mapping` SET `model` = "Institution.Students" WHERE `model` = "Institution.StudentAdmission"');
        $this->execute('UPDATE `import_mapping` SET `column_name` = "class" WHERE `model` = "Institution.Students" AND `column_name` = "institution_class_id"');

        $this->execute('
            UPDATE `security_functions`
            SET `name` = "Import Students",
                `_execute` = "ImportStudents.add|ImportStudents.template|ImportStudents.results|ImportStudents.downloadFailed|ImportStudents.downloadPassed"
            WHERE `id` = 1035
        ');
    }
}   
