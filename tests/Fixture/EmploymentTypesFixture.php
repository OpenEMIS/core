<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class EmploymentTypesFixture extends TestFixture
{
    public $import = ['table' => 'employment_types'];
    public $records = [
        [
            "id" => "1",
            "name" => "Appointment",
            "order" => "1",
            "visible" => "1",
            "editable" => "1",
            "default" => "0",
            "international_code" => null,
            "national_code" => null,
            "modified_user_id" => null,
            "modified" => null,
            "created_user_id" => "1",
            "created" => "2013-12-19 10:08:15"
        ], [
            "id" => "2",
            "name" => "Probation",
            "order" => "2",
            "visible" => "1",
            "editable" => "1",
            "default" => "0",
            "international_code" => null,
            "national_code" => null,
            "modified_user_id" => null,
            "modified" => null,
            "created_user_id" => "1",
            "created" => "2013-12-19 10:08:15"
        ], [
            "id" => "3",
            "name" => "Contract",
            "order" => "3",
            "visible" => "1",
            "editable" => "1",
            "default" => "0",
            "international_code" => "",
            "national_code" => "",
            "modified_user_id" => null,
            "modified" => null,
            "created_user_id" => "1",
            "created" => "2013-12-19 10:08:15"
        ], [
            "id" => "4",
            "name" => "Temporary",
            "order" => "4",
            "visible" => "1",
            "editable" => "1",
            "default" => "0",
            "international_code" => "",
            "national_code" => "",
            "modified_user_id" => null,
            "modified" => null,
            "created_user_id" => "1",
            "created" => "2015-08-28 20:30:38"
        ]
    ];
}
