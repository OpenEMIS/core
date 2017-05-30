<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TrainingFieldOfStudiesFixture extends TestFixture
{
    public $import = ['table' => 'training_field_of_studies'];
    public $records = [
        [
            'id' => '226',
            'name' => 'Social',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2014-06-25 14:34:16'
        ], [
            'id' => '681',
            'name' => 'Communications',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '2',
            'created' => '2016-04-26 09:37:59'
        ]
    ];
}

