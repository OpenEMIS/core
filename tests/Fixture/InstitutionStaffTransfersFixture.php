<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionStaffTransfersFixture extends TestFixture
{
    public $import = ['table' => 'institution_staff_transfers'];
    public $records = [
        [
            'id' => '1',
            'staff_id' => '3',
            'institution_id' => '1',
            'previous_institution_id' => '2',
            'status_id' => '43',
            'assignee_id' => '3',
            'institution_position_id' => '4',
            'staff_type_id' => '3',
            'FTE' => '0.75',
            'start_date' => '2017-06-01',
            'end_date' => null,
            'institution_staff_id' => null,
            'previous_end_date' => null,
            'comment' => null,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2017-10-23 09:32:36'
        ],
        [
            'id' => '2',
            'staff_id' => '4',
            'institution_id' => '1',
            'previous_institution_id' => '2',
            'status_id' => '49',
            'assignee_id' => '4',
            'institution_position_id' => null,
            'staff_type_id' => null,
            'FTE' => null,
            'start_date' => null,
            'end_date' => null,
            'institution_staff_id' => '5',
            'previous_end_date' => '2017-10-01',
            'comment' => null,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2017-10-23 09:32:36'
        ]
    ];
}

