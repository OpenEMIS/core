<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionStaffFixture extends TestFixture
{
    public $import = ['table' => 'institution_staff'];
    public $records = [
        [
            'id' => '1',
            'FTE' => '1.00',
            'start_date' => '2003-06-15',
            'start_year' => '2003',
            'end_date' => NULL,
            'end_year' => NULL,
            'staff_id' => '3',
            'staff_type_id' => '3',
            'staff_status_id' => '1',
            'institution_id' => '1',
            'institution_position_id' => '1',
            'security_group_user_id' => 'f72db01a-7bf3-4603-bc61-0ee476a3bdf2',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2024-06-16 02:47:00'
        ]
    ];
}

