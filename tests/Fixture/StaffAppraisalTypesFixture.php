<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StaffAppraisalTypesFixture extends TestFixture
{
    public $import = ['table' => 'staff_appraisal_types'];
    public $records = [
        [
            'code' => 'SELF',
            'id' => '2',
            'name' => 'Self'
        ],
        [
            'code' => 'SUPERVISOR',
            'id' => '3',
            'name' => 'Supervisor'
        ],
        [
            'code' => 'PEER',
            'id' => '4',
            'name' => 'Peer'
        ]
    ];
}

