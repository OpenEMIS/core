<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionGendersFixture extends TestFixture
{
    public $import = ['table' => 'institution_genders'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Mixed',
            'code' => 'X',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '1',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-04-26 07:49:56'
        ], [
            'id' => '2',
            'name' => 'Male',
            'code' => 'M',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-04-26 07:50:01'
        ], [
            'id' => '3',
            'name' => 'Female',
            'code' => 'F',
            'order' => '3',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-04-26 07:50:05'
        ]
    ];
}
