<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class EducationProgrammesFixture extends TestFixture
{
    public $import = ['table' => 'education_programmes'];
    public $records = [
        [
            'id' => '8',
            'code' => '1',
            'name' => 'Pre-school Education',
            'duration' => '2',
            'order' => '1',
            'visible' => '1',
            'education_field_of_study_id' => '1',
            'education_cycle_id' => '11',
            'education_certification_id' => '2',
            'modified_user_id' => '2',
            'modified' => '2015-08-28 17:57:34',
            'created_user_id' => '2',
            'created' => '2014-09-20 22:21:26'
        ], [
            'id' => '9',
            'code' => '2',
            'name' => 'Primary Education',
            'duration' => '6',
            'order' => '1',
            'visible' => '1',
            'education_field_of_study_id' => '1',
            'education_cycle_id' => '12',
            'education_certification_id' => '3',
            'modified_user_id' => '2',
            'modified' => '2016-05-25 09:55:59',
            'created_user_id' => '2',
            'created' => '2014-09-20 22:22:42'
        ], [
            'id' => '10',
            'code' => '3',
            'name' => 'Special Education',
            'duration' => '6',
            'order' => '7',
            'visible' => '1',
            'education_field_of_study_id' => '1',
            'education_cycle_id' => '13',
            'education_certification_id' => '3',
            'modified_user_id' => '2',
            'modified' => '2015-08-28 17:58:57',
            'created_user_id' => '2',
            'created' => '2014-09-20 22:29:36'
        ], [
            'id' => '12',
            'code' => '6',
            'name' => 'Lower Secondary Education - Express',
            'duration' => '2',
            'order' => '2',
            'visible' => '1',
            'education_field_of_study_id' => '1',
            'education_cycle_id' => '15',
            'education_certification_id' => '1',
            'modified_user_id' => '2',
            'modified' => '2016-04-26 07:49:25',
            'created_user_id' => '1',
            'created' => '2014-09-20 22:31:50'
        ], [
            'id' => '13',
            'code' => '8',
            'name' => 'Secondary Education - Express',
            'duration' => '2',
            'order' => '6',
            'visible' => '1',
            'education_field_of_study_id' => '1',
            'education_cycle_id' => '18',
            'education_certification_id' => '5',
            'modified_user_id' => '2',
            'modified' => '2016-04-26 07:49:25',
            'created_user_id' => '1',
            'created' => '2014-09-20 22:32:27'
        ], [
            'id' => '14',
            'code' => '9',
            'name' => 'Lower Secondary Education - Normal',
            'duration' => '2',
            'order' => '3',
            'visible' => '1',
            'education_field_of_study_id' => '1',
            'education_cycle_id' => '15',
            'education_certification_id' => '1',
            'modified_user_id' => '2',
            'modified' => '2016-04-26 07:49:25',
            'created_user_id' => '1',
            'created' => '2014-09-20 22:33:12'
        ], [
            'id' => '15',
            'code' => '11',
            'name' => 'Secondary Education - Normal Academic',
            'duration' => '3',
            'order' => '8',
            'visible' => '1',
            'education_field_of_study_id' => '1',
            'education_cycle_id' => '18',
            'education_certification_id' => '5',
            'modified_user_id' => '2',
            'modified' => '2016-04-26 07:49:09',
            'created_user_id' => '1',
            'created' => '2014-09-20 22:33:51'
        ], [
            'id' => '16',
            'code' => '12',
            'name' => 'Secondary Education - Normal Technical',
            'duration' => '2',
            'order' => '4',
            'visible' => '1',
            'education_field_of_study_id' => '1',
            'education_cycle_id' => '18',
            'education_certification_id' => '4',
            'modified_user_id' => '2',
            'modified' => '2016-04-26 07:49:09',
            'created_user_id' => '2',
            'created' => '2016-04-26 07:45:26'
        ]
    ];
}
