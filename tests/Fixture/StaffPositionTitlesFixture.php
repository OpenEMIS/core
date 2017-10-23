<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StaffPositionTitlesFixture extends TestFixture
{
    public $import = ['table' => 'staff_position_titles'];
    public $records = [
        [
            'id' => '240',
            'name' => 'Principal',
            'type' => '0',
            'security_role_id' => '4',
            'order' => '10',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '01',
            'national_code' => '01',
            'modified_user_id' => '1',
            'modified' => '2015-07-23 03:08:52',
            'created_user_id' => '1',
            'created' => '2014-09-29 08:16:10'
        ], [
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
        ], [
            'id' => '465',
            'name' => 'Assistant Teacher',
            'type' => '1',
            'security_role_id' => '6',
            'order' => '10',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '09',
            'national_code' => '09',
            'modified_user_id' => '2',
            'modified' => '2015-08-23 17:35:47',
            'created_user_id' => '2',
            'created' => '2015-07-23 03:16:22'
        ], [
            'id' => '495',
            'name' => 'Lecturer',
            'type' => '1',
            'security_role_id' => '7',
            'order' => '39',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '39',
            'national_code' => '39',
            'modified_user_id' => '2',
            'modified' => '2015-08-23 17:45:22',
            'created_user_id' => '2',
            'created' => '2015-07-23 03:21:48'
        ]
    ];
}

