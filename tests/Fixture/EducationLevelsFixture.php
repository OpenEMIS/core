<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class EducationLevelsFixture extends TestFixture
{
    public $import = ['table' => 'education_levels'];
    public $records = [
        [
            'id' => 1,
            'name' => 'Primary',
            'order' => 2,
            'visible' => 1,
            'education_system_id' => 1,
            'education_level_isced_id' => 2,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2015-01-01 00:00:00'
        ], [
            'id' => 2,
            'name' => 'Secondary',
            'order' => 3,
            'visible' => 1,
            'education_system_id' => 1,
            'education_level_isced_id' => 3,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2015-01-01 00:00:00'
        ], [
            'id' => 4,
            'name' => 'Pre-School Education',
            'order' => 1,
            'visible' => 1,
            'education_system_id' => 2,
            'education_level_isced_id' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2015-09-01 00:00:00'
        ]
    ];
}