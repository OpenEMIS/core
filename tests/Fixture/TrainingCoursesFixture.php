<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TrainingCoursesFixture extends TestFixture
{
    public $import = ['table' => 'training_courses'];
    public $records = [
        [
            'id' => '1',
            'code' => '001',
            'name' => 'Basic excel',
            'description' => 'Basic excel',
            'objective' => 'Test objective',
            'credit_hours' => '2',
            'duration' => '2',
            'number_of_months' => '3',
            'file_name' => NULL,
            'file_content' => NULL,
            'training_field_of_study_id' => '681',
            'training_course_type_id' => '225',
            'training_mode_of_delivery_id' => '580',
            'training_requirement_id' => '682',
            'training_level_id' => '227',
            'assignee_id' => '2',
            'status_id' => '9',
            'modified_user_id' => '2',
            'modified' => '2016-11-03 15:09:40',
            'created_user_id' => '2',
            'created' => '2016-10-27 14:36:01'
        ], [
            'id' => '2',
            'code' => '002',
            'name' => 'Intermediate Excel',
            'description' => 'Intermediate Excel',
            'objective' => 'Test objective 2',
            'credit_hours' => '2',
            'duration' => '2',
            'number_of_months' => '2',
            'file_name' => NULL,
            'file_content' => NULL,
            'training_field_of_study_id' => '681',
            'training_course_type_id' => '225',
            'training_mode_of_delivery_id' => '228',
            'training_requirement_id' => '229',
            'training_level_id' => '679',
            'assignee_id' => '2',
            'status_id' => '9',
            'modified_user_id' => '2',
            'modified' => '2016-11-02 15:33:23',
            'created_user_id' => '2',
            'created' => '2016-10-27 14:36:58'
        ], [
            'id' => '3',
            'code' => '003',
            'name' => 'Advanced Excel',
            'description' => 'Advanced Excel',
            'objective' => 'Test objective 3',
            'credit_hours' => '2',
            'duration' => '3',
            'number_of_months' => '3',
            'file_name' => NULL,
            'file_content' => NULL,
            'training_field_of_study_id' => '681',
            'training_course_type_id' => '225',
            'training_mode_of_delivery_id' => '228',
            'training_requirement_id' => '229',
            'training_level_id' => '680',
            'assignee_id' => '2',
            'status_id' => '8',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '2',
            'created' => '2016-10-27 14:37:34'
        ]
    ];
}

