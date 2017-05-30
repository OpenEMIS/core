<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ExaminationGradingTypesFixture extends TestFixture
{
    public $import = ['table' => 'examination_grading_types'];
    public $records = [
        [
            'id' => '1',
            'code' => 'gradingtypes',
            'name' => 'Grading',
            'pass_mark' => '20.00',
            'max' => '100.00',
            'result_type' => 'MARKS',
            'visible' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-10-25 10:59:13'
        ]
    ];
}
