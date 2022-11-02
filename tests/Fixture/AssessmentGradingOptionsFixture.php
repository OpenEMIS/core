<?php 
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AssessmentGradingOptionsFixture extends TestFixture
{
    public $import = ['table' => 'assessment_grading_options'];
    public $records = [
        [
            'id' => '2',
            'code' => 'A',
            'name' => 'Excellent',
            'min' => '80.00',
            'max' => '100.00',
            'order' => '1',
            'visible' => '1',
            'assessment_grading_type_id' => '2',
            'modified_user_id' => '1',
            'modified' => '2015-07-10 19:24:17',
            'created_user_id' => '1',
            'created' => '2015-07-10 19:24:17'
        ], [
            'id' => '3',
            'code' => 'B',
            'name' => 'Competent',
            'min' => '70.00',
            'max' => '79.00',
            'order' => '2',
            'visible' => '1',
            'assessment_grading_type_id' => '2',
            'modified_user_id' => '1',
            'modified' => '2015-07-10 19:24:49',
            'created_user_id' => '1',
            'created' => '2015-07-10 19:24:49'
        ], [
            'id' => '4',
            'code' => 'C',
            'name' => 'Satisfactory',
            'min' => '60.00',
            'max' => '69.00',
            'order' => '3',
            'visible' => '1',
            'assessment_grading_type_id' => '2',
            'modified_user_id' => '1',
            'modified' => '2015-07-10 21:37:53',
            'created_user_id' => '1',
            'created' => '2015-07-10 21:37:53'
        ], [
            'id' => '5',
            'code' => 'D',
            'name' => 'Adequate',
            'min' => '50.00',
            'max' => '59.00',
            'order' => '4',
            'visible' => '1',
            'assessment_grading_type_id' => '2',
            'modified_user_id' => '1',
            'modified' => '2015-07-10 21:38:36',
            'created_user_id' => '1',
            'created' => '2015-07-10 21:38:36'
        ], [
            'id' => '6',
            'code' => 'E',
            'name' => 'Inadequate',
            'min' => '0.00',
            'max' => '49.00',
            'order' => '5',
            'visible' => '1',
            'assessment_grading_type_id' => '2',
            'modified_user_id' => '1',
            'modified' => '2015-07-10 21:39:01',
            'created_user_id' => '1',
            'created' => '2015-07-10 21:39:01'
        ], [
            'id' => '7',
            'code' => 'GradingOption01',
            'name' => 'Grading Options One',
            'min' => '50.00',
            'max' => '60.00',
            'order' => '6',
            'visible' => '1',
            'assessment_grading_type_id' => '6',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-17 06:22:10'
        ], [
            'id' => '8',
            'code' => 'GradingOption02',
            'name' => 'Grading Options Two',
            'min' => '61.00',
            'max' => '70.00',
            'order' => '7',
            'visible' => '1',
            'assessment_grading_type_id' => '6',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-17 06:22:10'
        ], [
            'id' => '9',
            'code' => 'GradingOption03',
            'name' => 'Grading Options Three',
            'min' => '71.00',
            'max' => '80.00',
            'order' => '8',
            'visible' => '1',
            'assessment_grading_type_id' => '6',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-17 06:23:16'
        ], [
            'id' => '10',
            'code' => 'GradingOption0301',
            'name' => 'grade option three threee',
            'min' => '50.00',
            'max' => '75.00',
            'order' => '9',
            'visible' => '1',
            'assessment_grading_type_id' => '7',
            'modified_user_id' => '2',
            'modified' => '2016-08-24 06:52:21',
            'created_user_id' => '2',
            'created' => '2016-08-24 06:43:07'
        ], [
            'id' => '11',
            'code' => 'GradingOption0302',
            'name' => 'grade option three Two',
            'min' => '75.00',
            'max' => '100.00',
            'order' => '10',
            'visible' => '1',
            'assessment_grading_type_id' => '7',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-24 06:52:21'
        ]
    ];
}