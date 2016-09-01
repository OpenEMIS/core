<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SecurityUsersFixture extends TestFixture
{
    public $import = ['table' => 'security_users'];
    public $records = [
        [
            "address" => null,
            "address_area_id" => "51",
            "birthplace_area_id" => "51",
            "created" => "0000-00-00 00:00:00",
            "created_user_id" => "0",
            "date_of_birth" => "2000-01-01",
            "date_of_death" => null,
            "first_name" => "System",
            "gender_id" => "1",
            "id" => "1",
            "is_guardian" => "0",
            "is_staff" => "0",
            "is_student" => "0",
            "last_login" => "2016-08-17 10:06:30",
            "last_name" => "Administrator",
            "middle_name" => null,
            "modified" => "2016-08-17 10:06:30",
            "modified_user_id" => null,
            "openemis_no" => "sysadmin",
            "password" => '$2y$10$wd3suBW4zs7dWNmj3cQwy.ojg5IIe6tkSP2mZFcgYrY0K/yFBhKgm',
            "photo_content" => null,
            "photo_name" => null,
            "postal_code" => null,
            "preferred_name" => null,
            "status" => "1",
            "super_admin" => "1",
            "third_name" => null,
            "username" => "administrator"
        ], [
            "address" => null,
            "address_area_id" => "163",
            "birthplace_area_id" => "163",
            "created" => "0000-00-00 00:00:00",
            "created_user_id" => "0",
            "date_of_birth" => "2000-01-01",
            "date_of_death" => null,
            "first_name" => "Administrator",
            "gender_id" => "1",
            "id" => "2",
            "is_guardian" => "0",
            "is_staff" => "0",
            "is_student" => "0",
            "last_login" => "2016-08-29 06:13:30",
            "last_name" => "Demo User",
            "middle_name" => null,
            "modified" => "2016-08-29 06:13:30",
            "modified_user_id" => null,
            "openemis_no" => "admin",
            "password" => '$2y$10$pDYCdZfAk2kn6k/CLgZvIeO1tYBtvGzUkRKL0NJg69edQKRSLLYeu',
            "photo_content" => null,
            "photo_name" => null,
            "postal_code" => null,
            "preferred_name" => null,
            "status" => "1",
            "super_admin" => "1",
            "third_name" => null,
            "username" => "admin"
        ], [
            "address" => null,
            "address_area_id" => "150",
            "birthplace_area_id" => "150",
            "created" => "0000-00-00 00:00:00",
            "created_user_id" => "0",
            "date_of_birth" => "2000-01-01",
            "date_of_death" => null,
            "first_name" => "District Officer",
            "gender_id" => "1",
            "id" => "3",
            "is_guardian" => "0",
            "is_staff" => "1",
            "is_student" => "0",
            "last_login" => "0000-00-00 00:00:00",
            "last_name" => "Demo User",
            "middle_name" => null,
            "modified" => "0000-00-00 00:00:05",
            "modified_user_id" => null,
            "openemis_no" => "districtofficer",
            "password" => '$2y$10$pDYCdZfAk2kn6k/CLgZvIeO1tYBtvGzUkRKL0NJg69edQKRSLLYeu',
            "photo_content" => null,
            "photo_name" => "",
            "postal_code" => null,
            "preferred_name" => null,
            "status" => "1",
            "super_admin" => "0",
            "third_name" => null,
            "username" => "distofficer"
        ], [
            "address" => null,
            "address_area_id" => "84",
            "birthplace_area_id" => "84",
            "created" => "0000-00-00 00:00:00",
            "created_user_id" => "0",
            "date_of_birth" => "2000-01-01",
            "date_of_death" => null,
            "first_name" => "Principal",
            "gender_id" => "1",
            "id" => "4",
            "is_guardian" => "0",
            "is_staff" => "1",
            "is_student" => "0",
            "last_login" => "2016-07-19 07:12:38",
            "last_name" => "Demo User",
            "middle_name" => null,
            "modified" => "2016-07-19 07:12:38",
            "modified_user_id" => null,
            "openemis_no" => "principal",
            "password" => '$2y$10$pDYCdZfAk2kn6k/CLgZvIeO1tYBtvGzUkRKL0NJg69edQKRSLLYeu',
            "photo_content" => null,
            "photo_name" => "",
            "postal_code" => null,
            "preferred_name" => null,
            "status" => "1",
            "super_admin" => "0",
            "third_name" => null,
            "username" => "principal"
        ], [
            "address" => "",
            "address_area_id" => "5",
            "birthplace_area_id" => "5",
            "created" => "0000-00-00 00:00:00",
            "created_user_id" => "0",
            "date_of_birth" => "2000-01-01",
            "date_of_death" => null,
            "first_name" => "Teacher",
            "gender_id" => "1",
            "id" => "5",
            "is_guardian" => "0",
            "is_staff" => "1",
            "is_student" => "0",
            "last_login" => "2016-07-19 07:30:41",
            "last_name" => "Demo User",
            "middle_name" => "",
            "modified" => "2016-08-22 09:44:35",
            "modified_user_id" => "2",
            "openemis_no" => "teacher",
            "password" => '$2y$10$pDYCdZfAk2kn6k/CLgZvIeO1tYBtvGzUkRKL0NJg69edQKRSLLYeu',
            "photo_content" => null,
            "photo_name" => null,
            "postal_code" => "",
            "preferred_name" => "",
            "status" => "1",
            "super_admin" => "0",
            "third_name" => "",
            "username" => "teacher"
        ], [
            "address" => null,
            "address_area_id" => "147",
            "birthplace_area_id" => "147",
            "created" => "0000-00-00 00:00:00",
            "created_user_id" => "0",
            "date_of_birth" => "2000-01-01",
            "date_of_death" => null,
            "first_name" => "Student",
            "gender_id" => "1",
            "id" => "6",
            "is_guardian" => "0",
            "is_staff" => "0",
            "is_student" => "1",
            "last_login" => "0000-00-00 00:00:00",
            "last_name" => "Demo User",
            "middle_name" => null,
            "modified" => "0000-00-00 00:00:05",
            "modified_user_id" => null,
            "openemis_no" => "student",
            "password" => '$2y$10$pDYCdZfAk2kn6k/CLgZvIeO1tYBtvGzUkRKL0NJg69edQKRSLLYeu',
            "photo_content" => null,
            "photo_name" => "",
            "postal_code" => null,
            "preferred_name" => null,
            "status" => "1",
            "super_admin" => "0",
            "third_name" => null,
            "username" => "student"
        ], [
            "address" => null,
            "address_area_id" => "147",
            "birthplace_area_id" => "147",
            "created" => "0000-00-00 00:00:00",
            "created_user_id" => "0",
            "date_of_birth" => "2003-01-01",
            "date_of_death" => null,
            "first_name" => "Student2",
            "gender_id" => "1",
            "id" => "7",
            "is_guardian" => "0",
            "is_staff" => "0",
            "is_student" => "1",
            "last_login" => "0000-00-00 00:00:00",
            "last_name" => "Demo User",
            "middle_name" => null,
            "modified" => "0000-00-00 00:00:05",
            "modified_user_id" => null,
            "openemis_no" => "student",
            "password" => '$2y$10$pDYCdZfAk2kn6k/CLgZvIeO1tYBtvGzUkRKL0NJg69edQKRSLLYeu',
            "photo_content" => null,
            "photo_name" => "",
            "postal_code" => null,
            "preferred_name" => null,
            "status" => "1",
            "super_admin" => "0",
            "third_name" => null,
            "username" => "student2"
        ]

    ];
}
