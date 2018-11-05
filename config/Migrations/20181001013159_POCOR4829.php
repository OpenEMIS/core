<?php

use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;

class POCOR4829 extends AbstractMigration
{
    public function up()
    {
        // Remove wrong tables
        $this->execute('DROP TABLE IF EXISTS `special_needs_purpose_types`');
        $this->execute('DROP TABLE IF EXISTS `special_needs_visit_types`');

        $today = date('Y-m-d H:i:s');
        
        // security_functions
        $this->execute('CREATE TABLE `z_4829_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4829_security_functions` SELECT * FROM `security_functions`');

        /*
            Updated Visits permissions
            Institution > Students
                - Requests (id: 2046, order: 150)
                - Visits   (id: 2047, order: 151)
         */

        $this->execute('UPDATE `security_functions` set `order` = `order` + 2 WHERE `order` >= 150');
        $securityFunctionData = [
            [
                'id' => 2046,
                'name' => 'Visit Requests',
                'controller' => 'Students',
                'module' => 'Institutions',
                'category' => 'Students - Visits',
                'parent_id' => 2000,
                '_view' => 'StudentVisitRequests.index|StudentVisitRequests.view',
                '_edit' => 'StudentVisitRequests.edit',
                '_add' => 'StudentVisitRequests.add',
                '_delete' => 'StudentVisitRequests.remove',
                '_execute' => 'StudentVisitRequests.download',
                'order' => 150,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'id' => 2047,
                'name' => 'Visits',
                'controller' => 'Students',
                'module' => 'Institutions',
                'category' => 'Students - Visits',
                'parent_id' => 2000,
                '_view' => 'StudentVisits.index|StudentVisits.view',
                '_edit' => 'StudentVisits.edit',
                '_add' => 'StudentVisits.add',
                '_delete' => 'StudentVisits.remove',
                '_execute' => 'StudentVisits.download',
                'order' => 151,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => $today
            ]
        ];
        $this->insert('security_functions', $securityFunctionData);

        /*
            Updated Special Needs permissions with download permission
            Institution > Students
                - Referrals   (id: 2041, order: 145) - SpecialNeedsReferrals
                - Assessments (id: 2042, order: 146) - SpecialNeedsAssessments
                - Services    (id: 2043, order: 147) - SpecialNeedsServices
                - Plans       (id: 2045, order: 149) - SpecialNeedsPlans

            Institution > Staff 
                - Referrals   (id: 3050, order: 188) + 5 - SpecialNeedsReferrals
                - Assessments (id: 3051, order: 189) + 5 - SpecialNeedsAssessments
                - Services    (id: 3052, order: 190) + 5 - SpecialNeedsServices
                - Plans       (id: 3054, order: 192) + 5 - SpecialNeedsPlans

            Directories
                - Referrals   (id: 7063, order: 332) + 10 - SpecialNeedsReferrals
                - Assessments (id: 7064, order: 333) + 10 - SpecialNeedsAssessments
                - Services    (id: 7065, order: 334) + 10 - SpecialNeedsServices
                - Plans       (id: 7067, order: 336) + 10 - SpecialNeedsPlans
         */

        // security_functions - END
        $this->execute('UPDATE `security_functions` SET `_execute` = "SpecialNeedsReferrals.download" WHERE `id` IN (2041, 3050, 7063)');
        $this->execute('UPDATE `security_functions` SET `_execute` = "SpecialNeedsAssessments.download" WHERE `id` IN (2042, 3051, 7064)');
        $this->execute('UPDATE `security_functions` SET `_execute` = "SpecialNeedsServices.download" WHERE `id` IN (2043, 3052, 7065)');
        $this->execute('UPDATE `security_functions` SET `_execute` = "SpecialNeedsPlans.download" WHERE `id` IN (2045, 3054, 7067)');

        // student_visit_types
        $StudentVisitTypes = $this->table('student_visit_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the visit types for all institution students'
        ]);

