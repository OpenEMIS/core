<?php

namespace Area\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AreaLevelsFixture extends TestFixture {

	public $import = ['table' => 'area_levels'];
	
	public $records = [
		[
			'id' => 1,
			'name' => 'Country',
			'level' => 1,
			'modified_user_id' => 1,
			'modified' => '2014-01-01 00:00:00',
			'created_user_id' => 1,
			'created' => '2014-01-01 00:00:00'
		],

		[
                        'id' => 2,
                        'name' => 'Province',
                        'level' => 2,
                        'modified_user_id' => 1,
                        'modified' => '2014-01-01 00:00:00',
                        'created_user_id' => 1,   
                        'created' => '2014-01-01 00:00:00'
		]
	];
}
