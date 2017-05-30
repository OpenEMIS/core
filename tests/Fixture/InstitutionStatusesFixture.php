<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionStatusesFixture extends TestFixture
{
    public $import = ['table' => 'institution_statuses'];
    public $records = [
        [
            'id' => '117',
            'name' => 'Operating',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '1',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => '2',
            'modified' => '2016-04-26 09:05:07',
            'created_user_id' => '0',
            'created' => '1970-01-01 00:00:00'
        ], [
            'id' => '118',
            'name' => 'Under Construction',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => null,
            'national_code' => null,
            'modified_user_id' => null,
            'modified' => '2015-07-03 05:37:45',
            'created_user_id' => '1',
            'created' => '2014-09-29 00:00:00'
        ], [
            'id' => '119',
            'name' => 'Suspended',
            'order' => '3',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => '1',
            'modified' => '2015-07-03 05:37:46',
            'created_user_id' => '0',
            'created' => '1970-01-01 00:00:00'
        ], [
            'id' => '120',
            'name' => 'Closed',
            'order' => '4',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => '1',
            'modified' => '2015-07-03 05:37:47',
            'created_user_id' => '0',
            'created' => '1970-01-01 00:00:00'
        ]
    ];
}
