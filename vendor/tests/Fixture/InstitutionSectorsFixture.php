<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionSectorsFixture extends TestFixture
{
    public $import = ['table' => 'institution_sectors'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Government',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '1',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => '2',
            'modified' => '2016-05-05 07:50:23',
            'created_user_id' => '2',
            'created' => '2016-04-26 09:04:40'
        ], [
            'id' => '2',
            'name' => 'Autonomous',
            'order' => '3',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => '2',
            'modified' => '2016-05-05 07:51:24',
            'created_user_id' => '2',
            'created' => '2016-04-26 09:04:45'
        ], [
            'id' => '3',
            'name' => 'Government-Aided',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-05-05 07:51:41'
        ], [
            'id' => '4',
            'name' => 'Independant',
            'order' => '4',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-05-05 07:51:53'
        ]
    ];
}
