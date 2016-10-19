<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InfrastructureCustomFieldsFixture extends TestFixture
{
    public $import = ['table' => 'infrastructure_custom_fields'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Text field',
            'field_type' => 'TEXT',
            'is_mandatory' => 0,
            'is_unique' => 0,
            'params' => null,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '1970-01-01 00:00:00'
        ]
    ];
}
