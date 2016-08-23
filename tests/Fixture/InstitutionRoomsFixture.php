<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionRoomsFixture extends TestFixture
{
    // Optional. Set this property to load fixtures to a different test datasource
    // public $connection = 'test';

    public $import = ['table' => 'institution_rooms'];
    public $records = [
        [
            'id' => 1,
            'code' => 'ABS6653801010101',
            'name' => 'Room 1-A',
            'start_date' => '2016-01-02',
            'start_year' => '2016',
            'end_date' => '2016-12-31',
            'end_year' => '2016',
            'room_status_id' => 1,
            'institution_infrastructure_id' => 1,
            'institution_id' => 1,
            'academic_period_id' => 3,
            'room_type_id' => 1,
            'infrastructure_condition_id' => 1,
            'previous_room_id' => 0,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2016-08-17 09:34:01'
        ]
    ];
}