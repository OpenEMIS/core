<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionStudentAbsencesFixture extends TestFixture
{
    public $import = ['table' => 'institution_student_absences'];
    public $records = [
        [
            'id' => 1,
            'start_date' => '2017-10-10',
            'end_date' => '2017-10-10',
            'full_day' => 1,
            'start_time' => NULL,
            'end_time' => NULL,
            'comment' => NULL,
            'student_id' => 4,
            'institution_id' => 13,
            'absence_type_id' => 2,
            'student_absence_reason_id' => 0,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2017-10-23 07:51:55'
        ],
        [
            'id' => 2,
            'start_date' => '2017-10-20',
            'end_date' => '2017-10-20',
            'full_day' => 1,
            'start_time' => NULL,
            'end_time' => NULL,
            'comment' => NULL,
            'student_id' => 7,
            'institution_id' => 13,
            'absence_type_id' => 2,
            'student_absence_reason_id' => 0,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => '2017-10-20 00:00:00'
        ]
    ];
}

