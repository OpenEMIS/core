<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class EducationCertificationsFixture extends TestFixture
{
    // Optional. Set this property to load fixtures to a different test datasource
    // public $connection = 'test';

    public $import = ['table' => 'education_certifications'];
    public $records = [
        [
            'id' => 1,
            'name' => 'No Certification',
            'order' => 1,
            'visible' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-01-01 00:00:00'
        ],
        // testingId
        [
            'id' => 2,
            'name' => 'Pre-School Certicate',
            'order' => 2,
            'visible' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-01-01 00:00:00'
        ],
        [
            'id' => 3,
            'name' => 'Primary Certicate',
            'order' => 3,
            'visible' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => '1',
            'created' => '2015-01-01 00:00:00'
        ],
    ];
}