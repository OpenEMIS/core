<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InfrastructureTypesFixture extends TestFixture
{
    public $import = ['table' => 'infrastructure_types'];
    public $records = [
        [
            'id' => 1,
            'name' => 'Educational',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'infrastructure_level_id' => '1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2015-08-28 17:20:38'
        ], [
            'id' => 2,
            'name' => 'General',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'infrastructure_level_id' => '2',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2015-08-28 17:20:38'
        ], [
            'id' => 3,
            'name' => 'Multipurpose',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'infrastructure_level_id' => '3',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2015-08-28 17:20:38'
        ]
    ];
}
