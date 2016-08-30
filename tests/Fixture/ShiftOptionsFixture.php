<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ShiftOptionsFixture extends TestFixture
{
    public $import = ['table' => 'shift_options'];
    public $records = [
        [
            'id' => '1',
            'name' => 'First Shift',
            'start_time' => '07:00:00',
            'end_time' => '11:00:00',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => null,
            'national_code' => null,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2016-06-21 00:00:00'
        ], [
            'id' => '2',
            'name' => 'Second Shift',
            'start_time' => '11:00:00',
            'end_time' => '15:00:00',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => null,
            'national_code' => null,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2016-06-21 00:00:00'
        ], [
            'id' => '3',
            'name' => 'Third Shift',
            'start_time' => '15:00:00',
            'end_time' => '19:00:00',
            'order' => '3',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => null,
            'national_code' => null,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2016-06-21 00:00:00'
        ], [
            'id' => '4',
            'name' => 'Fourth Shift',
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'order' => '4',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => null,
            'national_code' => null,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '2016-06-21 00:00:00'
        ]
    ];
}
