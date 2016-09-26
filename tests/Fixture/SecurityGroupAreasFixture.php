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
        ],
            'security_group_id' => '1',
            'area_id' => '2',
            'created_user_id' => '2',
            'created' => '0000-00-00 00:00:00'
        ],
        [
            'security_group_id' => '1',
            'area_id' => '13',
            'created_user_id' => '2',
            'created' => '0000-00-00 00:00:00'
        ],
        [
            'security_group_id' => '2',
            'area_id' => '2',
            'created_user_id' => '2',
            'created' => '0000-00-00 00:00:00'
        ],
        [
            'security_group_id' => '2',
            'area_id' => '13',
            'created_user_id' => '2',
            'created' => '0000-00-00 00:00:00'
        ],
        [
            'security_group_id' => '13',
            'area_id' => '13',
            'created_user_id' => '2',
            'created' => '0000-00-00 00:00:00'
        ],
        [
            'security_group_id' => '14',
            'area_id' => '1',
            'created_user_id' => '2',
            'created' => '0000-00-00 00:00:00'
        ],
        [
            'security_group_id' => '14',
            'area_id' => '13',
            'created_user_id' => '2',
            'created' => '0000-00-00 00:00:00'
        ]
    ];
}

