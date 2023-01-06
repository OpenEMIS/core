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
        [
            'id' => '11',
            'name' => 'Pre-school Education',
            'admission_age' => '3',
            'order' => '1',
            'visible' => '1',
            'education_level_id' => '4',
            'modified_user_id' => '2',
            'modified' => '2015-08-28 17:51:15',
            'created_user_id' => '2',
            'created' => '2014-09-20 22:20:53'
        ], [ 
            'id' => '12',
            'name' => 'Primary Education',
            'admission_age' => '7',
            'order' => '1',
            'visible' => '1',
            'education_level_id' => '5',
            'modified_user_id' => '2',
            'modified' => '2016-05-25 09:54:59',
            'created_user_id' => '2',
            'created' => '2014-09-20 22:22:12'
        ], [ 
            'id' => '13',
            'name' => 'Special Education',
            'admission_age' => '5',
            'order' => '1',
            'visible' => '1',
            'education_level_id' => '6',
            'modified_user_id' => '2',
            'modified' => '2015-08-28 17:52:03',
            'created_user_id' => '2',
            'created' => '2014-09-20 22:29:04'
        ], [ 
            'id' => '15',
            'name' => 'Secondary Education - Lower Secondary',
            'admission_age' => '13',
            'order' => '1',
            'visible' => '1',
            'education_level_id' => '8',
            'modified_user_id' => '2',
            'modified' => '2016-04-26 07:41:54',
            'created_user_id' => '1',
            'created' => '2014-09-20 22:31:24'
        ], [ 
            'id' => '18',
            'name' => 'Secondary Education - Upper Secondary',
            'admission_age' => '15',
            'order' => '2',
            'visible' => '1',
            'education_level_id' => '8',
            'modified_user_id' => '2',
            'modified' => '2016-04-26 07:41:39',
            'created_user_id' => '1',
            'created' => '2014-09-20 22:33:27'
        ], [ 
            'id' => '19',
            'name' => 'Primary Two',
            'admission_age' => '8',
            'order' => '2',
            'visible' => '1',
            'education_level_id' => '5',
            'modified_user_id' => null,
            'modified' => null,
            'created_user_id' => '2',
            'created' => '2016-08-11 09:33:41'
        ]
    ];
}
