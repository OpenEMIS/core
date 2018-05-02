<?php
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;

class POCOR2813 extends AbstractMigration
{
    private $workflowModelId = 19;

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
                'is_editable' => '0',
                'is_removable' => '0',
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
                'event_key' => NULL,
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
            ->addColumn('date_applications_open', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('date_applications_close', 'date', [
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
            ->addColumn('id', 'char', [
                'default' => null,
                'limit' => 64,
                'null' => false
            ])
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


        // Scholarship Attachments
        $table = $this->table('scholarship_attachments', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the attachments for the scholarships'
        ]);

        $table
            ->addColumn('type', 'string', [
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

        //TBC 
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
            ->addColumn('institution_name', 'string', [ // Will be null if it's domestic
                'default' => null,
                'limit' => 150,
                'null' => true
            ])
            ->addColumn('institution_choice_status_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to institution_choice_statuses.id' // need to create the fixed field option
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
                    'name' => 'Accepteds'
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

        $table = $this->table('scholarship_applications_attachments', [
            'id' => false,
            'primary_key' => [
                'applicant_id',
                'scholarship_id',
                'scholarship_attachment_id',
            ],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the attachments for the scholarship applications'
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
            ->addColumn('scholarship_attachment_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to scholarship_attachments.id'
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false
            ])
            ->addColumn('file', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false
            ])
            ->addIndex('applicant_id')
            ->addIndex('scholarship_id')
            ->addIndex('scholarship_attachment_id')
            ->save();

        // End of Applicant ====================================================            


        // Start of Recipent ===================================================
        // scholarship recipents 

        $table = $this->table('scholarship_recipents', [
            'id' => false,
            'primary_key' => ['recipent_id', 'scholarship_id'],
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the scholarship recipents'
        ]);

        $table
           ->addColumn('recipent_id', 'integer', [
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
            ->addIndex('recipent_id')
            ->addIndex('scholarship_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $table = $this->table('recipent_activities', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the activities of the recipents'
        ]);

        $table
           ->addColumn('recipent_id', 'integer', [
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
            ->addIndex('recipent_id')
            ->addIndex('scholarship_id')
            ->addIndex('activity_status_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

         // recipent_activity_status
        $table = $this->table('activity_statuses', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This field options table contains types of recipent activity statuses'
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
        // end recipent_activity_statuses


        $table = $this->table('recipent_payment_structures', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the payment structures of the recipents'
        ]);

        $table
           ->addColumn('recipent_id', 'integer', [
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
            ->addIndex('recipent_id')
            ->addIndex('scholarship_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $table = $this->table('recipent_payment_structure_estimates', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the payment structure estimates of the recipents'
        ]);

        $table
            ->addColumn('recipent_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('scholarship_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to scholarships.id'
            ])
            ->addColumn('recipent_payment_structure_id', 'integer', [
                'null' => false,
                'limit' => 11,
                'comment' => 'links to recipent_payment_structures.id'
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
            ->addIndex('recipent_id')
            ->addIndex('scholarship_id')
            ->addIndex('recipent_payment_structure_id')
            ->addIndex('payment_structure_category_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();


        $table = $this->table('recipent_disbursements', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the disbursement of the recipents'
        ]);

        $table
           ->addColumn('recipent_id', 'integer', [
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
            ->addIndex('recipent_id')
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
        $table = $this->table('recipent_collections', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the collections of the recipents'
        ]);
        $table
            ->addColumn('recipent_id', 'integer', [
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
            ->addIndex('recipent_id')
            ->addIndex('scholarship_id')
            ->addIndex('academic_period_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // collection        

        // academic standing    
        $table = $this->table('recipent_academic_standings', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the academic standings of all recipents'
        ]);
        $table
            ->addColumn('recipent_id', 'integer', [
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
            ->addIndex('recipent_id')
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

        // delete workflow_steps
        $this->execute("DELETE FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId);

        $this->dropTable('scholarships');
        $this->dropTable('financial_assistance_types');
        $this->dropTable('funding_sources');
        $this->dropTable('scholarships_education_field_of_studies');
        $this->dropTable('payment_frequencies');  
        $this->dropTable('scholarship_loans');
        $this->dropTable('scholarship_attachments');
        $this->dropTable('scholarship_applications');
        $this->dropTable('scholarship_institution_choices');
        $this->dropTable('institution_choice_statuses');
        $this->dropTable('scholarship_applications_attachments');
        $this->dropTable('scholarship_recipents');
        $this->dropTable('recipent_activities');
        $this->dropTable('activity_statuses');
        $this->dropTable('recipent_payment_structures');
        $this->dropTable('recipent_payment_structure_estimates');
        $this->dropTable('recipent_disbursements');
        $this->dropTable('semesters');
        $this->dropTable('payment_structure_categories');
        $this->dropTable('recipent_collections');
        $this->dropTable('recipent_academic_standings');
    }
}
