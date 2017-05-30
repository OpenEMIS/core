<?php 
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AssessmentPeriodsFixture extends TestFixture
{
    public $import = ['table' => 'assessment_periods'];
    public $records = [
        [
            'id' => 1,
            'code' => 'AssessmentPeriod01',
            'name' => 'Assessment Period One',
            'start_date' => '2016-01-01',
            'end_date' => '2016-12-31',
            'date_enabled' => '2015-12-31',
            'date_disabled' => '2017-01-01',
            'weight' => 0.1,
            'assessment_id' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 5,
            'created' => '2017-01-07 00:00:00'
        ], [
            'id' => 2,
            'code' => 'AssessmentPeriod02',
            'name' => 'Assessment Period Two',
            'start_date' => '2016-01-01',
            'end_date' => '2016-12-31',
            'date_enabled' => '2015-12-31',
            'date_disabled' => '2017-01-01',
            'weight' => 0.2,
            'assessment_id' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 5,
            'created' => '2017-01-07 00:00:00'
        ], [
            'id' => 3,
            'code' => 'AssessmentPeriod03',
            'name' => 'Assessment Period Three',
            'start_date' => '2017-01-01',
            'end_date' => '2017-12-31',
            'date_enabled' => '2016-12-31',
            'date_disabled' => '2018-01-01',
            'weight' => 0.3,
            'assessment_id' => 2,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 5,
            'created' => '2017-01-07 00:00:00'
        ]
    ];
}