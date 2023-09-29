<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class IndexesFixture extends TestFixture
{
    public $import = ['table' => 'indexes'];
    public $records = [
        [
            'id' => '19',
            'name' => 'Dropout Risk 2015',
            'generated_by' => null,
            'generated_on' => null,
            'academic_period_id' => '10',
            'modified_user_id' => '2',
            'modified' => '2016-12-30 15:08:11',
            'created_user_id' => '2',
            'created' => '2016-12-30 14:36:23'
        ],
        [
            'id' => '20',
            'name' => 'Dropout Risk 2016',
            'generated_by' => '2',
            'generated_on' => '2017-01-03 15:01:27',
            'academic_period_id' => '25',
            'modified_user_id' => '2',
            'modified' => '2017-01-03 14:53:08',
            'created_user_id' => '2',
            'created' => '2016-12-30 14:36:43'
        ],
        [
            'id' => '21',
            'name' => 'Dropout Risk 2017',
            'generated_by' => '2',
            'generated_on' => '2017-01-03 15:01:27',
            'academic_period_id' => '26',
            'modified_user_id' => '2',
            'modified' => '2016-12-30 14:38:03',
            'created_user_id' => '2',
            'created' => '2016-12-30 14:37:54'
        ]
    ];
}
