<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class IdentityTypesFixture extends TestFixture
{
    public $import = ['table' => 'identity_types'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Birth Certificate',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '1',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '1',
            'created' => '0000-00-00 00:00:00'
        ]
    ];
}

