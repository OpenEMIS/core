<?php 
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AcademicPeriodsFixture extends TestFixture
{
    // Optional. Set this property to load fixtures to a different test datasource
    // public $connection = 'test';

    public $import = ['table' => 'academic_periods'];
    public $records = [
        [
            'id' => NULL,
            'code' => 'All',
            'name' => 'All Data Test',
            'start_date' => '0000-00-00',
            'start_year' => '0',
            'end_date' => NULL,
            'end_year' => NULL,
            'school_days' => '0',
            'current' => '0',
            'editable' => '1',
            'parent_id' => '0',
            'lft' => '1',
            'rght' => '1',
            'academic_period_level_id' => '-1',
            'order' => '1',
            'visible' => '1',
            'modified_user_id' => NULL,
            'modified' => '2015-05-21 11:22:05',
            'created_user_id' => '1',
            'created' => '0000-00-00 00:00:00'
        ],
        // testingId
        [
            'id' => NULL,
            'code' => 'YearDel',
            'name' => 'Year to be deleted',
            'start_date' => '0000-00-00',
            'start_year' => '0',
            'end_date' => NULL,
            'end_year' => NULL,
            'school_days' => '0',
            'current' => '0',
            'editable' => '1',
            'parent_id' => '0',
            // 'lft' => '1',
            // 'rght' => '46',
            'academic_period_level_id' => '1',
            'order' => '1',
            'visible' => '1',
            'modified_user_id' => NULL,
            'modified' => '2015-05-21 11:22:05',
            'created_user_id' => '1',
            'created' => '0000-00-00 00:00:00'
        ]
    ];
}