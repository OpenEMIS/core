<?php
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;

class POCOR2813 extends AbstractMigration
{
    private $workflowModelId = 20;

    public function up()
    {
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');
        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');

        $workflowModelData = [
            [
                'id' => $this->workflowModelId,
                'name' => 'Administration > Scholarships > Applications',
                'model' => 'Scholarship.ScholarshipApplications',
                'filter' => NULL,
                'is_school_based' => '0',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_models', $workflowModelData);

        // workflows
        $workflowData = [
            [
                'code' => 'Scholarships-1001',
                'name' => 'Scholarship Applications',
                'workflow_model_id' => $this->workflowModelId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflows', $workflowData);

        // get the workflowId for the created workflow
        $workflowId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $this->workflowModelId])
            ->extract('id')
            ->first();

         // workflow_steps
        $workflowStepData = [
            [
                'name' => 'Open',
                'category' => '1',
                'is_editable' => '1',
                'is_removable' => '1',
                'is_system_defined' => '1',
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Application Review',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Application Approved',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Rejected',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_steps', $workflowStepData);

        // Get the workflowSteps for the created workflowsteps
        $openStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 1
            ])
            ->extract('id')
            ->first();
        $applicationReviewStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 2
            ])
            ->extract('id')
            ->first();
        $applicationApprovedStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Application Approved'
            ])
            ->extract('id')
            ->first();
        $rejectedStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Rejected'
            ])
            ->extract('id')
            ->first();

        //  workflow_actions
        $workflowActionData = [
            [
                'name' => 'Submit For Review',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '1',
                'event_key' => NULL,
                'workflow_step_id' => $openStatusId,
                'next_workflow_step_id' => $applicationReviewStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Approve',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => 'Workflow.onApprove',
                'workflow_step_id' => $applicationReviewStatusId,
                'next_workflow_step_id' => $applicationApprovedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'description' => NULL,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '1',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $applicationReviewStatusId,
                'next_workflow_step_id' => $rejectedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_actions', $workflowActionData);

        // scholarships
        $table = $this->table('scholarships', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of scholarships'
        ]);

        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 50
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('date_application_open', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('date_application_close', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('maximum_award_amount', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('total_amount', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('bond', 'integer', [  
                'limit' => 2,
                'null' => true
            ])
             ->addColumn('requirements', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('instructions', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('financial_assistance_type_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to financial_assistance_types.id'
            ])
            ->addColumn('scholarship_funding_source_id', 'integer', [ 
                'null' => true,
                'limit' => 11,
                'comment' => 'links to scholarship_funding_sources.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'null' => false,
                'limit' => 11,
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
            ->addIndex('financial_assistance_type_id')
            ->addIndex('scholarship_funding_source_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // financial_assistance_types
        $table = $this->table('financial_assistance_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the fixed list of financial assistance types used in scholarships'
        ]);

        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 100
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->save();

        $table
            ->insert([
                [
                    'code' => 'SCHOLARSHIP',
                    'name' => 'Scholarship'
                ],
                [
                    'code' => 'LOAN',
                    'name' => 'Loan'
                ]
            ])
            ->save();

        // scholarship_funding_sources
        $table = $this->table('scholarship_funding_sources', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This field options table contains the list of funding sources used in scholarships'
        ]);

        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('visible', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('default', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
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

        // scholarships_field_of_studies
        $table = $this->table('scholarships_field_of_studies', [
            'id' => false,
            'primary_key' => [
                'scholarship_id',
                'education_field_of_study_id',
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of field of studies linked to specific scholarship'
        ]);

        $table
            ->addColumn('scholarship_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to scholarships.id'
            ])
            ->addColumn('education_field_of_study_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_field_of_studies.id'
            ])
            ->addIndex('scholarship_id')
            ->addIndex('education_field_of_study_id')
            ->save();

        // scholarship_payment_frequencies
        $table = $this->table('scholarship_payment_frequencies', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains the list of payment frequencies used in scholarships'
            ]);

        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('visible', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('default', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
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

       // scholarship_loans
        $table = $this->table('scholarship_loans', [
            'id' => false,
            'primary_key' => ['scholarship_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the loan details linked to specific scholarship'
        ]);
        
        $table
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
            ])
            ->addColumn('interest_rate', 'decimal', [
                'null' => true,
                'precision' => 5,
                'scale' => 2
            ])
            ->addColumn('interest_rate_type', 'integer', [
                'limit' => 1,
                'null' => true,
                'comment' => '0 -> Fixed, 1 -> Variable'
            ])
            ->addColumn('loan_term', 'integer', [
                'limit' => 3,
                'null' => true
            ])
            ->addColumn('scholarship_payment_frequency_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarship_payment_frequencies.id'
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
            ->addIndex('scholarship_id')
            ->addIndex('scholarship_payment_frequency_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // scholarship_attachment_types
        $table = $this->table('scholarship_attachment_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of attachment types linked to specific scholarship'
        ]);

        $table
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 50
            ])
            ->addColumn('is_mandatory', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('scholarship_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to scholarships.id'
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
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // Start of applicant =====================================================
        // scholarship_applications
        $table = $this->table('scholarship_applications', [
            'id' => false,
            'primary_key' => ['applicant_id', 'scholarship_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of applications linked to specific scholarship'
        ]);
        $table
            ->addColumn('applicant_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
            ])
            ->addColumn('requested_amount', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
                'null' => true
            ])
            ->addColumn('status_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to workflow_steps.id'
            ])
            ->addColumn('assignee_id', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
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
            ->addIndex('applicant_id')
            ->addIndex('scholarship_id')
            ->addIndex('status_id')
            ->addIndex('assignee_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
 
        // scholarship_application_institution_choices
        $table = $this->table('scholarship_application_institution_choices', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of institution choices linked to specific scholarship application'
        ]);

        $table
            ->addColumn('location_type', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'DOMESTIC, INTERNATIONAL'
            ])
            ->addColumn('institution_name', 'string', [ 
                'default' => null,
                'limit' => 150,
                'null' => true
            ])
            ->addColumn('estimated_cost', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('course_name', 'string', [
                'default' => null,
                'limit' => 150,
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
            ->addColumn('is_selected', 'integer', [    // Applicant selection
                'default' => 0,
                'null' => false,
                'limit' => 1,
                'comment' => '0 -> No, 1 -> Yes'
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('country_id', 'integer', [     // Will be set to 0 if it's domestic
                'default' => 0,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to countries.id'
            ])
            ->addColumn('scholarship_institution_choice_status_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarship_institution_choice_statuses.id' 
            ])
            ->addColumn('education_field_of_study_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to education_field_of_studies.id'
            ])
            ->addColumn('qualification_level_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to qualification_levels.id'
            ])
            ->addColumn('applicant_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
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
            ->addIndex('country_id')
            ->addIndex('scholarship_institution_choice_status_id')
            ->addIndex('education_field_of_study_id')
            ->addIndex('qualification_level_id')
            ->addIndex('applicant_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // scholarship_institution_choice_statuses
        $table = $this->table('scholarship_institution_choice_statuses', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the fixed list of statuses for institution choices in scholarship applications'
        ]);

        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 100
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->save();

        $table
            ->insert([
                [
                    'code' => 'PENDING_ACCEPTANCE',
                    'name' => 'Pending Acceptance'
                ],
                [
                    'code' => 'ACCEPTED',
                    'name' => 'Accepted'
                ],
                [
                    'code' => 'CONDITIONAL_OFFER',
                    'name' => 'Conditional Offer'
                ],
                [
                    'code' => 'UNCONDITIONAL_OFFER',
                    'name' => 'Unconditional Offer'
                ],
                [
                    'code' => 'REJECTED',
                    'name' => 'Rejected'
                ]
            ])
            ->save();

        // scholarship_application_attachments
        $table = $this->table('scholarship_application_attachments', [
            'id' => false,
            'primary_key' => 'id',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of attachments linked to specific scholarship application'
        ]);

        $table
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('file_name', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => false
            ])
            ->addColumn('scholarship_attachment_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to scholarship_attachment_types.id'
            ])
            ->addColumn('applicant_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
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
            ->addIndex('scholarship_attachment_type_id')
            ->addIndex('applicant_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // End of applicant =====================================================

        // Start of recipient ===================================================
        // scholarship_recipients
        $table = $this->table('scholarship_recipients', [
            'id' => false,
            'primary_key' => ['recipient_id', 'scholarship_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of recipients linked to specific scholarship'
        ]);

        $table
           ->addColumn('recipient_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
            ])
            ->addColumn('approved_amount', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
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
            ->addIndex('recipient_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // scholarship_recipient_activities
        $table = $this->table('scholarship_recipient_activities', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of activities linked to specific scholarship recipient'
        ]);

        $table
            ->addColumn('date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('scholarship_recipient_activity_status_id', 'integer', [ 
                'null' => true,
                'limit' => 11,
                'comment' => 'links to scholarship_recipient_activity_statuses.id'
            ])
            ->addColumn('recipient_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
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
            ->addIndex('scholarship_recipient_activity_status_id')
            ->addIndex('recipient_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

         // scholarship_recipient_activity_statuses
        $table = $this->table('scholarship_recipient_activity_statuses', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains the list of statuses used in scholarship recipient activities'
            ]);
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('visible', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('default', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
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

        // scholarship_recipient_payment_structures
        $table = $this->table('scholarship_recipient_payment_structures', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of payment structures linked to specific scholarship recipient'
        ]);

        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 50
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('academic_period_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('recipient_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
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
            ->addIndex('academic_period_id')
            ->addIndex('recipient_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // scholarship_recipient_payment_structure_estimates
        $table = $this->table('scholarship_recipient_payment_structure_estimates', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of payment structure estimates linked to specific scholarship recipient'
        ]);

        $table
            ->addColumn('estimated_disbursement_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('estimated_amount', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 15,
                'scale' => 2
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('scholarship_disbursement_category_id', 'integer', [
                'null' => true,
                'limit' => 11,
                'comment' => 'links to scholarship_disbursement_categories.id'
            ])
            ->addColumn('scholarship_recipient_payment_structure_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarship_recipient_payment_structures.id'
            ])
            ->addColumn('recipient_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
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
            ->addIndex('scholarship_disbursement_category_id')
            ->addIndex('scholarship_recipient_payment_structure_id')
            ->addIndex('recipient_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // scholarship_recipient_disbursements
        $table = $this->table('scholarship_recipient_disbursements', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of disbursements linked to specific scholarship recipient'
        ]);

        $table
            ->addColumn('disbursement_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('amount', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('scholarship_semester_id', 'integer', [ 
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarship_semesters.id'
            ])
            ->addColumn('scholarship_disbursement_category_id', 'integer', [ 
                'null' => true,
                'limit' => 11,
                'comment' => 'links to scholarship_disbursement_categories.id'
            ])
            ->addColumn('recipient_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
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
            ->addIndex('scholarship_semester_id')
            ->addIndex('scholarship_disbursement_category_id')
            ->addIndex('recipient_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

          // scholarship_semesters
        $table = $this->table('scholarship_semesters', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains the list of semesters used in scholarships'
            ]);
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('visible', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('default', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
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

        // scholarship_disbursement_categories
        $table = $this->table('scholarship_disbursement_categories', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains the list of disbursement categories used in scholarships'
            ]);
        $table
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
                'null' => false
            ])
            ->addColumn('visible', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('default', 'integer', [
                'default' => 0,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('international_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
            ])
            ->addColumn('national_code', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true
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

         // scholarship_recipient_collections
        $table = $this->table('scholarship_recipient_collections', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of collections linked to specific scholarship recipient'
        ]);
        $table
            ->addColumn('payment_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('amount', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('academic_period_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('recipient_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
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
            ->addIndex('academic_period_id')
            ->addIndex('recipient_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // scholarship_recipient_academic_standings
        $table = $this->table('scholarship_recipient_academic_standings', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of academic standings linked to specific scholarship recipient'
        ]);
        $table
            ->addColumn('date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('gpa', 'decimal', [
                'null' => false,
                'precision' => 3,
                'scale' => 2
            ])
            ->addColumn('comments', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('academic_period_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('scholarship_semester_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarship_semesters.id'
            ])
            ->addColumn('recipient_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
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
            ->addIndex('academic_period_id')
            ->addIndex('scholarship_semester_id')
            ->addIndex('recipient_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
    }

    public function down()
    {   
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');

        // delete workflow_models
        $this->execute("DELETE FROM `workflow_models` WHERE `id` = " . $this->workflowModelId);
        // delete workflows
        $workflowId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $this->workflowModelId])
            ->extract('id')
            ->first();
        $this->execute("DELETE FROM `workflows` WHERE `id` = " . $workflowId); 

        // delete workflow_actions
        $this->execute("DELETE FROM `workflow_actions` WHERE `workflow_actions`.`workflow_step_id` IN (
                SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId . "
            )");

        // delete workflow_steps
        $this->execute("DELETE FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId);

        $this->dropTable('scholarships');
        $this->dropTable('financial_assistance_types');
        $this->dropTable('scholarship_funding_sources');
        $this->dropTable('scholarships_field_of_studies');
        $this->dropTable('scholarship_payment_frequencies');
        $this->dropTable('scholarship_loans');
        $this->dropTable('scholarship_attachment_types');
        $this->dropTable('scholarship_applications');
        $this->dropTable('scholarship_application_institution_choices');
        $this->dropTable('scholarship_institution_choice_statuses');
        $this->dropTable('scholarship_application_attachments');
        $this->dropTable('scholarship_recipients');
        $this->dropTable('scholarship_recipient_activities');
        $this->dropTable('scholarship_recipient_activity_statuses');
        $this->dropTable('scholarship_recipient_payment_structures');
        $this->dropTable('scholarship_recipient_payment_structure_estimates');
        $this->dropTable('scholarship_recipient_disbursements');
        $this->dropTable('scholarship_semesters');
        $this->dropTable('scholarship_disbursement_categories');
        $this->dropTable('scholarship_recipient_collections');
        $this->dropTable('scholarship_recipient_academic_standings');
    }
}
