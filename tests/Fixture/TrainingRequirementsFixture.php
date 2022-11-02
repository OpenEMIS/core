<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TrainingRequirementsFixture extends TestFixture
{
    public $import = ['table' => 'training_requirements'];
    public $records = [
        [
            'id' => '229',
            'name' => 'Elective',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => '2015-07-03 05:37:43',
            'created_user_id' => '1',
            'created' => '2014-06-25 14:36:43'
        ], [
            'id' => '682',
            'name' => 'Compulsary',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '2',
            'created' => '2016-04-26 09:38:20'
        ]
    ];
}

