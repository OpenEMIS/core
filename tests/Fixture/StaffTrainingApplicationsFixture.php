<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StaffTrainingApplicationsFixture extends TestFixture
{
    public $import = ['table' => 'staff_training_applications'];
    public $records = [
        [
            'id' => '1',
            'staff_id' => '3',
            'training_session_id' => '1',
            'status_id' => '36',
            'assignee_id' => '3',
            'institution_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-05-05 07:10:00'
        ], [
            'id' => '2',
            'staff_id' => '3',
            'training_session_id' => '3',
            'status_id' => '36',
            'assignee_id' => '4',
            'institution_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-05-05 07:10:00'
        ]
    ];
}