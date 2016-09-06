<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionShiftsFixture extends TestFixture
{
    public $import = ['table' => 'institution_shifts'];
    public $records = [
        [
            'id' => '1',
            'start_time' => '08:00:00',
            'end_time' => '14:00:00',
            'academic_period_id' => '25',
            'institution_id' => '1',
            'location_institution_id' => '1',
            'shift_option_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-06-23 02:41:48'
        ], [
            'id' => '4',
            'start_time' => '14:00:00',
            'end_time' => '18:00:00',
            'academic_period_id' => '25',
            'institution_id' => '1',
            'location_institution_id' => '2',
            'shift_option_id' => '2',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-17 01:58:26'
        ], [
            'id' => '5',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'academic_period_id' => '25',
            'institution_id' => '3',
            'location_institution_id' => '2',
            'shift_option_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-17 02:02:59'
        ], [
            'id' => '6',
            'start_time' => '18:00:00',
            'end_time' => '21:00:00',
            'academic_period_id' => '25',
            'institution_id' => '1',
            'location_institution_id' => '4',
            'shift_option_id' => '3',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-17 02:09:10'
        ], [
            'id' => '7',
            'start_time' => '07:00:00',
            'end_time' => '11:00:00',
            'academic_period_id' => '25',
            'institution_id' => '5',
            'location_institution_id' => '5',
            'shift_option_id' => '1',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-18 07:58:35'
        ], [
            'id' => '8',
            'start_time' => '11:00:00',
            'end_time' => '15:00:00',
            'academic_period_id' => '25',
            'institution_id' => '5',
            'location_institution_id' => '6',
            'shift_option_id' => '2',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-18 08:25:49'
        ]
    ];
}
