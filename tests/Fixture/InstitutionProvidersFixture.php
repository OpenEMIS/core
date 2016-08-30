<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionProvidersFixture extends TestFixture
{
    public $import = ['table' => 'institution_providers'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Private',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-04-26 09:03:39'
        ], [
            'id' => '2',
            'name' => 'Government',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '1',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-04-26 09:03:47'
        ]
    ];
}
