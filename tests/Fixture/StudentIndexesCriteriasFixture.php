<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StudentIndexesCriteriasFixture extends TestFixture
{
    public $import = ['table' => 'student_indexes_criterias'];
    public $records = [
        [
            'id' => '127',
            'value' => 'True',
            'institution_student_index_id' => '31',
            'indexes_criteria_id' => '13',
            'modified_user_id' => '1',
            'modified' => '2017-01-03 16:02:16',
            'created_user_id' => '2',
            'created' => '2017-01-03 16:02:16'
        ],
        [
            'id' => '128',
            'value' => 'True',
            'institution_student_index_id' => '32',
            'indexes_criteria_id' => '14',
            'modified_user_id' => '1',
            'modified' => '2017-01-03 16:01:58',
            'created_user_id' => '2',
            'created' => '2017-01-03 16:02:16'
        ],
        [
            'id' => '129',
            'value' => null,
            'institution_student_index_id' => '32',
            'indexes_criteria_id' => '17',
            'modified_user_id' => '1',
            'modified' => '2017-01-03 16:01:58',
            'created_user_id' => '2',
            'created' => '2017-01-03 16:02:16'
        ],
        [
            'id' => '130',
            'value' => null,
            'institution_student_index_id' => '33',
            'indexes_criteria_id' => '14',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2017-01-03 16:02:16'
        ],
        [
            'id' => '131',
            'value' => 'True',
            'institution_student_index_id' => '33',
            'indexes_criteria_id' => '17',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2017-01-03 16:02:16'
        ]
    ];
}
