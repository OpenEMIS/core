<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SecurityRolesFixture extends TestFixture
{
    public $import = ['table' => 'security_roles'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Group Administrator',
            'code' => 'GROUP_ADMINISTRATOR',
            'order' => '3',
            'visible' => '1',
            'security_group_id' => '-1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-10-25 12:10:14'
        ], [
            'id' => '2',
            'name' => 'Administrator',
            'code' => 'ADMINISTRATOR',
            'order' => '2',
            'visible' => '1',
            'security_group_id' => '-1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-10-25 12:10:14'
        ], [
            'id' => '3',
            'name' => 'District Officer',
            'code' => 'DISTRICT_OFFICER',
            'order' => '4',
            'visible' => '1',
            'security_group_id' => '-1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-10-25 12:10:14'
        ], [
            'id' => '4',
            'name' => 'Principal',
            'code' => 'PRINCIPAL',
            'order' => '5',
            'visible' => '1',
            'security_group_id' => '-1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-10-25 12:10:14'
        ], [
            'id' => '5',
            'name' => 'Homeroom Teacher',
            'code' => 'HOMEROOM_TEACHER',
            'order' => '6',
            'visible' => '1',
            'security_group_id' => '-1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-10-25 12:10:14'
        ], [
            'id' => '6',
            'name' => 'Teacher',
            'code' => 'TEACHER',
            'order' => '7',
            'visible' => '1',
            'security_group_id' => '-1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-10-25 12:10:14'
        ], [
            'id' => '7',
            'name' => 'Staff',
            'code' => 'STAFF',
            'order' => '8',
            'visible' => '1',
            'security_group_id' => '-1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-10-25 12:10:14'
        ], [
            'id' => '8',
            'name' => 'Student',
            'code' => 'STUDENT',
            'order' => '10',
            'visible' => '1',
            'security_group_id' => '-1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-10-25 12:10:14'
        ], [
            'id' => '9',
            'name' => 'Guardian',
            'code' => 'GUARDIAN',
            'order' => '9',
            'visible' => '1',
            'security_group_id' => '-1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-10-25 12:10:14'
        ], [
            'id' => '10',
            'name' => 'Superrole',
            'code' => 'SUPERROLE',
            'order' => '1',
            'visible' => '1',
            'security_group_id' => '-1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-10-25 12:10:14'
        ], [
            'id' => '11',
            'name' => 'Managing Authority',
            'code' => 'MANAGING_AUTHORITY',
            'order' => '11',
            'visible' => '1',
            'security_group_id' => '-1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-10-25 12:10:14'
        ]
    ];
}

