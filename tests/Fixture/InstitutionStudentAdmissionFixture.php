<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionStudentAdmissionFixture extends TestFixture
{
    public $import = ['table' => 'institution_student_admission'];
    public $records = [
        [
            'id' => 105,
            'start_date' => '2017-01-01',
            'end_date' => '2017-12-31',
            'requested_date' =>  '2017-10-01',
            'student_id' => 5,
            'status' => 2,
            'institution_id' =>  1,
            'academic_period_id' => 26,
            'education_grade_id' => 74,
            'new_education_grade_id' => 74,
            'institution_class_id' => NULL,
            'previous_institution_id' => 476,
            'student_transfer_reason_id' => 575,
            'comment' =>
            '',
            'type' => 2,
            'modified_user_id' => 2,
            'modified' => '2017-10-20 07:50:35',
            'created_user_id' => 2,
            'created' => '2017-10-20 07:43:25'
        ],
        [
            'id' => 106,
            'start_date' => '2017-01-03',
            'end_date' => '2017-12-31',
            'requested_date' => '2017-10-01',
            'student_id' => 3,
            'status' => 0,
            'institution_id' =>  13,
            'academic_period_id' => 26,
            'education_grade_id' => 74,
            'new_education_grade_id' => 74,
            'institution_class_id' => NULL,
            'previous_institution_id' => 1,
            'student_transfer_reason_id' => 575,
            'comment' =>
            '',
            'type' => 2,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2017-10-23 03:37:47'
        ],
        [
            'id' => 107,
            'start_date' => '2017-01-01',
            'end_date' => '2017-12-31',
            'requested_date' =>  '2017-10-01',
            'student_id' => 7,
            'status' => '0',
            'institution_id' =>  13,
            'academic_period_id' => 26,
            'education_grade_id' => 74,
            'new_education_grade_id' => 74,
            'institution_class_id' => NULL,
            'previous_institution_id' => 1,
            'student_transfer_reason_id' => 575,
            'comment' =>
            '',
            'type' => 2,
            'modified_user_id' => 2,
            'modified' => '2017-10-20 07:50:35',
            'created_user_id' => 2,
            'created' => '2017-10-20 07:43:25'
        ],
    ];
}

