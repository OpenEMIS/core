<?php 
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AssessmentGradingTypesFixture extends TestFixture
{
    public $import = ['table' => 'assessment_grading_types'];
    public $records = [
        [
            'id' => 2,
            'code' => 'GradingType01',
            'name' => 'Marks Grading Scale',
            'pass_mark' => '50.00',
            'max' => 100.00,
            'result_type' => 'MARKS',
            'visible' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 5,
            'created' => '2017-01-07 00:00:00'
        ], [
            'id' => 6,
            'code' => 'GradingType02',
            'name' => 'Grading Type Two',
            'pass_mark' => 65.00,
            'max' => 80.00,
            'result_type' => 'MARKS',
            'visible' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 5,
            'created' => '2017-01-07 00:00:00'
        ], [
            'id' => 7,
            'code' => 'GradingType03',
            'name' => 'Grading Type Three',
            'pass_mark' => 80.00,
            'max' => 100.00,
            'result_type' => 'GRADES',
            'visible' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 5,
            'created' => '2017-01-07 00:00:00'
        ]
    ];
}