<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AreaAdministrativeLevelsFixture extends TestFixture
{
    public $import = ['table' => 'area_administrative_levels'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Country',
            'level' => '0',
            'area_administrative_id' => '8',
            'modified_user_id' => '0',
            'modified' => null,
            'created_user_id' => '0',
            'created' => '1970-01-01 00:00:00'
        ], [
            'id' => '2',
            'name' => 'Region',
            'level' => '2',
            'area_administrative_id' => '2',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-05-05 07:10:00'
        ], [
            'id' => '3',
            'name' => 'Zone',
            'level' => '3',
            'area_administrative_id' => '2',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-05-05 07:10:04'
        ], [
            'id' => '4',
            'name' => 'Country',
            'level' => '1',
            'area_administrative_id' => '2',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-05-05 07:09:54'
        ], [
            'id' => '5',
            'name' => 'Continent',
            'level' => '1',
            'area_administrative_id' => '1',
            'modified_user_id' => '2',
            'modified' => '2016-05-05 07:08:54',
            'created_user_id' => '0',
            'created' => '1970-01-01 00:00:00'
        ], [
            'id' => '6',
            'name' => 'World',
            'level' => '-1',
            'area_administrative_id' => '0',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '1970-01-01 00:00:00'
        ]
    ];
}
