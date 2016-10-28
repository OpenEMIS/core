<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class CompetencySetsFixture extends TestFixture
{
    public $import = ['table' => 'competency_sets'];
    public $records = [
        [
            'created' => '2016-10-24 18:07:18',
            'created_user_id' => '2',
            'default' => '0',
            'editable' => '1',
            'id' => '2',
            'international_code' => null,
            'modified' => '2016-10-27 13:50:13',
            'modified_user_id' => '2',
            'name' => 'Competency set 1',
            'national_code' => null,
            'order' => '1',
            'visible' => '1'
        ],
        [
            'created' => '2016-10-24 18:07:33',
            'created_user_id' => '2',
            'default' => '0',
            'editable' => '1',
            'id' => '3',
            'international_code' => null,
            'modified' => '2016-10-27 17:15:24',
            'modified_user_id' => '2',
            'name' => 'Competency set 2',
            'national_code' => null,
            'order' => '2',
            'visible' => '1'
        ]
    ];
}

