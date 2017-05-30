<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InfrastructureCustomFieldValuesFixture extends TestFixture
{
    public $import = ['table' => 'infrastructure_custom_field_values'];
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
            'institution_infrastructure_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '0',
            'created' => '1970-01-01 00:00:00'
        ]
    ];
}
