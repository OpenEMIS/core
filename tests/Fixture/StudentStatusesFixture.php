<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StudentStatusesFixture extends TestFixture
{
    public $import = ['table' => 'student_statuses'];
    public $records = [
        [
            'id' => 1,
            'code' => 'CURRENT',
            'name' => 'Enrolled'
        ], [
            'id' => 3,
            'code' => 'TRANSFERRED',
            'name' => 'Transferred'
        ], [
            'id' => 4,
            'code' => 'WITHDRAWN',
            'name' => 'Withdrawn'
        ], [
            'id' => 6,
            'code' => 'GRADUATED',
            'name' => 'Graduated'
        ], [
            'id' => 7,
            'code' => 'PROMOTED',
            'name' => 'Promoted'
        ], [
            'id' => 8,
            'code' => 'REPEATED',
            'name' => 'Repeated'
        ]
    ];
}
