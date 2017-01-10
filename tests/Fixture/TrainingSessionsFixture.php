<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TrainingSessionsFixture extends TestFixture
{
    public $import = ['table' => 'training_sessions'];
    public $records = [
        [
            'id' => '1',
            'code' => '01',
            'name' => 'Tuesday PM',
            'start_date' => '2016-10-28',
            'end_date' => '2016-10-28',
            'comment' => '',
            'training_course_id' => '1',
            'training_provider_id' => '1',
            'assignee_id' => '2',
            'status_id' => '15',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '2',
            'created' => '2016-10-28 10:53:27'
        ], [
            'id' => '2',
            'code' => '02',
            'name' => 'Wednesday PM',
            'start_date' => '2016-11-02',
            'end_date' => '2016-11-26',
            'comment' => '',
            'training_course_id' => '1',
            'training_provider_id' => '1',
            'assignee_id' => '2',
            'status_id' => '15',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '2',
            'created' => '2016-11-02 13:27:10'
        ], [
            'id' => '3',
            'code' => '03',
            'name' => 'Mondays',
            'start_date' => '2016-11-02',
            'end_date' => '2016-11-26',
            'comment' => '',
            'training_course_id' => '2',
            'training_provider_id' => '1',
            'assignee_id' => '2',
            'status_id' => '15',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '2',
            'created' => '2016-11-02 13:27:10'
        ]
    ];
}

