<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionStudentWithdrawFixture extends TestFixture
{
    public $import = ['table' => 'institution_student_withdraw'];
    public $records = [
        [
            'id' => 1,
            'effective_date' => '2016-11-01',
            'student_id' => 7,
            'status' => 0,
            'institution_id' => 1,
            'academic_period_id' => 3,
            'education_grade_id' => 76,
            'student_withdraw_reason_id' => 661,
            'comment' => NULL,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2015-01-01 00:00:00'
        ]
    ];
}

