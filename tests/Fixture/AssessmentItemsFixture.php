<?php 
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AssessmentItemsFixture extends TestFixture
{
    public $import = ['table' => 'assessment_items'];
    public $records = [
        [
            'id' => 1,
            'weight' => 0.1,
            'assessment_id' => 2,
            'education_subject_id' => 2,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2017-01-07 00:00:00'
        ], [
            'id' => 2,
            'weight' => 0.2,
            'assessment_id' => 2,
            'education_subject_id' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2017-01-07 00:00:00'
        ], [
            'id' => 3,
            'weight' => 0.3,
            'assessment_id' => 1,
            'education_subject_id' => 3,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2017-01-07 00:00:00'
        ]
    ];
}