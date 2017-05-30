<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StudentWithdrawReasonsFixture extends TestFixture
{
    public $import = ['table' => 'student_withdraw_reasons'];
    public $records = [
         [
            'id' => 649,
            'name' => 'Family Issues',
            'order' => 5,
            'visible' => 1,
            'editable' => 1,
            'default' => 0,
            'international_code' => NULL,
            'national_code' => NULL,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2015-01-01 00:00:00'
        ], [
            'id' => 660,
            'name' => 'Expelled',
            'order' => 3,
            'visible' => 1,
            'editable' => 1,
            'default' => 0,
            'international_code' => NULL,
            'national_code' => NULL,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2015-01-01 00:00:00'
        ], [
            'id' => 661,
            'name' => 'Financial Issues',
            'order' => 6,
            'visible' => 1,
            'editable' => 1,
            'default' => 0,
            'international_code' => NULL,
            'national_code' => NULL,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2015-01-01 00:00:00'
        ]
    ];
}

