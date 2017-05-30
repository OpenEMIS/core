<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ExaminationGradingOptionsFixture extends TestFixture
{
    public $import = ['table' => 'examination_grading_options'];
    public $records = [
        [
            'id' => '1',
            'code' => 'Excellent',
            'name' => 'Excellent',
            'min' => '50.00',
            'max' => '100.00',
            'order' => '1',
            'visible' => '1',
            'examination_grading_type_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-10-25 10:59:13'
        ],
        [
            'id' => '2',
            'code' => 'Fail',
            'name' => 'Fail',
            'min' => '0.00',
            'max' => '49.00',
            'order' => '2',
            'visible' => '1',
            'examination_grading_type_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-10-25 10:59:13'
        ]
    ];
}
