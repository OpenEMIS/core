<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SecurityGroupAreasFixture extends TestFixture
{
    public $import = ['table' => 'security_group_areas'];
    public $records = [
        [
            'security_group_id' => '1',
            'area_id' => '2',
            'created_user_id' => '2',
            'created' => '1970-01-01 00:00:00'
        ],
        [
            'security_group_id' => '1',
            'area_id' => '13',
            'created_user_id' => '2',
            'created' => '1970-01-01 00:00:00'
        ],
        [
            'security_group_id' => '2',
            'area_id' => '2',
            'created_user_id' => '2',
            'created' => '1970-01-01 00:00:00'
        ],
        [
            'security_group_id' => '2',
            'area_id' => '13',
            'created_user_id' => '2',
            'created' => '1970-01-01 00:00:00'
        ],
        [
            'security_group_id' => '13',
            'area_id' => '13',
            'created_user_id' => '2',
            'created' => '1970-01-01 00:00:00'
        ],
        [
            'security_group_id' => '14',
            'area_id' => '1',
            'created_user_id' => '2',
            'created' => '1970-01-01 00:00:00'
        ],
        [
            'security_group_id' => '14',
            'area_id' => '13',
            'created_user_id' => '2',
            'created' => '1970-01-01 00:00:00'
        ]
    ];
}

