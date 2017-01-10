<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StaffTypesFixture extends TestFixture
{
    public $import = ['table' => 'staff_types'];
    public $records = [
        [
            'id' => '3',
            'name' => 'Full-Time',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '1',
            'international_code' => '',
            'national_code' => 'FT',
            'modified_user_id' => '1',
            'modified' => '2015-07-10 19:36:55',
            'created_user_id' => '1',
            'created' => '2014-06-04 16:54:58'
        ], [
            'id' => '4',
            'name' => 'Part-Time',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => 'PT',
            'modified_user_id' => '1',
            'modified' => '2015-07-10 19:38:15',
            'created_user_id' => '1',
            'created' => '2014-06-04 17:09:17'
        ]
    ];
}

