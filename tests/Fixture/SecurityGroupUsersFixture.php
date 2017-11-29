<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SecurityGroupUsersFixture extends TestFixture
{
    public $import = ['table' => 'security_group_users'];
    public $records = [
        //principals
        [
            'id' => '07bb92b8-cb32-4e6d-bd4c-8a48a411cba5',
            'security_group_id' => '1',
            'security_user_id' => '6',
            'security_role_id' => '4',
            'created_user_id' => '1',
            'created' => '2017-03-30 16:09:02'
        ], [
            'id' => '093d95dc-2442-4aeb-9971-16306c3d7612',
            'security_group_id' => '2',
            'security_user_id' => '7',
            'security_role_id' => '4',
            'created_user_id' => '1',
            'created' => '2017-03-30 16:09:02'
        ], [
            'id' => '1213d9d1-1f44-4da0-a2f6-5dc39b610b86',
            'security_group_id' => '2',
            'security_user_id' => '3',
            'security_role_id' => '6',
            'created_user_id' => '1',
            'created' => '2017-03-30 16:09:02'
        ], [
            'id' => '202cc02c-bfd9-4ecc-94d5-b143bd0a5ec4',
            'security_group_id' => '2',
            'security_user_id' => '4',
            'security_role_id' => '6',
            'created_user_id' => '1',
            'created' => '2017-03-30 16:09:02'
        ], [
            'id' => '933be20b-935e-4150-9b96-15927107d81b',
            'security_group_id' => '2',
            'security_user_id' => '5',
            'security_role_id' => '6',
            'created_user_id' => '1',
            'created' => '2017-03-30 16:09:02'
        ]
    ];
}