        $StudentVisitTypes
            ->addColumn('code', 'string', [
                'limit' => 100,
                'null' => false,
                'default' => null
            ])
            ->addColumn('name', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => null
            ])
            ->addIndex('code')
            ->save();

        $visitTypeData = [
            [
                'id' => 1,
                'code' => 'INSTITUTION_VISIT',
                'name' => 'Institution Visit'
            ],
            [
                'id' => 2,
                'code' => 'HOME_VISIT',
                'name' => 'Home Visit'
            ]
        ];

        $this->insert('student_visit_types', $visitTypeData);
        // student_visit_types - END

        // student_visit_purpose_types
        $StudentVisitPurposeTypes = $this->table('student_visit_purpose_types', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the visit purpose types for all institution students'
        ]);

        $StudentVisitPurposeTypes
            ->addColumn('name', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => null
            ])
            ->addColumn('order', 'integer', [
                'limit' => 3,
                'null' => false,
                'default' => null
            ])
            ->addColumn('visible', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 1
            ])
            ->addColumn('editable', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 1
            ])
            ->addColumn('default', 'integer', [
                'limit' => 1,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('international_code', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
            ])
            ->addColumn('national_code', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null
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
        // student_visit_purpose_types - END

        // Workflow Patch
        $today = date('Y-m-d H:i:s');
        $modelId = 21;

        // workflow_models
        $this->execute('CREATE TABLE `z_4829_workflow_models` LIKE `workflow_models`');
        $this->execute('INSERT INTO `z_4829_workflow_models` SELECT * FROM `workflow_models`');

        $workflowModelData = [
            'id' => $modelId,
            'name' => 'Students > Visits > Requests',
            'model' => 'Student.StudentVisitRequests',
            'filter' => NULL,
            'is_school_based' => 1,
            'created_user_id' => 1,
            'created' => $today
        ];

        $this->insert('workflow_models', $workflowModelData);

        // workflows
        $this->execute('CREATE TABLE `z_4829_workflows` LIKE `workflows`');
        $this->execute('INSERT INTO `z_4829_workflows` SELECT * FROM `workflows`');

        $workflowData = [
            [
                'code' => 'STUDENT-VISIT-1001',
                'name' => 'Student - Visit Requests',
                'workflow_model_id' => $modelId,
                'created_user_id' => 1,
                'created' => $today
            ]
        ];

        $this->insert('workflows', $workflowData);

        // workflow_steps
        $this->execute('CREATE TABLE `z_4829_workflow_steps` LIKE `workflow_steps`');
        $this->execute('INSERT INTO `z_4829_workflow_steps` SELECT * FROM `workflow_steps`');

        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');
        $workflowId = $WorkflowsTable
            ->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $modelId])
            ->extract('id')
            ->first();

        $workflowStepData = [
            [
                'name' => 'Open',
                'category' => 1,
                'is_editable' => 1,
                'is_removable' => 1,
                'is_system_defined' => 1,
                'workflow_id' => $workflowId,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'name' => 'Pending For Approval',
                'category' => 2,
                'is_editable' => 0,
                'is_removable' => 0,
                'is_system_defined' => 1,
                'workflow_id' => $workflowId,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'name' => 'Closed',
                'category' => 3,
                'is_editable' => 0,
                'is_removable' => 0,
                'is_system_defined' => 1,
                'workflow_id' => $workflowId,
                'created_user_id' => 1,
                'created' => $today
            ]
        ];

        $this->insert('workflow_steps', $workflowStepData);

        // workflow_actions
        $this->execute('CREATE TABLE `z_4829_workflow_actions` LIKE `workflow_actions`');
        $this->execute('INSERT INTO `z_4829_workflow_actions` SELECT * FROM `workflow_actions`');

        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');

        $openStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 1
            ])
            ->extract('id')
            ->first();

        $pendingStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 2
            ])
            ->extract('id')
            ->first();

        $closedStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 3
            ])
            ->extract('id')
            ->first();

        $workflowActionData = [
            [
                'name' => 'Submit For Approval',
                'action' => 0,
                'visible' => 1,
                'comment_required' => 0,
                'allow_by_assignee' => 1,
                'workflow_step_id' => $openStepId,
                'next_workflow_step_id' => $pendingStepId,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'name' => 'Cancel',
                'action' => 1,
                'visible' => 1,
                'comment_required' => 0,
                'allow_by_assignee' => 1,
                'workflow_step_id' => $openStepId,
                'next_workflow_step_id' => $closedStepId,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'name' => 'Approve',
                'action' => 0,
                'visible' => 1,
                'comment_required' => 0,
                'allow_by_assignee' => 1,
                'event_key' => 'Workflow.onAssignBack',
                'workflow_step_id' => $pendingStepId,
                'next_workflow_step_id' => $closedStepId,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'name' => 'Reject',
                'action' => 1,
                'visible' => 1,
                'comment_required' => 0,
                'allow_by_assignee' => 1,
                'event_key' => 'Workflow.onAssignBack',
                'workflow_step_id' => $pendingStepId,
                'next_workflow_step_id' => $openStepId,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'name' => 'Approve',
                'action' => 0,
                'visible' => 0,
                'comment_required' => 0,
                'allow_by_assignee' => 0,
                'workflow_step_id' => $closedStepId,
                'next_workflow_step_id' => 0,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'name' => 'Reject',
                'action' => 1,
                'visible' => 0,
                'comment_required' => 0,
                'allow_by_assignee' => 0,
                'workflow_step_id' => $closedStepId,
                'next_workflow_step_id' => 0,
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'name' => 'Reopen',
                'visible' => 1,
                'comment_required' => 0,
                'allow_by_assignee' => 0,
                'workflow_step_id' => $closedStepId,
                'next_workflow_step_id' => $openStepId,
                'created_user_id' => 1,
                'created' => $today
            ]
        ];

        $this->insert('workflow_actions', $workflowActionData);
        // Workflow Patch - END

        // institution_student_visit_requests
        $InstitutionStudentVisitRequests = $this->table('institution_student_visit_requests', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the visit requests for all institution students'
        ]);

        $InstitutionStudentVisitRequests
            ->addColumn('date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('file_name', 'string', [
                'null' => true,
                'limit' => 250,
                'default' => null
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('evaluator_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('student_visit_type_id', 'integer', [
                'comment' => 'links to student_visit_types.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('student_visit_purpose_type_id', 'integer', [
                'comment' => 'links to student_visit_purpose_types.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => 0,
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
            ->addIndex('academic_period_id')
            ->addIndex('evaluator_id')
            ->addIndex('student_id')
            ->addIndex('institution_id')
            ->addIndex('student_visit_type_id')
            ->addIndex('student_visit_purpose_type_id')
            ->addIndex('status_id')
            ->addIndex('assignee_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // institution_student_visit_requests - END

        // institution_student_visits
        $InstitutionStudentVisits = $this->table('institution_student_visits', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the visits for all institution students'
        ]);

        $InstitutionStudentVisits
            ->addColumn('date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('file_name', 'string', [
                'null' => true,
                'limit' => 250,
                'default' => null
            ])
            ->addColumn('file_content', 'blob', [
                'limit' => '4294967295',
                'default' => null,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('academic_period_id', 'integer', [
                'comment' => 'links to academic_periods.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('evaluator_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('student_id', 'integer', [
                'comment' => 'links to security_users.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('institution_id', 'integer', [
                'comment' => 'links to institutions.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('student_visit_type_id', 'integer', [
                'comment' => 'links to student_visit_types.id',
                'limit' => 11,
                'default' => null,
                'null' => false
            ])
            ->addColumn('student_visit_purpose_type_id', 'integer', [
                'comment' => 'links to student_visit_purpose_types.id',
                'limit' => 11,
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
            ->addIndex('academic_period_id')
            ->addIndex('evaluator_id')
            ->addIndex('student_id')
            ->addIndex('institution_id')
            ->addIndex('student_visit_type_id')
            ->addIndex('student_visit_purpose_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
        // institution_student_visits - END
    }

    public function down()
    {
        // security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_4829_security_functions` TO `security_functions`');

        // student_visit_types
        $this->execute('DROP TABLE IF EXISTS `student_visit_types`');

        // student_visit_purpose_types
        $this->execute('DROP TABLE IF EXISTS `student_visit_purpose_types`');

        // institution_student_visit_requests
        $this->execute('DROP TABLE IF EXISTS `institution_student_visit_requests`');

        // institution_student_visits
        $this->execute('DROP TABLE IF EXISTS `institution_student_visits`');

        // Workflows table
        $this->execute('DROP TABLE IF EXISTS `workflow_models`');
        $this->execute('RENAME TABLE `z_4829_workflow_models` TO `workflow_models`');

        $this->execute('DROP TABLE IF EXISTS `workflows`');
        $this->execute('RENAME TABLE `z_4829_workflows` TO `workflows`');

        $this->execute('DROP TABLE IF EXISTS `workflow_steps`');
        $this->execute('RENAME TABLE `z_4829_workflow_steps` TO `workflow_steps`');

        $this->execute('DROP TABLE IF EXISTS `workflow_actions`');
        $this->execute('RENAME TABLE `z_4829_workflow_actions` TO `workflow_actions`');
    }
}
