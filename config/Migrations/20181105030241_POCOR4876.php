<?php
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Text;

class POCOR4876 extends AbstractMigration
{
    private $workflowModelStaffReleaseInId = 22;
    private $workflowModelStaffReleaseOutId = 23;

    public function up()
    {
        // backup the table
        $this->execute('CREATE TABLE `z_4876_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `z_4876_config_items` SELECT * FROM `config_items`');
        $this->execute('CREATE TABLE `z_4876_workflow_models` LIKE `workflow_models`');
        $this->execute('INSERT INTO `z_4876_workflow_models` SELECT * FROM `workflow_models`');
        $this->execute('CREATE TABLE `z_4876_workflows` LIKE `workflows`');
        $this->execute('INSERT INTO `z_4876_workflows` SELECT * FROM `workflows`');
        $this->execute('CREATE TABLE `z_4876_workflow_steps` LIKE `workflow_steps`');
        $this->execute('INSERT INTO `z_4876_workflow_steps` SELECT * FROM `workflow_steps`');
        $this->execute('CREATE TABLE `z_4876_workflow_actions` LIKE `workflow_actions`');
        $this->execute('INSERT INTO `z_4876_workflow_actions` SELECT * FROM `workflow_actions`');
        $this->execute('CREATE TABLE `z_4876_workflow_steps_params` LIKE `workflow_steps_params`');
        $this->execute('INSERT INTO `z_4876_workflow_steps_params` SELECT * FROM `workflow_steps_params`');
        $this->execute('CREATE TABLE `z_4876_labels` LIKE `labels`');
        $this->execute('INSERT INTO `z_4876_labels` SELECT * FROM `labels`');
        $this->execute('CREATE TABLE `z_4876_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_4876_security_functions` SELECT * FROM `security_functions`');
        // end backup

        //Create table for staff release
        $InstitutionStaffReleases = $this->table('institution_staff_releases', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the staff release requests'
        ]);
        $InstitutionStaffReleases
            ->addColumn('previous_FTE', 'decimal', [
                'default' => null,
                'precision' => 5,
                'scale' => 2,
                'null' => true
            ])
            ->addColumn('previous_start_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('previous_end_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('new_FTE', 'decimal', [
                'default' => null,
                'precision' => 5,
                'scale' => 2,
                'null' => true
            ])
            ->addColumn('new_start_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('new_end_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('all_visible', 'integer', [
                'default' => '0',
                'limit' => 1,
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
            ->addColumn('staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('previous_institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('previous_institution_staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_staff.id'
            ])
            ->addColumn('previous_staff_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to staff_types.id'
            ])
            ->addColumn('new_institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('new_institution_position_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_positions.id'
            ])
            ->addColumn('new_staff_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to staff_types.id'
            ])
            ->addIndex('staff_id')
            ->addIndex('previous_institution_id')
            ->addIndex('status_id')
            ->addIndex('assignee_id')
            ->addIndex('previous_institution_staff_id')
            ->addIndex('previous_staff_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->addIndex('new_institution_id')
            ->addIndex('new_institution_position_id')
            ->addIndex('new_staff_type_id')
            ->save();

        // Insert 3 rows into config item table for Staff Release.
        $configData = [
            [
                'id' => 1020,
                'name' => 'Enable Staff Release By Types',
                'code' => 'staff_release_by_types',
                'type' => 'Staff Releases',
                'label' => 'Enable Staff Release By Types',
                'value' => "",
                'default_value' => '{"selection":"0"}',
                'editable' => 1,
                'visible' => 1,
                'field_type' => "",
                'option_type' => "",
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 1021,
                'name' => 'Enable Staff Release By Sectors',
                'code' => 'staff_release_by_sectors',
                'type' => 'Staff Releases',
                'label' => 'Enable Staff Release By Sectors',
                'value' => "",
                'default_value' => '{"selection":"0"}',
                'editable' => 1,
                'visible' => 1,
                'field_type' => "",
                'option_type' => "",
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 1022,
                'name' => 'Restrict Staff Release Between Same Type',
                'code' => 'restrict_staff_release_between_same_type',
                'type' => 'Staff Releases',
                'label' => 'Restrict Staff Release Between Same Type',
                'value' => "",
                'default_value' => "0",
                'editable' => 1,
                'visible' => 1,
                'field_type' => "Dropdown",
                'option_type' => "yes_no",
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->insert('config_items', $configData);

        //Inserting into workflows,workflow_model,workflow_steps,workflow_actions tables
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');
        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
        $WorkflowStatusesTable = TableRegistry::get('Workflow.WorkflowStatuses');

        $workflowModelData = [
            [
                'id' => $this->workflowModelStaffReleaseOutId,
                'name' => 'Institutions > Staff Release',
                'model' => 'Institution.StaffRelease',
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_models', $workflowModelData);

        $workflowData = [
            [
                'code' => 'STAFF-RELEASE-1001',
                'name' => 'Staff Release',
                'workflow_model_id' => $this->workflowModelStaffReleaseOutId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflows', $workflowData);

        $staffReleaseOutWorkflowId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $this->workflowModelStaffReleaseOutId])
            ->extract('id')
            ->first();

        // workflow_steps for outgoing staff release
        $workflowStepData = [
            [
                'name' => 'Open',
                'category' => '1',
                'is_editable' => '1',
                'is_removable' => '1',
                'is_system_defined' => '1',
                'workflow_id' => $staffReleaseOutWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $staffReleaseOutWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval From Receiving Institution',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $staffReleaseOutWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Staff Release',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $staffReleaseOutWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Released',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $staffReleaseOutWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Closed',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $staffReleaseOutWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_steps', $workflowStepData);

        // Get the workflowSteps id for the created workflowsteps for out
        $outOpenStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $staffReleaseOutWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 1,
            ])
            ->extract('id')
            ->first();
        $outPendingApprovalStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $staffReleaseOutWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval'
            ])
            ->extract('id')
            ->first();
        $outPendingApprovalIncomingStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $staffReleaseOutWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval From Receiving Institution'
            ])
            ->extract('id')
            ->first();
        $outPendingReleaseStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $staffReleaseOutWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Staff Release'
            ])
            ->extract('id')
            ->first();
        $outReleaseStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $staffReleaseOutWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Released'
            ])
            ->extract('id')
            ->first();
        $outClosedStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $staffReleaseOutWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Closed'
            ])
            ->extract('id')
            ->first();

        // workflow_actions for out
        $workflowActionData = [
            [
                'name' => 'Submit For Approval',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '1',
                'event_key' => NULL,
                'workflow_step_id' => $outOpenStatusId,
                'next_workflow_step_id' => $outPendingApprovalStatusId,
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
                'workflow_step_id' => $outPendingApprovalStatusId,
                'next_workflow_step_id' => $outPendingApprovalIncomingStatusId,
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
                'workflow_step_id' => $outPendingApprovalStatusId,
                'next_workflow_step_id' => $outClosedStatusId,
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
                'workflow_step_id' => $outPendingApprovalIncomingStatusId,
                'next_workflow_step_id' => $outPendingReleaseStatusId,
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
                'workflow_step_id' => $outPendingApprovalIncomingStatusId,
                'next_workflow_step_id' => $outClosedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Release',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => 'Workflow.onReleaseStaff',
                'workflow_step_id' => $outPendingReleaseStatusId,
                'next_workflow_step_id' => $outReleaseStatusId,
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
                'workflow_step_id' => $outPendingReleaseStatusId,
                'next_workflow_step_id' => $outClosedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_actions', $workflowActionData);

        // workflow_steps_params for out
        $institutionOwner = [
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $outOpenStatusId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $outPendingApprovalStatusId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $outPendingApprovalIncomingStatusId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $outPendingReleaseStatusId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $outReleaseStatusId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $outClosedStatusId,
                'name' => 'institution_owner',
                'value' => '2'
            ]
        ];
        $this->insert('workflow_steps_params', $institutionOwner);

        $validateApprove = [
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $outPendingApprovalIncomingStatusId,
                'name' => 'validate_approve',
                'value' => '1'
            ]
        ];
        $this->insert('workflow_steps_params', $validateApprove);

        //labels to overwrite fields display
        $labels = [
            [
                'id' => Text::uuid(),
                'module' => 'StaffRelease',
                'field' => 'previous_institution_id',
                'module_name' => 'Institution -> Staff Release',
                'field_name' => 'Current Institution',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffRelease',
                'field' => 'previous_end_date',
                'module_name' => 'Institution -> Staff Release',
                'field_name' => 'Position End Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffRelease',
                'field' => 'comment',
                'module_name' => 'Institution -> Staff Release',
                'field_name' => 'Release Comments',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffReleaseIn',
                'field' => 'previous_institution_id',
                'module_name' => 'Institution -> Staff Release In',
                'field_name' => 'Current Institution',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffReleaseIn',
                'field' => 'new_FTE',
                'module_name' => 'Institution -> Staff Release In',
                'field_name' => 'FTE',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffReleaseIn',
                'field' => 'new_institution_position_id',
                'module_name' => 'Institution -> Staff Release In',
                'field_name' => 'Institution Position',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffReleaseIn',
                'field' => 'previous_start_date',
                'module_name' => 'Institution -> Staff Release In',
                'field_name' => 'Position Start Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffReleaseIn',
                'field' => 'previous_end_date',
                'module_name' => 'Institution -> Staff Release In',
                'field_name' => 'Position End Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffReleaseIn',
                'field' => 'new_staff_type_id',
                'module_name' => 'Institution -> Staff Release In',
                'field_name' => 'Staff Type',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffReleaseIn',
                'field' => 'new_start_date',
                'module_name' => 'Institution -> Staff Release In',
                'field_name' => 'Position Start Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffReleaseIn',
                'field' => 'new_end_date',
                'module_name' => 'Institution -> Staff Release In',
                'field_name' => 'Position End Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffReleaseIn',
                'field' => 'comment',
                'module_name' => 'Institution -> Staff Release In',
                'field_name' => 'Release Comments',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]

        ];
        $this->insert('labels', $labels);

        // update the security_table order
        $updateOrder = 'UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= 94';
        $this->execute($updateOrder);

        //insert into security_functions for staff release
        $seucrityFunctionsData = [
            [
                'id' => 1090,
                'name' => 'Staff Release',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Staff',
                'parent_id' => 8,
                '_view' => 'StaffReleaseIn.index|StaffReleaseIn.view|StaffReleaseIn.approve|StaffRelease.index|StaffRelease.view|StaffRelease.approve',
                '_edit' => 'StaffReleaseIn.edit|StaffRelease.edit',
                '_add' => 'StaffRelease.add',
                '_delete' => 'StaffReleaseIn.remove|StaffRelease.remove',
                'order' => 94,
                'visible' => 1,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('security_functions', $seucrityFunctionsData);
    }

    public function down()
    {
        //Restore backups
        $this->execute('DROP TABLE config_items');
        $this->table('z_4876_config_items')->rename('config_items');
        $this->execute('DROP TABLE workflow_models');
        $this->table('z_4876_workflow_models')->rename('workflow_models');
        $this->execute('DROP TABLE workflows');
        $this->table('z_4876_workflows')->rename('workflows');
        $this->execute('DROP TABLE workflow_steps');
        $this->table('z_4876_workflow_steps')->rename('workflow_steps');
        $this->execute('DROP TABLE `workflow_actions`');
        $this->table('z_4876_workflow_actions')->rename('workflow_actions');
        $this->execute('DROP TABLE `workflow_steps_params`');
        $this->table('z_4876_workflow_steps_params')->rename('workflow_steps_params');
        $this->execute('DROP TABLE `labels`');
        $this->table('z_4876_labels')->rename('labels');
        $this->execute('DROP TABLE `institution_staff_releases`');
        $this->execute('DROP TABLE `security_functions`');
        $this->table('z_4876_security_functions')->rename('security_functions');
    }
}