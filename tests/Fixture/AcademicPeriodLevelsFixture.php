<?php 
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AcademicPeriodLevelsFixture extends TestFixture
{
    // Optional. Set this property to load fixtures to a different test datasource
    // public $connection = 'test';

    public $import = ['table' => 'academic_period_levels'];
    public $records = [
        [
            'id' => 1, 
            'name' => 'Year', 
            'level' => '1', 
            'editable' => '0', 
            'modified_user_id' => NULL, 
            'modified' => NULL, 
            'created_user_id' => '1', 
            'created' => '2015-02-04 00:00:00'
        ]
    ];
}