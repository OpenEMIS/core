<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class CustomModulesFixture extends TestFixture
{
    // Optional. Set this property to load fixtures to a different test datasource
    // public $connection = 'test';

    public $import = ['table' => 'custom_modules'];
    public $records = [
      [
        'id' => '1',
        'code' => 'Institution',
        'name' => 'Institution > Overview',
        'model' => 'Institution.Institutions',
        'visible' => '1',
        'parent_id' => '0',
        'modified_user_id' => null,
        'modified' => null,
        'created_user_id' => '1',
        'created' => '1970-01-01 00:00:00'
      ],
      [
        'id' => '2',
        'code' => 'Student',
        'name' => 'Student > Overview',
        'model' => 'Student.Students',
        'visible' => '1',
        'parent_id' => '0',
        'modified_user_id' => null,
        'modified' => null,
        'created_user_id' => '1',
        'created' => '1970-01-01 00:00:00'
      ],
      [
        'id' => '3',
        'code' => 'Staff',
        'name' => 'Staff > Overview',
        'model' => 'Staff.Staff',
        'visible' => '1',
        'parent_id' => '0',
        'modified_user_id' => null,
        'modified' => null,
        'created_user_id' => '1',
        'created' => '1970-01-01 00:00:00'
      ],
      [
        'id' => '4',
        'code' => 'Infrastructure',
        'name' => 'Institution - Infrastructure',
        'model' => 'Institution.InstitutionInfrastructures',
        'visible' => '1',
        'parent_id' => '1',
        'modified_user_id' => null,
        'modified' => null,
        'created_user_id' => '1',
        'created' => '1970-01-01 00:00:00'
      ],
      [
        'id' => '5',
        'code' => 'Institution > Students',
        'name' => 'Institution > Students > Survey',
        'model' => 'Student.StudentSurveys',
        'visible' => '1',
        'parent_id' => '0',
        'modified_user_id' => null,
        'modified' => null,
        'created_user_id' => '1',
        'created' => '1970-01-01 00:00:00'
      ],
      [
        'id' => '6',
        'code' => 'Institution > Repeater',
        'name' => 'Institution > Repeater > Survey',
        'model' => 'InstitutionRepeater.RepeaterSurveys',
        'visible' => '1',
        'parent_id' => '0',
        'modified_user_id' => null,
        'modified' => null,
        'created_user_id' => '1',
        'created' => '1970-01-01 00:00:00'
      ],
      [
        'id' => '7',
        'code' => 'Room',
        'name' => 'Institution > Room',
        'model' => 'Institution.InstitutionRooms',
        'visible' => '1',
        'parent_id' => '1',
        'modified_user_id' => null,
        'modified' => null,
        'created_user_id' => '1',
        'created' => '1970-01-01 00:00:00'
      ]
    ];
}