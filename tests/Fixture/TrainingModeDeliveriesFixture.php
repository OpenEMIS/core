<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TrainingModeDeliveriesFixture extends TestFixture
{
    public $import = ['table' => 'training_mode_deliveries'];
    public $records = [
        [
            'id' => '228',
            'name' => 'Online',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => 'OL',
            'national_code' => 'ONL',
            'modified_user_id' => NULL,
            'modified' => '2015-07-03 05:37:43',
            'created_user_id' => '1',
            'created' => '2014-06-25 14:34:42'
        ], [
            'id' => '580',
            'name' => 'Self Study',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => '2',
            'modified' => '2015-08-27 19:44:22',
            'created_user_id' => '2',
            'created' => '2015-08-27 19:44:22'
        ], [
            'id' => '581',
            'name' => 'School Visit',
            'order' => '3',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => '2',
            'modified' => '2015-08-27 19:44:38',
            'created_user_id' => '2',
            'created' => '2015-08-27 19:44:38'
        ]
    ];
}

