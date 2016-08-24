<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class CustomFieldValuesFixture extends TestFixture
{
    // Optional. Set this property to load fixtures to a different test datasource
    // public $connection = 'test';

    public $import = ['table' => 'custom_field_values'];
    public $records = [
        [
            'id' => 'cfdf6d59-69c1-11e6-b9f3-525400b263eb',
            'text_value' => null,
            'number_value' => null,
            'textarea_value' => null,
            'date_value' => null,
            'time_value' => null,
            'file' => null,
            'custom_field_id' => '1',
            'custom_record_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '0',
            'created' => '0000-00-00 00:00:00'
        ]
    ];
}