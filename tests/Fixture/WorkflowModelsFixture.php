<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class WorkflowModelsFixture extends TestFixture
{
    public $import = ['table' => 'workflow_models'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Staff > Career > Leave',
            'model' => 'Staff.Leaves',
            'filter' => 'Staff.StaffLeaveTypes',
            'is_school_based' => '1',
            'created_user_id' => '1',
            'created' => '1970-01-01 00:00:00'
        ], [
            'id' => '2',
            'name' => 'Institutions > Survey > Forms',
            'model' => 'Institution.InstitutionSurveys',
            'filter' => 'Survey.SurveyForms',
            'is_school_based' => '1',
            'created_user_id' => '1',
            'created' => '1970-01-01 00:00:00'
        ], [
            'id' => '3',
            'name' => 'Administration > Training > Courses',
            'model' => 'Training.TrainingCourses',
            'filter' => null,
            'is_school_based' => '0',
            'created_user_id' => '1',
            'created' => '2015-11-06 11:27:05'
        ], [
            'id' => '4',
            'name' => 'Administration > Training > Sessions',
            'model' => 'Training.TrainingSessions',
            'filter' => null,
            'is_school_based' => '0',
            'created_user_id' => '1',
            'created' => '2015-11-06 11:27:05'
        ], [
            'id' => '5',
            'name' => 'Administration > Training > Results',
            'model' => 'Training.TrainingSessionResults',
            'filter' => null,
            'is_school_based' => '0',
            'created_user_id' => '1',
            'created' => '2015-11-06 11:27:05'
        ], [
            'id' => '6',
            'name' => 'Staff > Training > Needs',
            'model' => 'Staff.TrainingNeeds',
            'filter' => null,
            'is_school_based' => '0',
            'created_user_id' => '1',
            'created' => '2015-12-15 15:41:55'
        ], [
            'id' => '7',
            'name' => 'Institutions > Positions',
            'model' => 'Institution.InstitutionPositions',
            'filter' => null,
            'is_school_based' => '1',
            'created_user_id' => '1',
            'created' => '2016-02-12 18:29:36'
        ], [
            'id' => '8',
            'name' => 'Institutions > Staff > Change in Assignment',
            'model' => 'Institution.StaffPositionProfiles',
            'filter' => null,
            'is_school_based' => '1',
            'created_user_id' => '1',
            'created' => '2016-04-15 10:45:45'
        ], [
            'id' => '10',
            'name' => 'Administration > Training > Applications',
            'model' => 'Training.TrainingApplications',
            'filter' => null,
            'is_school_based' => '1',
            'created_user_id' => '1',
            'created' => '2016-04-15 10:45:45'
        ], [
            'id' => '13',
            'name' => 'Institutions > Staff > Incoming Transfer',
            'model' => 'Institution.StaffTransferIn',
            'filter' => null,
            'is_school_based' => '1',
            'created_user_id' => '1',
            'created' => '2017-10-13 18:10:04'
        ], [
            'id' => '14',
            'name' => 'Institutions > Staff > Outgoing Transfer',
            'model' => 'Institution.StaffTransferOut',
            'filter' => null,
            'is_school_based' => '1',
            'created_user_id' => '1',
            'created' => '2017-10-13 18:10:04'
        ]
    ];
}
