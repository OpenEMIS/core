<?php

use Phinx\Migration\AbstractMigration;

class POCOR4252 extends AbstractMigration
{
    // commit
    public function up()
    {
        // institution_staff_transfers
        $staffTransfers = $this->table('institution_staff_transfers');
        $staffTransfers
            ->changeColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->save();

        // outcome_templates
        $outcomeTemplates = $this->table('outcome_templates', [
            'id' => false,
            'primary_key' => ['id', 'academic_period_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the outcome template for a specific grade'
        ]);
        $outcomeTemplates
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'identity' => true
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('education_grade_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_grades.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('id')
            ->addIndex('academic_period_id')
            ->addIndex('education_grade_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // outcome_periods
        $outcomePeriods = $this->table('outcome_periods', [
            'id' => false,
            'primary_key' => ['id', 'academic_period_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of periods for a specific outcome'
        ]);
        $outcomePeriods
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'identity' => true
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('date_enabled', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('date_disabled', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('outcome_template_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to outcome_templates.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('id')
            ->addIndex('academic_period_id')
            ->addIndex('outcome_template_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // outcome_grading_types
        $outcomeGradingTypes = $this->table('outcome_grading_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of grading types that can be used for an assessable outcome'
        ]);
        $outcomeGradingTypes
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // outcome_grading_options
        $outcomeGradingOptions = $this->table('outcome_grading_options', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all options linked to a specific grading type for outcome'
        ]);
        $outcomeGradingOptions
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('outcome_grading_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to outcome_grading_types.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('outcome_grading_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // outcome_criterias
        $outcomeCriterias = $this->table('outcome_criterias', [
            'id' => false,
            'primary_key' => [
                'id',
                'academic_period_id',
                'outcome_template_id',
                'education_grade_id',
                'education_subject_id'
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of outcome criterias for a given subject'
        ]);
        $outcomeCriterias
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'identity' => true
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 500,
                'null' => false
            ])
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('outcome_template_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to outcome_templates.id'
            ])
            ->addColumn('education_grade_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_grades.id'
            ])
            ->addColumn('education_subject_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_subjects.id'
            ])
            ->addColumn('outcome_grading_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to outcome_grading_types.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('id')
            ->addIndex('academic_period_id')
            ->addIndex('outcome_template_id')
            ->addIndex('education_grade_id')
            ->addIndex('education_subject_id')
            ->addIndex('outcome_grading_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // institution_outcome_results
        $institutionOutcomeResults = $this->table('institution_outcome_results', [
            'id' => false,
            'primary_key' => [
                'student_id',
                'outcome_template_id',
                'outcome_period_id',
                'education_grade_id',
                'education_subject_id',
                'outcome_criteria_id',
                'institution_id',
                'academic_period_id'
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the outcome results for an individual student in an institution'
        ]);
        $institutionOutcomeResults
            ->addColumn('id', 'char', [
                'default' => null,
                'limit' => 64,
                'null' => false
            ])
            ->addColumn('outcome_grading_option_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to outcome_grading_options.id'
            ])
            ->addColumn('student_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('outcome_template_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to outcome_templates.id'
            ])
            ->addColumn('outcome_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to outcome_periods.id'
            ])
            ->addColumn('education_grade_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_grades.id'
            ])
            ->addColumn('education_subject_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_subjects.id'
            ])
            ->addColumn('outcome_criteria_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to outcome_criterias.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('id')
            ->addIndex('outcome_grading_option_id')
            ->addIndex('student_id')
            ->addIndex('outcome_template_id')
            ->addIndex('outcome_period_id')
            ->addIndex('education_grade_id')
            ->addIndex('education_subject_id')
            ->addIndex('outcome_criteria_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // institution_outcome_subject_comments
        $institutionOutcomeSubjectComments = $this->table('institution_outcome_subject_comments', [
            'id' => false,
            'primary_key' => [
                'student_id',
                'outcome_template_id',
                'outcome_period_id',
                'education_grade_id',
                'education_subject_id',
                'institution_id',
                'academic_period_id'
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the comments for an outcome subject for an individual student in an institution'
        ]);
        $institutionOutcomeSubjectComments
            ->addColumn('id', 'char', [
                'default' => null,
                'limit' => 64,
                'null' => false
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('student_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('outcome_template_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to outcome_templates.id'
            ])
            ->addColumn('outcome_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to outcome_periods.id'
            ])
            ->addColumn('education_grade_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_grades.id'
            ])
            ->addColumn('education_subject_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_subjects.id'
            ])
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('id')
            ->addIndex('student_id')
            ->addIndex('outcome_template_id')
            ->addIndex('outcome_period_id')
            ->addIndex('education_grade_id')
            ->addIndex('education_subject_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // import_mapping
        $importMappingData = [
            [
                'model' => 'Institution.InstitutionOutcomeResults',
                'column_name' => 'outcome_criteria_id',
                'description' => 'Id',
                'order' => 1,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Outcome',
                'lookup_model' => 'OutcomeCriterias',
                'lookup_column' => 'id'
            ],
            [
                'model' => 'Institution.InstitutionOutcomeResults',
                'column_name' => 'student_id',
                'description' => 'OpenEMIS ID',
                'order' => 2,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Security',
                'lookup_model' => 'Users',
                'lookup_column' => 'openemis_no'
            ],
            [
                'model' => 'Institution.InstitutionOutcomeResults',
                'column_name' => 'outcome_grading_option_id',
                'description' => 'Id',
                'order' => 3,
                'is_optional' => 0,
                'foreign_key' => 2,
                'lookup_plugin' => 'Outcome',
                'lookup_model' => 'OutcomeGradingOptions',
                'lookup_column' => 'id'
            ]
        ];
        $this->insert('import_mapping', $importMappingData);

        // security_functions
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 2 WHERE `order` >= 74');
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= 94');
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= 262');

        $securityFunctionsData = [
            [
                'id' => '1081',
                'name' => 'Outcome Results',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Students',
                'parent_id' => 8,
                '_view' => 'StudentOutcomes.index|StudentOutcomes.view',
                '_edit' => 'StudentOutcomes.edit',
                'order' => '74',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '1082',
                'name' => 'Import Outcome Results',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Students',
                'parent_id' => 8,
                '_execute' => 'ImportOutcomeResults.add|ImportOutcomeResults.template|ImportOutcomeResults.results|ImportOutcomeResults.downloadFailed|ImportOutcomeResults.downloadPassed',
                'order' => '75',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '2037',
                'name' => 'Outcomes',
                'controller' => 'Students',
                'module' => 'Institutions',
                'category' => 'Students - Academic',
                'parent_id' => 2000,
                '_view' => 'Outcomes.index',
                'order' => '94',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '7056',
                'name' => 'Outcomes',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Students - Academic',
                'parent_id' => 7000,
                '_view' => 'StudentOutcomes.index',
                'order' => '262',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '5082',
                'name' => 'Outcome Setup',
                'controller' => 'Outcomes',
                'module' => 'Administration',
                'category' => 'Learning Outcomes',
                'parent_id' => 5000,
                '_view' => 'Templates.index|Templates.view|Criterias.index|Criterias.view',
                '_edit' => 'Templates.edit|Criterias.edit',
                '_add' => 'Templates.add|Criterias.add',
                '_delete' => 'Templates.remove|Criterias.remove',
                'order' => '306',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '5083',
                'name' => 'Periods',
                'controller' => 'Outcomes',
                'module' => 'Administration',
                'category' => 'Learning Outcomes',
                'parent_id' => 5000,
                '_view' => 'Periods.index|Periods.view',
                '_edit' => 'Periods.edit',
                '_add' => 'Periods.add',
                '_delete' => 'Periods.remove',
                'order' => '307',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => '5084',
                'name' => 'Grading Types',
                'controller' => 'Outcomes',
                'module' => 'Administration',
                'category' => 'Learning Outcomes',
                'parent_id' => 5000,
                '_view' => 'GradingTypes.index|GradingTypes.view',
                '_edit' => 'GradingTypes.edit',
                '_add' => 'GradingTypes.add',
                '_delete' => 'GradingTypes.remove',
                'order' => '308',
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('security_functions', $securityFunctionsData);
    }

    // rollback
    public function down()
    {
        // institution_staff_transfers
        $staffTransfers = $this->table('institution_staff_transfers');
        $staffTransfers
            ->changeColumn('modified', 'date', [
                'default' => null,
                'null' => true
            ])
            ->save();

        $this->dropTable('outcome_templates');
        $this->dropTable('outcome_periods');
        $this->dropTable('outcome_grading_types');
        $this->dropTable('outcome_grading_options');
        $this->dropTable('outcome_criterias');
        $this->dropTable('institution_outcome_results');
        $this->dropTable('institution_outcome_subject_comments');

        // import_mapping
        $this->execute("DELETE FROM `import_mapping` WHERE `model` = 'Institution.InstitutionOutcomeResults'");

        // security_functions
        $this->execute("DELETE FROM `security_functions` WHERE `id` IN (1081,1082,2037,7056,5082,5083,5084)");
        $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` >= 263');
        $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` >= 95');
        $this->execute('UPDATE `security_functions` SET `order` = `order` - 2 WHERE `order` >= 76');
    }
}
