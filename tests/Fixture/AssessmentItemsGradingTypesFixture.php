<?php 
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AssessmentItemsGradingTypesFixture extends TestFixture
{
    public $import = ['table' => 'assessment_items_grading_types'];
    public $records = [
        [
            'id' => 123,
            'assessment_id' => 1,
            'education_subject_id' => 3,
            'assessment_grading_type_id' => 2,
            'assessment_period_id' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 5,
            'created' => '2017-01-07 00:00:00'
        ]
    ];
}