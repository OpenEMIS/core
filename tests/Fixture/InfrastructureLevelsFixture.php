<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InfrastructureLevelsFixture extends TestFixture
{
    public $import = ['table' => 'infrastructure_levels'];
    public $records = [
        [
            'id' => 1,
            'code' => 'LAND',
            'name' => 'Land',
            'description' => '',
            'editable' => '0',
            'parent_id' => NULL,
            'lft' => '1',
            'rght' => '8',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2015-08-28 17:20:38'
        ], [
            'id' => 2,
            'code' => 'BUILDING',
            'name' => 'Building',
            'description' => '',
            'editable' => '0',
            'parent_id' => NULL,
            'lft' => '2',
            'rght' => '7',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2015-08-28 17:20:38'
        ], [
            'id' => 3,
            'code' => 'FLOOR',
            'name' => 'floor',
            'description' => '',
            'editable' => '0',
            'parent_id' => NULL,
            'lft' => '3',
            'rght' => '6',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2015-08-28 17:20:38'
        ], [
            'id' => 4,
            'code' => 'ROOM',
            'name' => 'Room',
            'description' => '',
            'editable' => '0',
            'parent_id' => NULL,
            'lft' => '4',
            'rght' => '5',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
           'created' => '2015-08-28 17:20:38'
        ]
    ];
}
