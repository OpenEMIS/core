<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class UserIdentitiesFixture extends TestFixture
{
    public $import = ['table' => 'user_identities'];
    public $records = [
        [
            'id' => '2',
            'identity_type_id' => '163',
            'number' => '123',
            'issue_date' => '2016-09-20',
            'expiry_date' => '2016-09-21',
            'issue_location' => '',
            'comments' => '',
            'security_user_id' => '4',
            'modified_user_id' => '2',
            'modified' => '2016-09-21 02:34:49',
            'created_user_id' => '2',
            'created' => '2016-09-21 02:34:20'
        ],
        [
            'id' => '3',
            'identity_type_id' => '452',
            'number' => '312',
            'issue_date' => '2016-09-21',
            'expiry_date' => '2016-09-22',
            'issue_location' => '',
            'comments' => '',
            'security_user_id' => '1005',
            'modified_user_id' => '2',
            'modified' => '2016-09-21 05:51:58',
            'created_user_id' => '2',
            'created' => '2016-09-21 05:51:31'
        ],
        [
            'id' => '4',
            'identity_type_id' => '160',
            'number' => '4123',
            'issue_date' => '2016-09-21',
            'expiry_date' => '2016-09-22',
            'issue_location' => '',
            'comments' => '',
            'security_user_id' => '1039',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-09-21 06:20:57'
        ],
        [
            'id' => '5',
            'identity_type_id' => '161',
            'number' => '3r4tr',
            'issue_date' => '2016-09-21',
            'expiry_date' => '2016-09-22',
            'issue_location' => '',
            'comments' => '',
            'security_user_id' => '1039',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-09-21 06:22:23'
        ]
    ];
}

