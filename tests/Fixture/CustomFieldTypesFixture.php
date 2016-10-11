<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class CustomFieldTypesFixture extends TestFixture
{
    public $import = ['table' => 'custom_field_types'];
    public $records = [
        [
            "code" => "TEXT",
            "description" => "",
            "format" => "OpenEMIS",
            "id" => "1",
            "is_mandatory" => "1",
            "is_unique" => "1",
            "name" => "Text",
            "value" => "text_value",
            "visible" => "1"
        ], [
            "code" => "NUMBER",
            "description" => "",
            "format" => "OpenEMIS",
            "id" => "2",
            "is_mandatory" => "1",
            "is_unique" => "1",
            "name" => "Number",
            "value" => "number_value",
            "visible" => "1"
        ], [
            "code" => "TEXTAREA",
            "description" => "",
            "format" => "OpenEMIS",
            "id" => "3",
            "is_mandatory" => "1",
            "is_unique" => "0",
            "name" => "Textarea",
            "value" => "textarea_value",
            "visible" => "1"
        ], [
            "code" => "DROPDOWN",
            "description" => "",
            "format" => "OpenEMIS",
            "id" => "4",
            "is_mandatory" => "1",
            "is_unique" => "0",
            "name" => "Dropdown",
            "value" => "number_value",
            "visible" => "1"
        ], [
            "code" => "CHECKBOX",
            "description" => "",
            "format" => "OpenEMIS",
            "id" => "5",
            "is_mandatory" => "0",
            "is_unique" => "0",
            "name" => "Checkbox",
            "value" => "number_value",
            "visible" => "1"
        ], [
            "code" => "TABLE",
            "description" => "",
            "format" => "OpenEMIS",
            "id" => "6",
            "is_mandatory" => "0",
            "is_unique" => "0",
            "name" => "Table",
            "value" => "text_value",
            "visible" => "1"
        ], [
            "code" => "DATE",
            "description" => "",
            "format" => "OpenEMIS",
            "id" => "7",
            "is_mandatory" => "1",
            "is_unique" => "0",
            "name" => "Date",
            "value" => "date_value",
            "visible" => "1"
        ], [
            "code" => "TIME",
            "description" => "",
            "format" => "OpenEMIS",
            "id" => "8",
            "is_mandatory" => "1",
            "is_unique" => "0",
            "name" => "Time",
            "value" => "time_value",
            "visible" => "1"
        ], [
            "code" => "STUDENT_LIST",
            "description" => "",
            "format" => "OpenEMIS_Institution",
            "id" => "9",
            "is_mandatory" => "0",
            "is_unique" => "0",
            "name" => "Student List",
            "value" => "text_value",
            "visible" => "1"
        ], [
            "code" => "COORDINATES",
            "description" => "",
            "format" => "OpenEMIS",
            "id" => "10",
            "is_mandatory" => "1",
            "is_unique" => "0",
            "name" => "Coordinates",
            "value" => "text_value",
            "visible" => "1"
        ], [
            "code" => "FILE",
            "description" => "",
            "format" => "OpenEMIS",
            "id" => "11",
            "is_mandatory" => "0",
            "is_unique" => "0",
            "name" => "File",
            "value" => "file",
            "visible" => "1"
        ], [
            "code" => "REPEATER",
            "description" => "",
            "format" => "OpenEMIS_Institution",
            "id" => "12",
            "is_mandatory" => "0",
            "is_unique" => "0",
            "name" => "Repeater",
            "value" => "text_value",
            "visible" => "1"
        ]
    ];
}
