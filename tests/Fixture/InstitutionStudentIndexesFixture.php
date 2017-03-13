<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionStudentIndexesFixture extends TestFixture
{
    public $import = ['table' => 'institution_student_indexes'];
    public $records = [
        [
            'id' => '32',
            'average_index' => '0.00',
            'total_index' => '30',
            'academic_period_id' => '25',
            'index_id' => '20',
            'institution_id' => '1',
            'student_id' => '1039',
            'modified_user_id' => '1',
            'modified' => '2017-01-03 16:01:58',
            'created_user_id' => '2',
            'created' => '2017-01-03 16:02:16'
        ],
        [
            'id' => '33',
            'average_index' => '0.00',
            'total_index' => '5',
            'academic_period_id' => '25',
            'index_id' => '20',
            'institution_id' => '1',
            'student_id' => '1154',
            'modified_user_id' => '1',
            'modified' => '2017-01-03 16:01:58',
            'created_user_id' => '2',
            'created' => '2017-01-03 16:02:16'
        ]
    ];
}
