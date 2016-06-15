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
            'id' => 1,
            'code' => 'All',
            'name' => 'All Data Test',
            'start_date' => '0000-00-00',
            'start_year' => '0',
            'end_date' => NULL,
            'end_year' => NULL,
            'school_days' => '0',
            'current' => '0',
            'editable' => '0',
            'parent_id' => '0',
            'lft' => '1',
            'rght' => '6',
            'academic_period_level_id' => '-1',
            'order' => '1',
            'visible' => '1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-01-01 00:00:00'
        ],
        // testingId
        [
            'id' => 2,
            'code' => '2015',
            'name' => '2015',
            'start_date' => '2015-01-01',
            'start_year' => '2015',
            'end_date' => '2015-12-31',
            'end_year' => '2015',
            'school_days' => '360',
            'current' => '0',
            'editable' => '1',
            'parent_id' => '1',
            'lft' => '2',
            'rght' => '3',
            'academic_period_level_id' => '1',
            'order' => '1',
            'visible' => '1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-01-01 00:00:00'
        ],
        [
            'id' => 3,
            'code' => '2016',
            'name' => '2016',
            'start_date' => '2016-01-01',
            'start_year' => '2016',
            'end_date' => '2016-12-31',
            'end_year' => '2016',
            'school_days' => '360',
            'current' => '0',
            'editable' => '1',
            'parent_id' => '1',
            'lft' => '4',
            'rght' => '5',
            'academic_period_level_id' => '1',
            'order' => '1',
            'visible' => '1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-01-01 00:00:00'
        ],
    ];
}