<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ExaminationsFixture extends TestFixture
{
    public $import = ['table' => 'examinations'];
    public $records = [
        [
            'id' => 1,
            'code' => "Exam",
            'name' => "Exam",
            'description' => "Examination for 2016",
            'academic_period_id' => 25,
            'education_grade_id' => 77,
            'registration_start_date' => "2016-09-19",
            'registration_end_date' => "2016-09-20",
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => "2016-09-19 06:48:01"
        ],
        [
            'id' => 2,
            'code' => "New Exam",
            'name' => "New Exam",
            'description' => "This is a new examination",
            'academic_period_id' => 25,
            'education_grade_id' => 77,
            'registration_start_date' => "2016-09-20",
            'registration_end_date' => "2016-09-30",
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => "2016-09-20 07:48:30"
        ]
    ];
}