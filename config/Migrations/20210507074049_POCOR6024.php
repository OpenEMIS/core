<?php
use Migrations\AbstractMigration;

class POCOR6024 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_6024_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_6024_import_mapping` SELECT * FROM `import_mapping`');

        //deleting previous records
        $this->execute("DELETE FROM `import_mapping` WHERE model = 'Institution.AssessmentItemResults'");

        //inserting records
        $data = [
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
                'description' => 'Student (OpenEmis No)',
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 3,
                'lookup_plugin' => NULL,
                'lookup_model' => 'Users',
                'lookup_column' => 'openemis_no'
                  
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
                'column_name' => 'class_id',
                'description' => NULL,
                'order' => 5,
                'is_optional' => 0,
                'foreign_key' => 3,
                'lookup_plugin' => NULL,
                'lookup_model' => 'InstitutionClasses',
                'lookup_column' => 'id'
                  
            ]
        ];
        
        $this->insert('import_mapping', $data);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `zz_6024_import_mapping` TO `import_mapping`');
    }
}
