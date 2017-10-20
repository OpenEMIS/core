<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TransportFeaturesFixture extends TestFixture
{
    public $import = ['table' => 'transport_features'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Seat Belts',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '1',
            'international_code' => '',
            'national_code' => '',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2017-10-19 05:29:26'
        ]
    ];
}
