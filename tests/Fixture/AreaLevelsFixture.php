<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AreaLevelsFixture extends TestFixture
{
    public $import = ['table' => 'area_levels'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Country',
            'level' => '1',
            'modified_user_id' => '0',
            'modified' => null,
            'created_user_id' => '0',
            'created' => '1970-01-01 00:00:00'
        ],
        [
            'id' => '2',
            'name' => 'Region',
            'level' => '2',
            'modified_user_id' => '2',
            'modified' => '2016-04-27 08:35:34',
            'created_user_id' => '0',
            'created' => '1970-01-01 00:00:00'
        ],
        [
            'id' => '3',
            'name' => 'Zone',
            'level' => '3',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-05-05 06:18:16'
        ]
    ];
}
