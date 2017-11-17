<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class WorkflowsFixture extends TestFixture
{
    public $import = ['table' => 'workflows'];
    public $records = [
        [
            'id' => '1',
            'code' => 'SURVEY-1001',
            'name' => 'Institutions - Survey - General',
            'workflow_model_id' => '2',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2015-10-25 12:10:14'
        ], [
            'id' => '2',
            'code' => 'TRN-1001',
            'name' => 'Training Courses',
            'workflow_model_id' => '3',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2015-11-06 11:27:06'
        ], [
            'id' => '3',
            'code' => 'TRN-2001',
            'name' => 'Training Sessions',
            'workflow_model_id' => '4',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2015-11-06 11:27:06'
        ], [
            'id' => '4',
            'code' => 'TRN-3001',
            'name' => 'Training Results',
            'workflow_model_id' => '5',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2015-11-06 11:27:06'
        ], [
            'id' => '5',
            'code' => 'TRN-4001',
            'name' => 'Training Needs',
            'workflow_model_id' => '6',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2015-12-15 15:41:55'
        ], [
            'id' => '6',
            'code' => 'POSITION-1001',
            'name' => 'Positions',
            'workflow_model_id' => '7',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2016-02-12 18:29:36'
        ], [
            'id' => '7',
            'code' => 'STAFF-POSITION-PROFILE-01',
            'name' => 'Staff Position Profile',
            'workflow_model_id' => '8',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2016-04-15 10:45:45'
        ], [
            'id' => '9',
            'code' => 'TRN-5001',
            'name' => 'Training Applications',
            'workflow_model_id' => '10',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2016-04-15 10:45:45'
        ], [
            'id' => '14',
            'code' => 'STAFF-TRANSFER-1001',
            'name' => 'Staff Transfer - Initiated By Incoming Institution',
            'workflow_model_id' => '13',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2017-10-13 18:10:04'
        ], [
            'id' => '15',
            'code' => 'STAFF-TRANSFER-2001',
            'name' => 'Staff Transfer - Initiated By Outgoing Institution',
            'workflow_model_id' => '14',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2017-10-13 18:10:04'
        ]
    ];
}
