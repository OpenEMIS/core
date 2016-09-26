<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ExaminationItemsFixture extends TestFixture
{
    public $import = ['table' => 'examination_items'];
    public $records = [
        [
            'id' => "36d3647c-3f39-48b2-9149-e3371e15c9dc",
            'weight' => 0.00,
            'examination_id' => 1,
            'education_subject_id' => 8,
            'examination_grading_type_id' => 1,
            'date' => "2016-09-30",
            'start_time' => "01:00:00",
            'end_time' => "02:00:00",
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => "2016-09-19 06:48:01"
        ],
        [
            'id' => "558090a5-d222-4b1a-9c28-0b3a419a8e1d",
            'weight' => 1.00,
            'examination_id' => 2,
            'education_subject_id' => 94,
            'examination_grading_type_id' => 1,
            'date' => "2016-09-02",
            'start_time' => "15:00:00",
            'end_time' => "16:00:00",
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => "2016-09-20 07:48:30"
        ],
        [
            'id' => "799896c3-2fac-4521-af7b-2c0e14e25209",
            'weight' => 0.00,
            'examination_id' => 1,
            'education_subject_id' => 94,
            'examination_grading_type_id' => 1,
            'date' => "2016-09-30",
            'start_time' => "02:00:00",
            'end_time' => "03:00:00",
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => "2016-09-19 06:48:01"
        ],
        [
            'id' => "868043b9-931c-4618-8c77-71df83eaaed8",
            'weight' => 1.00,
            'examination_id' => 2,
            'education_subject_id' => 8,
            'examination_grading_type_id' => 1,
            'date' => "2016-09-01",
            'start_time' => "14:00:00",
            'end_time' => "15:00:00",
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 2,
            'created' => "2016-09-20 07:48:30"
        ]
    ];
}