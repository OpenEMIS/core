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
    }
}
