<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AssessmentsFixture extends TestFixture
{
    public $import = ['table' => 'assessments'];
    public $records = [
        [
            'id' => 1,
            'code' => 'Assessment01',
            'name' => 'Assessment One',
            'description' => 'Assessment One Desc',
            'type' => 2,
            'academic_period_id' => 25,
            'education_grade_id' => 59,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 5,
            'created' => '2017-01-07 00:00:00'
        ], [
            'id' => 2,
            'code' => 'Assessment02',
            'name' => 'Assessment Two',
            'description' => 'Assessment Two Desc',
            'type' => 2,
            'academic_period_id' => 26,
            'education_grade_id' => 60,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2017-01-07 00:00:00'
        ]
    ];
}