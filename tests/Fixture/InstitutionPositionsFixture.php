<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionPositionsFixture extends TestFixture
{
    public $import = ['table' => 'institution_positions'];
    public $records = [
        [
            'id' => '1',
            'status_id' => '29',
            'position_no' => 'Test123',
            'staff_position_title_id' => '242',
            'staff_position_grade_id' => '20',
            'institution_id' => '1',
            'assignee_id' => '0',
            'is_homeroom' => '0',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2017-06-16 09:08:00'
        ]
    ];
}

