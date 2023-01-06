<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionClassesFixture extends TestFixture
{
    public $import = ['table' => 'institution_classes'];
    public $records = [
		[
		    'academic_period_id' => '25',
		    'class_number' => '1',
		    'created' => '2016-06-23 02:41:57',
		    'created_user_id' => '2',
		    'id' => '1',
		    'institution_id' => '1',
		    'institution_shift_id' => '1',
		    'modified' => null,
		    'modified_user_id' => null,
		    'name' => 'Nursery-A',
		    'staff_id' => '11875'
		],
		[
		    'academic_period_id' => '25',
		    'class_number' => '1',
		    'created' => '2016-06-24 02:45:16',
		    'created_user_id' => '2',
		    'id' => '2',
		    'institution_id' => '1',
		    'institution_shift_id' => '1',
		    'modified' => '2016-06-24 02:48:37',
		    'modified_user_id' => '2',
		    'name' => 'Kindergarten 2-A',
		    'staff_id' => '11002'
		]
    ];
}
