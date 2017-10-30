<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionStatusesFixture extends TestFixture
{
    public $import = ['table' => 'institution_statuses'];
    public $records = [
        [
<<<<<<< HEAD
            'id' => '1',
            'code' => 'ACTIVE',
            'name' => 'Active'
        ], [
            'id' => '2',
            'code' => 'INACTIVE',
            'name' => 'inactive'
=======
            'id' => 1,
            'code' => 'ACTIVE',
            'name' => 'Active'
        ],
        [
            'id' => 2,
            'code' => 'INACTIVE',
            'name' => 'Inactive'
>>>>>>> e5ceb1b29534f6f0a34f8818ea01afb71d70b1eb
        ]
    ];
}
