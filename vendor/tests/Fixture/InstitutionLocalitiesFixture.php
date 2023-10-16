<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionLocalitiesFixture extends TestFixture
{
    public $import = ['table' => 'institution_localities'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Urban',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-04-26 08:51:01'
        ], [
            'id' => '2',
            'name' => 'Rural',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-04-26 08:51:05'
        ]
    ];
}
