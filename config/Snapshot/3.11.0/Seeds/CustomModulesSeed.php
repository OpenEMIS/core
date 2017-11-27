<?php
use Migrations\AbstractSeed;

/**
 * CustomModules seed.
 */
class CustomModulesSeed extends AbstractSeed
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
                'code' => 'Institution',
                'name' => 'Institution > Overview',
                'model' => 'Institution.Institutions',
                'visible' => '1',
                'parent_id' => '0',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
            [
                'id' => '2',
                'code' => 'Student',
                'name' => 'Student > Overview',
                'model' => 'Student.Students',
                'visible' => '1',
                'parent_id' => '0',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
            [
                'id' => '3',
                'code' => 'Staff',
                'name' => 'Staff > Overview',
                'model' => 'Staff.Staff',
                'visible' => '1',
                'parent_id' => '0',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
            [
                'id' => '5',
                'code' => 'Institution > Students',
                'name' => 'Institution > Students > Survey',
                'model' => 'Student.StudentSurveys',
                'visible' => '1',
                'parent_id' => '0',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
            [
                'id' => '6',
                'code' => 'Institution > Repeater',
                'name' => 'Institution > Repeater > Survey',
                'model' => 'InstitutionRepeater.RepeaterSurveys',
                'visible' => '1',
                'parent_id' => '0',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
            [
                'id' => '7',
                'code' => 'Land',
                'name' => 'Institution > Land',
                'model' => 'Institution.InstitutionLands',
                'visible' => '1',
                'parent_id' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '2017-05-29 09:57:12',
            ],
            [
                'id' => '8',
                'code' => 'Building',
                'name' => 'Institution > Building',
                'model' => 'Institution.InstitutionBuildings',
                'visible' => '1',
                'parent_id' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '2017-05-29 09:57:12',
            ],
            [
                'id' => '9',
                'code' => 'Floor',
                'name' => 'Institution > Floor',
                'model' => 'Institution.InstitutionFloors',
                'visible' => '1',
                'parent_id' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '2017-05-29 09:57:12',
            ],
            [
                'id' => '10',
                'code' => 'Room',
                'name' => 'Institution > Room',
                'model' => 'Institution.InstitutionRooms',
                'visible' => '1',
                'parent_id' => '1',
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
        ];

        $table = $this->table('custom_modules');
        $table->insert($data)->save();
    }
}
