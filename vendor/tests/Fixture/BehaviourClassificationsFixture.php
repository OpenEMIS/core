<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class BehaviourClassificationsFixture extends TestFixture
{
    public $import = ['table' => 'behaviour_classifications'];
    public $records = [
        [
            'id' => '1',
            'name' => 'Serious',
            'order' => '1',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => null,
            'national_code' => null,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-11-29 11:46:01'
        ],
        [
            'id' => '2',
            'name' => 'Very Serious',
            'order' => '2',
            'visible' => '1',
            'editable' => '1',
            'default' => '0',
            'international_code' => null,
            'national_code' => null,
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-11-29 11:46:15'
        ]
    ];
}
