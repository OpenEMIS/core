<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StaffPositionTitlesFixture extends TestFixture
{
    public $import = ['table' => 'staff_position_titles'];
    public $records = [
        [
            'id' => '242',
            'name' => 'Teacher',
            'type' => '1',
            'security_role_id' => '6',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '03',
            'national_code' => '03',
            'modified_user_id' => '1',
            'modified' => '2015-07-10 18:55:50',
            'created_user_id' => '1',
            'created' => '2014-09-29 08:16:36'
        ]
    ];
}

