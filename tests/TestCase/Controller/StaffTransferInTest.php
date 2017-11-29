<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;

use App\Test\AppTestCase;
use App\Test\SystemFixturesTrait;

class StaffTransferInTest extends AppTestCase
{
    use SystemFixturesTrait;

    private $institutionId = 1;
    private $primaryKey = 1;

    public function __construct()
    {
        $this->fixtures[] = 'app.institutions';
        $this->fixtures[] = 'app.institution_staff_transfers';
        $this->fixtures[] = 'app.workflow_steps_roles';
        $this->fixtures[] = 'app.workflow_actions';
        $this->fixtures[] = 'app.workflow_comments';
        $this->fixtures[] = 'app.workflow_transitions';
        $this->fixtures[] = 'app.workflow_steps_params';
        $this->fixtures[] = 'app.institution_statuses';
        $this->fixtures[] = 'app.institution_staff';
        $this->fixtures[] = 'app.staff_statuses';
        $this->fixtures[] = 'app.institution_positions';
        $this->fixtures[] = 'app.staff_types';
        $this->fixtures[] = 'app.staff_position_titles';
        $this->fixtures[] = 'app.security_roles';
        $this->fixtures[] = 'app.security_group_users';
        $this->fixtures[] = 'app.security_groups';
        $this->fixtures[] = 'app.staff_position_grades';
        $this->fixtures[] = 'app.areas';
        $this->fixtures[] = 'app.alert_logs';
        $this->fixtures[] = 'app.institution_subject_staff';
        $this->fixtures[] = 'app.config_item_options';
        $this->fixtures[] = 'app.staff_custom_field_values';
        $this->fixtures[] = 'app.staff_custom_fields';
        $this->fixtures[] = 'app.staff_custom_forms_fields';

        parent::__construct();
    }

    public function setup()
    {
        parent::setUp();

        $this->InstitutionStaffTransfers = TableRegistry::get('Institution.InstitutionStaffTransfers');
        $this->encodedInstitutionId = $this->paramsEncode(['id' => $this->institutionId]);
    }

