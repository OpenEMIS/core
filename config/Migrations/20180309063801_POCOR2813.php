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
        $WorkflowStatusesTable = TableRegistry::get('Workflow.WorkflowStatuses');

        $workflowModelData = [
            [
                'id' => $this->workflowModelId,
                'name' => 'Administration > Scholarships > Applications',
                'model' => 'Scholarship.ScholarshipApplications',
                'filter' => NULL,
                'is_school_based' => '0',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
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
            ],
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
                'name' => 'Pending For Review',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending For Approval',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Withdrawn',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Approved',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Rejected',
                'category' => '0',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
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
        $pendingForReviewStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending For Review'
            ])
            ->extract('id')
            ->first();
        $pendingForApprovalStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending For Approval'
            ])
            ->extract('id')
            ->first();
        $withdrawnStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Withdrawn'
            ])
            ->extract('id')
            ->first();
        $approvedStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Approved'
            ])
            ->extract('id')
            ->first();
        $rejectedStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 0,
                $WorkflowStepsTable->aliasField('name') => 'Rejected'
            ])
            ->extract('id')
            ->first();

        //  workflow_actions
        $workflowActionData = [
            [
                'name' => 'Submit For Approval',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '1',
                'event_key' => NULL,
                'workflow_step_id' => $openStatusId,
                'next_workflow_step_id' => $pendingForReviewStatusId,
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
                'event_key' => NULL,
                'workflow_step_id' => $pendingForReviewStatusId,
                'next_workflow_step_id' => $pendingForApprovalStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'description' => NULL,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingForReviewStatusId,
                'next_workflow_step_id' => $rejectedStatusId,
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
                'event_key' => 'Workflow.onApproveScholarship',
                'workflow_step_id' => $pendingForApprovalStatusId,
                'next_workflow_step_id' => $approvedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'description' => NULL,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingForApprovalStatusId,
                'next_workflow_step_id' => $rejectedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Withdraw From Scholarship Application',
                'description' => NULL,
                'action' => NULL,
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '1',
                'event_key' => 'Workflow.onWithdrawScholarship',
                'workflow_step_id' => $approvedStatusId,
                'next_workflow_step_id' => $withdrawnStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_actions', $workflowActionData);

         // workflow_statuses
        $workflowStatusesData = [
            [
                'code' => 'PENDINGREVIEW',
                'name' => 'Pending Review',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->workflowModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'PENDINGAPPROVAL',
                'name' => 'Pending Approval',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->workflowModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'APPROVED',
                'name' => 'Approved',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->workflowModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'REJECTED',
                'name' => 'Rejected',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->workflowModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_statuses', $workflowStatusesData);


        $pendingReviewId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'PENDINGREVIEW',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->workflowModelId
            ])
            ->extract('id')
            ->first();
        $pendingApprovalId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'PENDINGAPPROVAL',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->workflowModelId
            ])
            ->extract('id')
            ->first();
        $approvedId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'APPROVED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->workflowModelId
            ])
            ->extract('id')
            ->first();
        $rejectedId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'REJECTED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->workflowModelId
            ])
            ->extract('id')
            ->first();

        // workflow_statuses_steps
        $workflowStatusesStepsData = [
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingReviewId,
                'workflow_step_id' => $pendingForReviewStatusId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingApprovalId,
                'workflow_step_id' => $pendingForApprovalStatusId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $approvedId,
                'workflow_step_id' => $approvedStatusId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $rejectedId,
                'workflow_step_id' => $rejectedStatusId
            ]
        ];
        $this->insert('workflow_statuses_steps', $workflowStatusesStepsData);


        $table = $this->table('scholarships', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the scholarships'
        ]);

        $table
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 45
            ])
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 250
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('financial_assistance_type_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to financial_assistance_types.id'
            ])
            ->addColumn('funding_source_id', 'integer', [ 
                'null' => true,
                'limit' => 11,
                'comment' => 'links to funding_sources.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('date_application_open', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('date_application_close', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('max_award_amount', 'decimal', [
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
             ->addColumn('requirement', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('instruction', 'text', [
                'default' => null,
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
            ->addIndex('financial_assistance_type_id')
            ->addIndex('funding_source_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // Financial Assistance Type
        $table = $this->table('financial_assistance_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of available custom financial assistance types'
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
                    'code' => 'GRANT',
                    'name' => 'Grant'
                ],
                [
                    'code' => 'LOAN',
                    'name' => 'Loan'
                ],
                [
                    'code' => 'WORKSTUDY',
                    'name' => 'Workstudy'
                ]
            ])
            ->save();

        // Funding sources
        $table = $this->table('funding_sources', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of funding sources'
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
        // end funding sources


        // scholarships_education_field_of_studies
        $table = $this->table('scholarships_education_field_of_studies', [
            'id' => false,
            'primary_key' => [
                'scholarship_id',
                'education_field_of_study_id',
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the education field of studies for the scholarships'
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
        // end scholarships_education_field_of_studies


        // payment frequencies 
        $table = $this->table('payment_frequencies', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains payment frequencies'
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
        // end payment frequencies    

       // Table for Financial Assistance Type (LOANS)
        $table = $this->table('scholarship_loans', [
            'id' => false,
            'primary_key' => ['scholarship_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the loan details for a specific scholarship'
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
                'comment' => '0 - Fixed, 1 - Variable'
            ])
            ->addColumn('payment_frequency_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to payment_frequencies.id'
            ])
            ->addColumn('loan_term', 'integer', [
                'limit' => 2,
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
            ->addIndex('scholarship_id')
            ->addIndex('payment_frequency_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // Scholarship Attachment Types
        $table = $this->table('scholarship_attachment_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the types of attachments for the scholarships'
        ]);

        $table
            ->addColumn('name', 'string', [
                'null' => false,
                'limit' => 45
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

        // scholarship applications
        $table = $this->table('scholarship_applications', [
            'id' => false,
            'primary_key' => ['applicant_id', 'scholarship_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the scholarship applications'
        ]);
        $table
            ->addColumn('id', 'char', [
                'default' => null,
                'limit' => 64,
                'null' => false
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
            ->addColumn('requested_amount', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
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
            ->addIndex('applicant_id')
            ->addIndex('scholarship_id')
            ->addIndex('assignee_id')
            ->addIndex('status_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
            // end scholarship applications
 
        $table = $this->table('scholarship_institution_choices', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the institution choices of the scholarship application'
        ]);

        $table
            ->addColumn('location_type_id', 'integer', [
                'limit' => 1,
                'null' => false,
                'comment' => '0 - Domestic, 1 - International'
            ])
            ->addColumn('country_id', 'integer', [     // Will be set to 0 if it's domestic
                'default' => 0,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to countries.id'
            ])
            ->addColumn('institution_name', 'string', [ 
                'default' => null,
                'limit' => 150,
                'null' => true
            ])
            ->addColumn('institution_choice_status_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to institution_choice_statuses.id' 
            ])
            ->addColumn('estimated_cost', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('education_field_of_study_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to education_field_of_studies.id'
            ])
            ->addColumn('course_name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false
            ])
            ->addColumn('level_of_study_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to qualification_levels.id'
            ])
            ->addColumn('start_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'null' => false
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
            ->addColumn('selection_id', 'integer', [    //Applicant selection
                'default' => 0,
                'null' => false,
                'limit' => 1,
                'comment' => '0 - No, 1 - Yes'
            ])
            ->addColumn('order', 'integer', [
                'default' => null,
                'limit' => 3,
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
            ->addIndex('country_id')
            ->addIndex('institution_choice_status_id')
            ->addIndex('education_field_of_study_id')
            ->addIndex('level_of_study_id')
            ->addIndex('applicant_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $table = $this->table('institution_choice_statuses', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of available custom financial assistance types'
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
                    'code' => 'PENDING ACCEPTANCE',
                    'name' => 'Pending Acceptance'
                ],
                [
                    'code' => 'ACCEPTED',
                    'name' => 'Accepted'
                ],
                [
                    'code' => 'CONDITIONAL OFFER',
                    'name' => 'Conditional Offer'
                ],
                [
                    'code' => 'UNCONDITIONAL OFFER',
                    'name' => 'Unconditional Offer'
                ],
                [
                    'code' => 'REJECTED',
                    'name' => 'Rejected'
                ]
            ])
            ->save();

        $table = $this->table('scholarship_application_attachments', [
            'id' => false,
            'primary_key' => 'id',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the attachments for the scholarship applications'
        ]);

        $table
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false
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
            ->addColumn('scholarship_attachment_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to scholarship_attachment_types.id'
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
            ->addIndex('scholarship_attachment_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // End of Applicant ====================================================            


        // Start of recipient ===================================================
        // scholarship recipient 

        $table = $this->table('scholarship_recipients', [
            'id' => false,
            'primary_key' => ['recipient_id', 'scholarship_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the scholarship recipients'
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
            ->addIndex('recipient_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $table = $this->table('scholarship_recipient_activities', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the activities of the recipients'
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
            ->addColumn('date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('activity_status_id', 'integer', [ 
                'null' => true,
                'limit' => 11,
                'comment' => 'links to activity_statuses.id'
            ])
            ->addColumn('comment', 'string', [
                'null' => false,
                'limit' => 250
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
            ->addIndex('activity_status_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

         // recipient_activity_status
        $table = $this->table('activity_statuses', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of recipient activity statuses'
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
        // end recipient_activity_statuses


        $table = $this->table('recipient_payment_structures', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the payment structures of the recipients'
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
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 45
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
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $table = $this->table('recipient_payment_structure_estimates', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the payment structure estimates of the recipients'
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
            ->addColumn('recipient_payment_structure_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to recipient_payment_structures.id'
            ])
            ->addColumn('payment_structure_category_id', 'integer', [ 
                'null' => true,
                'limit' => 11,
                'comment' => 'links to payment_structure_categories.id'
            ])
            ->addColumn('estimated_disbursement_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('estimated_amount', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('comment', 'string', [
                'null' => false,
                'limit' => 250
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
            ->addIndex('recipient_payment_structure_id')
            ->addIndex('payment_structure_category_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();


        $table = $this->table('scholarship_recipient_disbursements', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the disbursement of the recipients'
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
            ->addColumn('semester_id', 'integer', [ 
                'null' => false,
                'limit' => 11,
                'comment' => 'links to semesters.id'
            ])
            ->addColumn('disbursement_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('amount', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('payment_structure_category_id', 'integer', [ 
                'null' => true,
                'limit' => 11,
                'comment' => 'links to payment_structure_categories.id'
            ])
            ->addColumn('comment', 'string', [
                'null' => false,
                'limit' => 250
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
            ->addIndex('semester_id')
            ->addIndex('payment_structure_category_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

          // disbursement_semesters
        $table = $this->table('semesters', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of semesters'
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
        // end disbursement_semesters


         // payment_structure_categories
        $table = $this->table('payment_structure_categories', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of payment structure categories'
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
        // end payment_structure_categories 

         // collection       
        $table = $this->table('scholarship_recipient_collections', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the collections of the recipients'
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
            ->addColumn('academic_period_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('payment_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('amount', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
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
            ->addIndex('recipient_id')
            ->addIndex('scholarship_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // collection        

        // academic standing    
        $table = $this->table('scholarship_recipient_academic_standings', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the academic standings of all recipients'
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
            ->addColumn('academic_period_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('semester_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to semesters.id'
            ])
            ->addColumn('date_entered', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('student_gpa', 'decimal', [
                'null' => true,
                'precision' => 3,
                'scale' => 2
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
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
            ->addIndex('recipient_id')
            ->addIndex('scholarship_id')
            ->addIndex('academic_period_id')
            ->addIndex('semester_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // end of academic standing

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

        // delete workflow_statuses_steps
        $this->execute("DELETE FROM `workflow_statuses_steps` WHERE `workflow_statuses_steps`.`workflow_step_id` IN (
                SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId . "
            )");


        // delete workflow_steps
        $this->execute("DELETE FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId);

        // delete workflow_statuses
        $this->execute("DELETE FROM `workflow_statuses` WHERE `workflow_model_id` = " . $this->workflowModelId);

        // delete workflow_transitions
        $this->execute("DELETE FROM `workflow_transitions` WHERE `workflow_model_id` = " . $this->workflowModelId);


        $this->dropTable('scholarships');
        $this->dropTable('financial_assistance_types');
        $this->dropTable('funding_sources');
        $this->dropTable('scholarships_education_field_of_studies');
        $this->dropTable('payment_frequencies');  
        $this->dropTable('scholarship_loans');
        $this->dropTable('scholarship_attachment_types');
        $this->dropTable('scholarship_applications');
        $this->dropTable('scholarship_institution_choices');
        $this->dropTable('institution_choice_statuses');
        $this->dropTable('scholarship_application_attachments');
        $this->dropTable('scholarship_recipients');
        $this->dropTable('scholarship_recipient_activities');
        $this->dropTable('activity_statuses');
        $this->dropTable('recipient_payment_structures');
        $this->dropTable('recipient_payment_structure_estimates');
        $this->dropTable('scholarship_recipient_disbursements');
        $this->dropTable('semesters');
        $this->dropTable('payment_structure_categories');
        $this->dropTable('scholarship_recipient_collections');
        $this->dropTable('scholarship_recipient_academic_standings');
    
    }
}
