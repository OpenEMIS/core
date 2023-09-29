<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TrainingCourseTypesFixture extends TestFixture
{
    public $import = ['table' => 'training_course_types'];
    public $records = [
        [
            'id' => '225',
            'name' => 'Open/Public',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2014-06-25 14:33:52'
        ], [
            'id' => '675',
            'name' => 'Bespoke',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '2',
            'created' => '2016-04-26 09:35:07'
        ]
    ];
}

