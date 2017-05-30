<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class GendersFixture extends TestFixture
{
    public $import = ['table' => 'genders'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Male',
            'code' => 'M',
            'order' => '1',
            'created_user_id' => '1',
            'created' => '2015-04-09 02:46:40'
        ], [
            'id' => '2',
            'name' => 'Female',
            'code' => 'F',
            'order' => '2',
            'created_user_id' => '1',
            'created' => '2015-04-09 02:46:40'
        ]
    ];
}