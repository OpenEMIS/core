<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionStudentsFixture extends TestFixture
{
    public $import = ['table' => 'institution_students'];
    public $records = [
        [
            'id' => 1,
            'student_status_id' => 7,
            'student_id' => 6,
            'education_grade_id' => 76,
            'academic_period_id' => 2,
            'start_date' => '2015-01-01',
            'start_year' => '2015',
            'end_date' => '2015-12-31',
            'end_year' => '2015',
            'institution_id' => 1,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => 1,
            'created' => '2015-01-01 00:00:00'
        ],
        [
            'id' => 2,
            'student_status_id' => 1,
            'student_id' => 6,
            'education_grade_id' => 77,
            'academic_period_id' => 3,
            'start_date' => '2016-01-01',
            'start_year' => '2016',
            'end_date' => '2016-12-31',
            'end_year' => '2016',
            'institution_id' => 1,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => 1,
            'created' => '2016-01-01 00:00:00'
        ],
        [
            'id' => '3333',
            'student_status_id' => 1,
            'student_id' => 3,
            'education_grade_id' => 74,
            'academic_period_id' => 26,
            'start_date' => '2017-01-01',
            'start_year' => '2017',
            'end_date' => '2017-12-31',
            'end_year' => '2017',
            'institution_id' => 1,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => 1,
            'created' => '2017-01-01 00:00:00'
        ],
        [
            'id' => '4444',
            'student_status_id' => 1,
            'student_id' => 4,
            'education_grade_id' => 74,
            'academic_period_id' => 26,
            'start_date' => '2017-01-01',
            'start_year' => '2017',
            'end_date' => '2017-12-31',
            'end_year' => '2017',
            'institution_id' => 1,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => 1,
            'created' => '2017-01-01 00:00:00'
        ],
    ];
}

