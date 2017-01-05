<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class IndexesCriteriasFixture extends TestFixture
{
    public $import = ['table' => 'indexes_criterias'];
    public $records = [
        [
            'id' => '13',
            'criteria' => 'Behaviour',
            'operator' => '3',
            'threshold' => '1',
            'index_value' => '8',
            'index_id' => '19',
            'modified_user_id' => '2',
            'modified' => '2016-12-30 15:08:11',
            'created_user_id' => '2',
            'created' => '2016-12-30 14:36:23'
        ],
        [
            'id' => '14',
            'criteria' => 'Behaviour',
            'operator' => '3',
            'threshold' => '1',
            'index_value' => '10',
            'index_id' => '20',
            'modified_user_id' => '2',
            'modified' => '2016-12-30 15:07:11',
            'created_user_id' => '2',
            'created' => '2016-12-30 14:37:01'
        ],
        [
            'id' => '16',
            'criteria' => 'Behaviour',
            'operator' => '3',
            'threshold' => '1',
            'index_value' => '4',
            'index_id' => '21',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-12-30 14:37:54'
        ],
        [
            'id' => '17',
            'criteria' => 'Behaviour',
            'operator' => '3',
            'threshold' => '2',
            'index_value' => '5',
            'index_id' => '20',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2017-01-03 14:53:08'
        ]
    ];
}
