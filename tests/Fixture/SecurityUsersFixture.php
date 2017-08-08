<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SecurityUsersFixture extends TestFixture
{
    public $import = ['table' => 'security_users'];
    public $records = [
        [
            "id" => "1",
            "username" => "administrator",
            "password" => '$2y$10$wd3suBW4zs7dWNmj3cQwy.ojg5IIe6tkSP2mZFcgYrY0K/yFBhKgm',
            "openemis_no" => "sysadmin",
            "first_name" => "System",
            "middle_name" => null,
            "third_name" => null,
            "last_name" => "Administrator",
            "preferred_name" => null,
            "email" => null,
            "address" => null,
            "postal_code" => null,
            "address_area_id" => "51",
            "birthplace_area_id" => "51",
            "gender_id" => "1",
            "date_of_birth" => "2000-01-01",
            "date_of_death" => null,
            "nationality_id" => null,
            "identity_type_id" => null,
            "identity_number" => null,
            "external_reference" => null,
            "super_admin" => "1",
            "status" => "1",
            "last_login" => null,
            "photo_name" => null,
            "photo_content" => null,
            "is_student" => "0",
            "is_staff" => "0",
            "is_guardian" => "0",
            "modified_user_id" => null,
            "modified" => null,
            "created_user_id" => "1",
            "created" => "1970-01-01 00:00:00"
        ], [
            "id" => "2",
            "username" => "admin",
            "password" => '$2y$10$pDYCdZfAk2kn6k/CLgZvIeO1tYBtvGzUkRKL0NJg69edQKRSLLYeu',
            "openemis_no" => "sysadmin",
            "first_name" => "Administrator",
            "middle_name" => null,
            "third_name" => null,
            "last_name" => "Demo User",
            "preferred_name" => null,
            "email" => null,
            "address" => null,
            "postal_code" => null,
            "address_area_id" => "51",
            "birthplace_area_id" => "51",
            "gender_id" => "1",
            "date_of_birth" => "2000-01-01",
            "date_of_death" => null,
            "nationality_id" => null,
            "identity_type_id" => null,
            "identity_number" => null,
            "external_reference" => null,
            "super_admin" => "1",
            "status" => "1",
            "last_login" => null,
            "photo_name" => null,
            "photo_content" => null,
            "is_student" => "0",
            "is_staff" => "0",
            "is_guardian" => "0",
            "modified_user_id" => null,
            "modified" => null,
            "created_user_id" => "1",
            "created" => "1970-01-01 00:00:00"
        ]
    ];
}
