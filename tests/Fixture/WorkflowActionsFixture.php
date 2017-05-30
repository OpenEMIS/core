<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class WorkflowActionsFixture extends TestFixture
{
    public $import = ['table' => 'workflow_actions'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Submit For Approval',
            'description' => NULL,
            'action' => '0',
            'visible' => '1',
            'comment_required' => '0',
            'allow_by_assignee' => '1',
            'event_key' => NULL,
            'workflow_step_id' => '66',
            'next_workflow_step_id' => '67',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2016-11-04 11:51:43'
        ], [
            'id' => '2',
            'name' => 'Approve',
            'description' => NULL,
            'action' => '0',
            'visible' => '1',
            'comment_required' => '0',
            'allow_by_assignee' => '0',
            'event_key' => NULL,
            'workflow_step_id' => '67',
            'next_workflow_step_id' => '68',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2016-11-04 11:51:43'
        ], [
            'id' => '3',
            'name' => 'Reject',
            'description' => NULL,
            'action' => '1',
            'visible' => '1',
            'comment_required' => '0',
            'allow_by_assignee' => '0',
            'event_key' => NULL,
            'workflow_step_id' => '67',
            'next_workflow_step_id' => '71',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2016-11-04 11:51:43'
        ], [
            'id' => '4',
            'name' => 'Approve',
            'description' => NULL,
            'action' => '0',
            'visible' => '1',
            'comment_required' => '0',
            'allow_by_assignee' => '0',
            'event_key' => 'Workflow.onAssignTrainingSession',
            'workflow_step_id' => '68',
            'next_workflow_step_id' => '70',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2016-11-04 11:51:43'
        ], [
            'id' => '5',
            'name' => 'Reject',
            'description' => NULL,
            'action' => '1',
            'visible' => '1',
            'comment_required' => '0',
            'allow_by_assignee' => '0',
            'event_key' => NULL,
            'workflow_step_id' => '68',
            'next_workflow_step_id' => '71',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2016-11-04 11:51:43'
        ], [
            'id' => '6',
            'name' => 'Withdraw From Training Session',
            'description' => NULL,
            'action' => NULL,
            'visible' => '1',
            'comment_required' => '0',
            'allow_by_assignee' => '1',
            'event_key' => 'Workflow.onWithdrawTrainingSession',
            'workflow_step_id' => '70',
            'next_workflow_step_id' => '69',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2016-11-04 11:51:43'
        ]
    ];
}

