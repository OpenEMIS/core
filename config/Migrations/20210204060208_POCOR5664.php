<?php
use Migrations\AbstractMigration;

class POCOR5664 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Backup locale_contents table
        $this->execute('CREATE TABLE `zz_5664_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_5664_import_mapping` SELECT * FROM `import_mapping`');

        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 125');

        $data = [
            'name' => 'Import Student Assessment',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Students',
            'parent_id' => 8,
            '_view' => NULL,
            '_edit' => NULL,
            '_add' => NULL,
            '_delete' => NULL,
            '_execute' => 'ImportAssessmentItemResults.add|ImportAssessmentItemResults.template|ImportAssessmentItemResults.results|ImportAssessmentItemResults.downloadFailed|ImportAssessmentItemResults.downloadPassed',
            'order' => 126,
            'visible' => 1,
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];

        $table = $this->table('security_functions');
        $table->insert($data);
        $table->saveData();

        //import mapping
        $row = [
            [   
                'model' => 'Institution.AssessmentItemResults',  
                'column_name' => 'assessment_period_id',
                'description' => NULL,
                'order' => 1,
                'is_optional' => 0,
                'foreign_key' => 3,
                'lookup_plugin' => NULL,
                'lookup_model' => 'AssessmentPeriods',
                'lookup_column' => 'code'

            ],
            [
                'model' => 'Institution.AssessmentItemResults',  
                'column_name' => 'student_id',
                'description' => NULL,
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.AssessmentItemResults',  
                'column_name' => 'education_subject_id',
                'description' => NULL,
                'order' => 3,
                'is_optional' => 0,
                'foreign_key' => 3,
                'lookup_plugin' => NULL,
                'lookup_model' => 'EducationSubjects',
                'lookup_column' => 'code'
            ],
            [
                'model' => 'Institution.AssessmentItemResults',  
                'column_name' => 'marks',
                'description' => NULL,
                'order' => 4,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => NULL,
                'lookup_model' => NULL,
                'lookup_column' => NULL
            ],
            [
                'model' => 'Institution.AssessmentItemResults',  
                'column_name' => 'assessment_id',
                'description' => NULL,
                'order' => 5,
                'is_optional' => 0,
                'foreign_key' => 3,
                'lookup_plugin' => NULL,
                'lookup_model' => 'Assessments',
                'lookup_column' => 'code'
            ]
        ];
        
        $tableData = $this->table('import_mapping');
        $tableData->insert($row);
        $tableData->saveData();
    }

    // rollback
    public function down()
    {
       $this->execute('DROP TABLE IF EXISTS `import_mapping`');
       $this->execute('RENAME TABLE `zz_5664_import_mapping` TO `import_mapping`');
       $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 125');
    }
}
