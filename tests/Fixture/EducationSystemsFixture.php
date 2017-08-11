<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class EducationSystemsFixture extends TestFixture
{
    public $import = ['table' => 'education_systems'];
    public $records = [
        [
            'id' => 1,
            'name' => 'National Education System',
            'order' => 1,
            'visible' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2015-01-01 00:00:00'
        ], [
            'id' => 2,
            'name' => 'International Education System',
            'order' => 2,
            'visible' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2015-01-01 00:00:00'
        ]
    ];
}
