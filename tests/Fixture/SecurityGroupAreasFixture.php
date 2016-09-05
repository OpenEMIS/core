<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SecurityGroupAreasFixture extends TestFixture
{
    public $import = ['table' => 'security_group_areas'];
    public $records = [
        [
            'security_group_id' => '517',
            'area_id' => '3',
            'created_user_id' => '2',
            'created' => '2016-06-03 04:01:55'
        ]
    ];
}

