<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TrainingLevelsFixture extends TestFixture
{
    public $import = ['table' => 'training_levels'];
    public $records = [
        [
            'id' => '227',
            'name' => 'Beginner',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '101',
            'national_code' => '101',
            'modified_user_id' => NULL,
            'modified' => '2015-07-03 05:37:43',
            'created_user_id' => '1',
            'created' => '2014-06-25 14:36:25'
        ], [
            'id' => '679',
            'name' => 'Intermediate',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '2',
            'created' => '2016-04-26 09:37:05'
        ], [
            'id' => '680',
            'name' => 'Advanced',
            'order' => '3',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '2',
            'created' => '2016-04-26 09:37:37'
        ]
    ];
}

