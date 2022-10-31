<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StaffStatusesFixture extends TestFixture
{
    public $import = ['table' => 'staff_statuses'];
    public $records = [
        [
            'id' => 1,
            'code' => 'ASSIGNED',
            'name' => 'Assigned'
        ], [
            'id' => 2,
            'code' => 'END_OF_ASSIGNMENT',
            'name' => 'End of Assignment'
        ]
    ];
}
