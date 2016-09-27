<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class RoomStatusesFixture extends TestFixture
{
    public $import = ['table' => 'room_statuses'];
    public $records = [
        [
            'id' => 1,
            'code' => 'IN_USE',
            'name' => 'In Use'
        ], [
            'id' => 2,
            'code' => 'END_OF_USAGE',
            'name' => 'End of Usage'
        ], [
            'id' => 3,
            'code' => 'CHANGE_IN_ROOM_TYPE',
            'name' => 'Change in Room Type'
        ],
    ];
}
