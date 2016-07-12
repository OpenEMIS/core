<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class EducationSubjectsFixture extends TestFixture
{
    public $import = ['table' => 'education_subjects'];
    public $records = [
        [
            'id' => 1,
            'name' => 'Expressive Arts Middle Division Test',
            'code' => 'EAMTest',
            'order' => 1,
            'visible' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2015-01-01 00:00:00'
        ], [
            'id' => 2,
            'name' => 'Expressive Arts Upper Division Test',
            'code' => 'EAUTest',
            'order' => 2,
            'visible' => 1,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => '2015-01-01 00:00:00'
        ]
    ];
}
