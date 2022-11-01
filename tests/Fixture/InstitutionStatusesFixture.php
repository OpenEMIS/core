<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionStatusesFixture extends TestFixture
{
    public $import = ['table' => 'institution_statuses'];
    public $records = [
        [
            'id' => '1',
            'code' => 'ACTIVE',
            'name' => 'Active'
        ], [
            'id' => '2',
            'code' => 'INACTIVE',
            'name' => 'inactive'
        ]
    ];
}
