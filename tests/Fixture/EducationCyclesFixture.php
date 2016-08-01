<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class EducationCyclesFixture extends TestFixture
{
    // Optional. Set this property to load fixtures to a different test datasource
    // public $connection = 'test';

    public $import = ['table' => 'education_cycles'];
    public $records = [
        [
            'id' => 1,
            'name' => 'Primary Education - Normal Technical (Primary 1-2)',
            'admission_age' => '6',
            'order' => '1',
            'visible' => '1',
            'education_level_id' => '1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-01-01 00:00:00'
        ],
        // testingId
        [
            'id' => 2,
            'name' => 'Primary Education - Normal Technical (Primary 3-4)',
            'admission_age' => '8',
            'order' => '1',
            'visible' => '1',
            'education_level_id' => '1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-01-01 00:00:00'
        ],
        [
            'id' => 3,
            'name' => 'Primary Education - Normal Technical (Primary 5-6)',
            'admission_age' => '10',
            'order' => '1',
            'visible' => '1',
            'education_level_id' => '1',
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-01-01 00:00:00'
        ],
    ];
}