    public function testIndex()
    {
        $this->get("/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/index");

        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFound()
    {
        $url = "/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/index";

        $data = [
            'Search' => [
                'searchField' => 'Teacher Demo'
            ]
        ];
        $this->postData($url, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFound()
    {
        $url = "/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/index";

        $data = [
            'Search' => [
                'searchField' => '@#!@!cantFindThis!@#!'
            ]
        ];
        $this->postData($url, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) == 0));
    }

    public function testRead()
    {
        $id = $this->paramsEncode(['id' => $this->primaryKey]);
        $this->get("/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/view/$id");

        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));
    }

    public function testUpdate()
    {
        $id = $this->paramsEncode(['id' => $this->primaryKey]);
        $url = "/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/edit/$id";
        $this->get($url);

        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));

        $data = [
            'StaffTransferIn' => [
                'id' => $this->primaryKey,
                'staff_id' => '3',
                'institution_id' => $this->institutionId,
                'previous_institution_id' => '2',
                'status_id' => '43',
                'institution_position_id' => '5',
                'staff_type_id' => '4',
                'FTE' => '0.5',
                'start_date' => '2017-07-01',
                'end_date' => '2018-07-01',
                'previous_end_date' => '2017-05-31',
                'comment' => 'test update'
            ],
            'submit' => 'save'
        ];
        $this->postData($url, $data);

        $entity = $this->InstitutionStaffTransfers->get($data['StaffTransferIn']['id']);
        $this->assertEquals($data['StaffTransferIn']['institution_position_id'], $entity->institution_position_id);
        $this->assertEquals($data['StaffTransferIn']['staff_type_id'], $entity->staff_type_id);
        $this->assertEquals($data['StaffTransferIn']['FTE'], $entity->FTE);
        $this->assertEquals($data['StaffTransferIn']['start_date'], $entity->start_date->format('Y-m-d'));
        $this->assertEquals($data['StaffTransferIn']['end_date'], $entity->end_date->format('Y-m-d'));
        $this->assertEquals($data['StaffTransferIn']['comment'], $entity->comment);
    }

    public function testUpdateWithMissingFields()
    {
        $id = $this->paramsEncode(['id' => $this->primaryKey]);
        $url = "/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/edit/$id";

        $data = [
            'StaffTransferIn' => [
                'id' => $this->primaryKey,
                'staff_id' => '3',
                'institution_id' => $this->institutionId,
                'previous_institution_id' => '2',
                'status_id' => '43'
            ],
            'submit' => 'save'
        ];

        $this->postData($url, $data);
        $postData = $this->viewVariable('data');
        $errors = $postData->errors();

        $this->assertEquals(true, (array_key_exists('institution_position_id', $errors)));
        $this->assertEquals(true, (array_key_exists('staff_type_id', $errors)));
        $this->assertEquals(true, (array_key_exists('FTE', $errors)));
        $this->assertEquals(true, (array_key_exists('start_date', $errors)));
    }

    public function testUpdateWithWrongData()
    {
        $id = $this->paramsEncode(['id' => $this->primaryKey]);
        $url = "/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/edit/$id";

        $data = [
            'StaffTransferIn' => [
                'id' => $this->primaryKey,
                'staff_id' => '3',
                'institution_id' => $this->institutionId,
                'previous_institution_id' => '2',
                'status_id' => '43',
                'institution_position_id' => '5',
                'staff_type_id' => '4',
                'FTE' => '0.5',
                'start_date' => '2017-07-01',
                'end_date' => '2017-06-01', // end_date earlier than start_date
                'previous_end_date' => '2017-08-01' // previous_end_date later than start_date
            ],
            'submit' => 'save'
        ];

        $this->postData($url, $data);
        $postData = $this->viewVariable('data');
        $errors = $postData->errors();

        $this->assertEquals(true, (array_key_exists('start_date', $errors)));
        $this->assertEquals(true, (array_key_exists('ruleCompareDate', $errors['start_date'])));
        $this->assertEquals(true, (array_key_exists('ruleCompareDateReverse', $errors['start_date'])));
    }

    public function testDelete()
    {
        $id = $this->paramsEncode(['id' => $this->primaryKey]);
        $url = "/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/remove/$id";

        $exists = $this->InstitutionStaffTransfers->exists([$this->InstitutionStaffTransfers->primaryKey() => $this->primaryKey]);
        $this->assertTrue($exists);

        $this->deleteData($url);
        $exists = $this->InstitutionStaffTransfers->exists([$this->InstitutionStaffTransfers->primaryKey() => $this->primaryKey]);
        $this->assertFalse($exists);
    }

    public function testApproveFullTransferWorkflow()
    {
        // Open to Pending Approval
        $data = [
            'WorkflowTransitions' => [
                'prev_workflow_step_id' => '43',
                'prev_workflow_step_name' => 'Open',
                'workflow_step_id' => '44',
                'workflow_step_name' => 'Pending Approval',
                'workflow_action_id' => '7',
                'workflow_action_name' => 'Submit For Approval',
                'workflow_action_description' => '',
                'workflow_model_id' => '13',
                'model_reference' => '1',
                'assignee_required' => '1',
                'comment_required' => '0',
                'assignee_id' => '6',
                'comment' => null
            ]
        ];
        $this->postData("/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/processWorkflow", $data);
        $this->checkSuccessfulWorkflowTransition($data, false);

        // Pending Approval to Pending Approval From Outgoing Institution
        $data = [
            'WorkflowTransitions' => [
                'prev_workflow_step_id' => '44',
                'prev_workflow_step_name' => 'Pending Approval',
                'workflow_step_id' => '45',
                'workflow_step_name' => 'Pending Approval From Outgoing Institution',
                'workflow_action_id' => '8',
                'workflow_action_name' => 'Approve',
                'workflow_action_description' => '',
                'workflow_model_id' => '13',
                'model_reference' => '1',
                'assignee_required' => '1',
                'comment_required' => '0',
                'assignee_id' => '-1', // set assignee to -1 to autoAssign to role in other school
                'comment' => null
            ]
        ];
        $this->postData("/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/processWorkflow", $data);
        $this->checkSuccessfulWorkflowTransition($data, true);

        // Pending Approval From Outgoing Institution to Pending Staff Assignment
        $data = [
            'StaffTransferOut' => [
                'id' => $this->primaryKey,
                'staff_positions' => '4',
                'previous_end_date' => '2017-05-31',
                'comment' => 'test validate approve',
                'validate_approve' => '1',
                'transfer_type' => '1'
            ],
            'WorkflowTransitions' => [
                'prev_workflow_step_id' => '45',
                'prev_workflow_step_name' => 'Pending Approval From Outgoing Institution',
                'workflow_step_id' => '46',
                'workflow_step_name' => 'Pending Staff Assignment',
                'workflow_action_id' => '10',
                'workflow_action_name' => 'Approve',
                'workflow_action_description' => '',
                'workflow_model_id' => '13',
                'model_reference' => '1',
                'assignee_required' => '1',
                'comment_required' => '0',
                'assignee_id' => '-1',
                'comment' => null
            ],
            'submit' => 'save'
        ];

        $id = $this->paramsEncode(['id' => $this->primaryKey]);
        $url = "/Institution/Institutions/$this->encodedInstitutionId/StaffTransferOut/edit/$id";
        $this->postData($url, $data);

        $entity = $this->InstitutionStaffTransfers->get($data['StaffTransferOut']['id']);
        $this->assertEquals($data['StaffTransferOut']['staff_positions'], $entity->institution_staff_id);
        $this->assertEquals($data['StaffTransferOut']['previous_end_date'], $entity->previous_end_date->format('Y-m-d'));
        $this->assertEquals($data['StaffTransferOut']['comment'], $entity->comment);

        $this->checkSuccessfulWorkflowTransition($data, true);

        // Pending Approval to Assigned
        $data = [
            'WorkflowTransitions' => [
                'prev_workflow_step_id' => '46',
                'prev_workflow_step_name' => 'Pending Staff Assignment',
                'workflow_step_id' => '47',
                'workflow_step_name' => 'Assigned',
                'workflow_action_id' => '12',
                'workflow_action_name' => 'Assign',
                'workflow_action_description' => '',
                'workflow_model_id' => '13',
                'model_reference' => '1',
                'assignee_required' => '1',
                'comment_required' => '0',
                'assignee_id' => '6',
                'comment' => null
            ]
        ];
        $this->postData("/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/processWorkflow", $data);
        $this->checkSuccessfulWorkflowTransition($data, false);

        // check staff successfully transferred
        $StaffTable = TableRegistry::get('Institution.Staff');
        $StaffStatusesTable = TableRegistry::get('Staff.StaffStatuses');
        $SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
        $transferEntity = $this->InstitutionStaffTransfers->get($this->primaryKey);

        $oldStaffRecord = $StaffTable->get($transferEntity->institution_staff_id);
        $this->assertEquals($oldStaffRecord->staff_status_id, $StaffStatusesTable->getIdByCode('END_OF_ASSIGNMENT'));
        $this->assertEquals($oldStaffRecord->end_date->format('Y-m-d'), $transferEntity->previous_end_date->format('Y-m-d'));
        $this->assertEquals($oldStaffRecord->end_year, $transferEntity->previous_end_date->year);

        // check staff roles removed
        $oldSecurityGroupUsersRecord = $SecurityGroupUsersTable->find()
            ->where([
                $SecurityGroupUsersTable->aliasField('security_group_id') => '2',
                $SecurityGroupUsersTable->aliasField('security_user_id') => '3',
            ])
            ->first();
        $this->assertEquals(true, (empty($oldSecurityGroupUsersRecord)));

        $newStaffRecord = $StaffTable->find()
            ->where([
                $StaffTable->aliasField('start_date') => $transferEntity->start_date->format('Y-m-d'),
                $StaffTable->aliasField('start_year') => $transferEntity->start_date->year,
                $StaffTable->aliasField('staff_id') => $transferEntity->staff_id,
                $StaffTable->aliasField('staff_type_id') => $transferEntity->staff_type_id,
                $StaffTable->aliasField('staff_status_id') => $StaffStatusesTable->getIdByCode('ASSIGNED'),
                $StaffTable->aliasField('institution_id') => $transferEntity->institution_id,
                $StaffTable->aliasField('institution_position_id') => $transferEntity->institution_position_id,
                $StaffTable->aliasField('FTE') => $transferEntity->FTE
            ])
            ->first();
        $this->assertEquals(true, (!empty($newStaffRecord)));

        // check staff roles added
        $newSecurityGroupUsersRecord = $SecurityGroupUsersTable->find()
            ->where([
                $SecurityGroupUsersTable->aliasField('security_group_id') => '1',
                $SecurityGroupUsersTable->aliasField('security_user_id') => '3',
            ])
            ->first();
        $this->assertEquals(true, (!empty($newSecurityGroupUsersRecord)));
    }

    public function testRejectWorkflow()
    {
        // Open to Pending Approval
        $data = [
            'WorkflowTransitions' => [
                'prev_workflow_step_id' => '43',
                'prev_workflow_step_name' => 'Open',
                'workflow_step_id' => '44',
                'workflow_step_name' => 'Pending Approval',
                'workflow_action_id' => '7',
                'workflow_action_name' => 'Submit For Approval',
                'workflow_action_description' => '',
                'workflow_model_id' => '13',
                'model_reference' => '1',
                'assignee_required' => '1',
                'comment_required' => '0',
                'assignee_id' => '6',
                'comment' => null
            ]
        ];
        $this->postData("/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/processWorkflow", $data);
        $this->checkSuccessfulWorkflowTransition($data, false);

        // Pending Approval to Pending Approval From Outgoing Institution
        $data = [
            'WorkflowTransitions' => [
                'prev_workflow_step_id' => '44',
                'prev_workflow_step_name' => 'Pending Approval',
                'workflow_step_id' => '45',
                'workflow_step_name' => 'Pending Approval From Outgoing Institution',
                'workflow_action_id' => '8',
                'workflow_action_name' => 'Approve',
                'workflow_action_description' => '',
                'workflow_model_id' => '13',
                'model_reference' => '1',
                'assignee_required' => '1',
                'comment_required' => '0',
                'assignee_id' => '-1', // set assignee to -1 to autoAssign to role in other school
                'comment' => null
            ]
        ];
        $this->postData("/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/processWorkflow", $data);
        $this->checkSuccessfulWorkflowTransition($data, true);

        // Pending Approval From Outgoing Institution to Pending Staff Assignment
        $data = [
            'StaffTransferOut' => [
                'id' => $this->primaryKey,
                'staff_positions' => '4',
                'previous_end_date' => '2017-05-31',
                'comment' => 'test validate approve',
                'validate_approve' => '1',
                'transfer_type' => '1'
            ],
            'WorkflowTransitions' => [
                'prev_workflow_step_id' => '45',
                'prev_workflow_step_name' => 'Pending Approval From Outgoing Institution',
                'workflow_step_id' => '46',
                'workflow_step_name' => 'Pending Staff Assignment',
                'workflow_action_id' => '10',
                'workflow_action_name' => 'Approve',
                'workflow_action_description' => '',
                'workflow_model_id' => '13',
                'model_reference' => '1',
                'assignee_required' => '1',
                'comment_required' => '0',
                'assignee_id' => '-1',
                'comment' => null
            ],
            'submit' => 'save'
        ];

        $id = $this->paramsEncode(['id' => $this->primaryKey]);
        $url = "/Institution/Institutions/$this->encodedInstitutionId/StaffTransferOut/edit/$id";
        $this->postData($url, $data);

        $entity = $this->InstitutionStaffTransfers->get($data['StaffTransferOut']['id']);
        $this->assertEquals($data['StaffTransferOut']['staff_positions'], $entity->institution_staff_id);
        $this->assertEquals($data['StaffTransferOut']['previous_end_date'], $entity->previous_end_date->format('Y-m-d'));
        $this->assertEquals($data['StaffTransferOut']['comment'], $entity->comment);

        $this->checkSuccessfulWorkflowTransition($data, true);

        // Pending Approval to Rejected
        $data = [
            'WorkflowTransitions' => [
                'prev_workflow_step_id' => '46',
                'prev_workflow_step_name' => 'Pending Staff Assignment',
                'workflow_step_id' => '48',
                'workflow_step_name' => 'Rejected',
                'workflow_action_id' => '13',
                'workflow_action_name' => 'Reject',
                'workflow_action_description' => '',
                'workflow_model_id' => '13',
                'model_reference' => '1',
                'assignee_required' => '1',
                'comment_required' => '1',
                'assignee_id' => '6',
                'comment' => 'Test'
            ]
        ];
        $this->postData("/Institution/Institutions/$this->encodedInstitutionId/StaffTransferIn/processWorkflow", $data);
        $this->checkSuccessfulWorkflowTransition($data, false);

        // check staff not transferred
        $StaffTable = TableRegistry::get('Institution.Staff');
        $StaffStatusesTable = TableRegistry::get('Staff.StaffStatuses');
        $SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
        $transferEntity = $this->InstitutionStaffTransfers->get($this->primaryKey);

        $oldStaffRecord = $StaffTable->get($transferEntity->institution_staff_id);
        $this->assertEquals($oldStaffRecord->staff_status_id, $StaffStatusesTable->getIdByCode('ASSIGNED'));
        $this->assertEquals($oldStaffRecord->end_date, null);
        $this->assertEquals($oldStaffRecord->end_year, null);

        $oldSecurityGroupUsersRecord = $SecurityGroupUsersTable->find()
            ->where([
                $SecurityGroupUsersTable->aliasField('security_group_id') => '2',
                $SecurityGroupUsersTable->aliasField('security_user_id') => '3',
            ])
            ->first();
        $this->assertEquals(true, (!empty($oldSecurityGroupUsersRecord)));

        // check new record not added
        $newStaffRecord = $StaffTable->find()
            ->where([
                $StaffTable->aliasField('start_date') => $transferEntity->start_date->format('Y-m-d'),
                $StaffTable->aliasField('start_year') => $transferEntity->start_date->year,
                $StaffTable->aliasField('staff_id') => $transferEntity->staff_id,
                $StaffTable->aliasField('staff_type_id') => $transferEntity->staff_type_id,
                $StaffTable->aliasField('staff_status_id') => $StaffStatusesTable->getIdByCode('ASSIGNED'),
                $StaffTable->aliasField('institution_id') => $transferEntity->institution_id,
                $StaffTable->aliasField('institution_position_id') => $transferEntity->institution_position_id,
                $StaffTable->aliasField('FTE') => $transferEntity->FTE
            ])
            ->first();
        $this->assertEquals(true, (empty($newStaffRecord)));

        $newSecurityGroupUsersRecord = $SecurityGroupUsersTable->find()
            ->where([
                $SecurityGroupUsersTable->aliasField('security_group_id') => '1',
                $SecurityGroupUsersTable->aliasField('security_user_id') => '3',
            ])
            ->first();
        $this->assertEquals(true, (empty($newSecurityGroupUsersRecord)));
    }

    private function checkSuccessfulWorkflowTransition($data, $autoAssign = false)
    {
        // check updated status_id and assignee_id in StaffTransferIn table
        $entity = $this->InstitutionStaffTransfers->get($this->primaryKey);
        $this->assertEquals($data['WorkflowTransitions']['workflow_step_id'], $entity->status_id);

        if ($autoAssign) {
            $this->assertNotEquals($data['WorkflowTransitions']['assignee_id'], $entity->assignee_id);
        } else {
            $this->assertEquals($data['WorkflowTransitions']['assignee_id'], $entity->assignee_id);
        }

        // check workflow transitions record inserted
        $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');
        $transitionEntity = $WorkflowTransitions->find()
            ->where([
                $WorkflowTransitions->aliasField('prev_workflow_step_name') => $data['WorkflowTransitions']['prev_workflow_step_name'],
                $WorkflowTransitions->aliasField('workflow_step_name') => $data['WorkflowTransitions']['workflow_step_name'],
                $WorkflowTransitions->aliasField('workflow_action_name') => $data['WorkflowTransitions']['workflow_action_name'],
                $WorkflowTransitions->aliasField('model_reference') => $data['WorkflowTransitions']['model_reference'],
                $WorkflowTransitions->aliasField('workflow_model_id') => $data['WorkflowTransitions']['workflow_model_id']
            ])
            ->first();
        $this->assertEquals(true, (!empty($transitionEntity)));
    }
}
