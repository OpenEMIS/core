<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class RoomTypesFixture extends TestFixture
{
    public $import = ['table' => 'room_types'];
    public $records = [
        [
            'id' => 1,
            'name' => 'Classroom',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2015-04-26 09:05:34'
        ], [
            'id' => 2,
            'name' => 'Laboratory',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2015-04-26 09:05:34'
        ]
    ];
}
