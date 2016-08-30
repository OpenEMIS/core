<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InfrastructureOwnershipsFixture extends TestFixture
{
    public $import = ['table' => 'infrastructure_ownerships'];
    public $records = [
        [
            'id' => 1,
            'name' => 'Denominational',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2015-08-28 17:20:38'
        ], [
            'id' => 2,
            'name' => 'Community',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2015-08-28 17:20:38'
        ], [
            'id' => 3,
            'name' => 'Private',
            'order' => '3',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2015-08-28 17:20:38'
        ], [
            'id' => 4,
            'name' => 'Government',
            'order' => '4',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2015-08-28 17:20:38'
        ]
    ];
}
