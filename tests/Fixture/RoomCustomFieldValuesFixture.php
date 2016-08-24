<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class RoomCustomFieldValuesFixture extends TestFixture
{
    // Optional. Set this property to load fixtures to a different test datasource
    // public $connection = 'test';

    public $import = ['table' => 'room_custom_field_values'];
    public $records = [
        [
            'id' => '7a0f083a-69bd-11e6-b9f3-525400b263eb',
            'text_value' => 'test_value',
            'number_value' => null,
            'textarea_value' => null,
            'date_value' => null,
            'time_value' => null,
            'file' => null,
            'infrastructure_custom_field_id' => '1',
            'institution_room_id' => '5',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '0',
            'created' => '0000-00-00 00:00:00'
        ]
    ];
}