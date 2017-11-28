<?php
use Migrations\AbstractSeed;

/**
 * WorkflowModels seed.
 */
class WorkflowModelsSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'id' => '1',
                'name' => 'Staff > Career > Leave',
                'model' => 'Institution.StaffLeave',
                'filter' => 'Staff.StaffLeaveTypes',
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => '2016-11-08 07:25:05',
            ],
            [
                'id' => '2',
                'name' => 'Institutions > Survey > Forms',
                'model' => 'Institution.InstitutionSurveys',
                'filter' => 'Survey.SurveyForms',
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => '2016-11-08 07:25:05',
            ],
            [
                'id' => '3',
                'name' => 'Administration > Training > Courses',
                'model' => 'Training.TrainingCourses',
                'filter' => NULL,
                'is_school_based' => '0',
                'created_user_id' => '1',
                'created' => '2016-11-08 07:25:05',
            ],
            [
                'id' => '4',
                'name' => 'Administration > Training > Sessions',
                'model' => 'Training.TrainingSessions',
                'filter' => NULL,
                'is_school_based' => '0',
                'created_user_id' => '1',
                'created' => '2016-11-08 07:25:05',
            ],
            [
                'id' => '5',
                'name' => 'Administration > Training > Results',
                'model' => 'Training.TrainingSessionResults',
                'filter' => NULL,
                'is_school_based' => '0',
                'created_user_id' => '1',
                'created' => '2016-11-08 07:25:05',
            ],
            [
                'id' => '6',
                'name' => 'Staff > Training > Needs',
                'model' => 'Institution.StaffTrainingNeeds',
                'filter' => NULL,
                'is_school_based' => '0',
                'created_user_id' => '1',
                'created' => '2016-11-08 07:25:05',
            ],
            [
                'id' => '7',
                'name' => 'Institutions > Positions',
                'model' => 'Institution.InstitutionPositions',
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => '2016-11-08 07:25:05',
            ],
            [
                'id' => '8',
                'name' => 'Institutions > Staff > Change in Assignment',
                'model' => 'Institution.StaffPositionProfiles',
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => '2016-11-08 07:25:05',
            ],
            [
                'id' => '9',
                'name' => 'Institutions > Visits > Requests',
                'model' => 'Institution.VisitRequests',
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => '2016-11-08 07:25:05',
            ],
            [
                'id' => '10',
                'name' => 'Administration > Training > Applications',
                'model' => 'Training.TrainingApplications',
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => '2016-11-08 07:25:05',
            ],
            [
                'id' => '11',
                'name' => 'Staff > Professional Development > Licenses',
                'model' => 'Staff.Licenses',
                'filter' => 'FieldOption.LicenseTypes',
                'is_school_based' => '0',
                'created_user_id' => '1',
                'created' => '2017-03-10 07:14:53',
            ],
            [
                'id' => '12',
                'name' => 'Institutions > Cases',
                'model' => 'Institution.InstitutionCases',
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => '2017-04-10 09:55:36',
            ],
        ];

        $table = $this->table('workflow_models');
        $table->insert($data)->save();
    }
}
