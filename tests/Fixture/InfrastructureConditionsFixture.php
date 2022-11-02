<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InfrastructureConditionsFixture extends TestFixture
{
    public $import = ['table' => 'infrastructure_conditions'];
    public $records = [
        [
            'id' => '1',
            'name' => 'In Good Condition',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => '2',
            'modified' => '2015-08-28 17:20:38',
            'created_user_id' => '2',
            'created' => '2015-08-28 17:20:38'
        ], [
            'id' => '2',
            'name' => 'In Need Of Minor Repair',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => '2',
            'modified' => '2015-08-28 17:20:59',
            'created_user_id' => '2',
            'created' => '2015-08-28 17:20:59'
        ], [
            'id' => '3',
            'name' => 'In Need of Major Repair',
            'order' => '3',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => '2',
            'modified' => '2015-08-28 17:21:18',
            'created_user_id' => '2',
            'created' => '2015-08-28 17:21:18'
        ], [
            'id' => '4',
            'name' => 'Complete Rebuilding Necessary',
            'order' => '4',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => '2',
            'modified' => '2015-08-28 17:21:37',
            'created_user_id' => '2',
            'created' => '2015-08-28 17:21:37'
        ]
    ];
}
