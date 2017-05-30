<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class StudentBehavioursFixture extends TestFixture
{
    public $import = ['table' => 'student_behaviours'];
    public $records = [
        [
            'id' => '90',
            'title' => 'Strike 3',
            'description' => 'q',
            'action' => 'q',
            'date_of_behaviour' => '2015-12-14',
            'time_of_behaviour' => '16:55:00',
            'academic_period_id' => '10',
            'student_id' => '1039',
            'institution_id' => '1',
            'student_behaviour_category_id' => '237',
            'modified_user_id' => '1',
            'modified' => '2017-01-04 09:16:46',
            'created_user_id' => '2',
            'created' => '2016-12-14 16:55:47'
        ],
        [
            'id' => '95',
            'title' => 'Strike 2',
            'description' => 'desc',
            'action' => 'act',
            'date_of_behaviour' => '2016-12-19',
            'time_of_behaviour' => '08:57:00',
            'academic_period_id' => '25',
            'student_id' => '1039',
            'institution_id' => '1',
            'student_behaviour_category_id' => '237',
            'modified_user_id' => '1',
            'modified' => '2017-01-03 16:01:56',
            'created_user_id' => '2',
            'created' => '2016-12-19 08:57:58'
        ],
        [
            'id' => '96',
            'title' => 'Strike 3',
            'description' => 'desc',
            'action' => 'act',
            'date_of_behaviour' => '2016-12-19',
            'time_of_behaviour' => '08:58:00',
            'academic_period_id' => '25',
            'student_id' => '1154',
            'institution_id' => '1',
            'student_behaviour_category_id' => '603',
            'modified_user_id' => '1',
            'modified' => '2017-01-03 16:01:58',
            'created_user_id' => '2',
            'created' => '2016-12-19 08:58:55'
        ],
        [
            'id' => '98',
            'title' => 'strke 2',
            'description' => 'desc',
            'action' => 'act',
            'date_of_behaviour' => '2016-12-19',
            'time_of_behaviour' => '10:51:00',
            'academic_period_id' => '25',
            'student_id' => '1039',
            'institution_id' => '1',
            'student_behaviour_category_id' => '238',
            'modified_user_id' => '1',
            'modified' => '2017-01-03 16:01:58',
            'created_user_id' => '2',
            'created' => '2016-12-19 10:51:35'
        ],
        [
            'id' => '99',
            'title' => 'Strike 1',
            'description' => 'q',
            'action' => 'q',
            'date_of_behaviour' => '2016-12-29',
            'time_of_behaviour' => '14:16:00',
            'academic_period_id' => '25',
            'student_id' => '1039',
            'institution_id' => '1',
            'student_behaviour_category_id' => '601',
            'modified_user_id' => '1',
            'modified' => '2017-01-03 16:01:58',
            'created_user_id' => '2',
            'created' => '2016-12-29 14:17:04'
        ]
    ];
}

