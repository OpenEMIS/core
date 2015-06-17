<?php

namespace Institution\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SecurityUsersFixture extends TestFixture {

	public $import = ['table' => 'security_users'];
	
	public $records = [
		[
			'id' => 1,
			'username' => 'admin',
			'password' => '$2y$10$2su1R6ZNGay4j9iBQ.lSbeV8A0j1PHcUPZR3gMgSR89WTeZlBTF4e',
			'openemis_no' => '',
			'first_name' => 'System',
			'middle_name' => NULL,
			'third_name' => NULL,
			'last_name' => 'Administrator',
			'preferred_name' => NULL,
			'address' => NULL,
			'postal_code' => NULL,
			'address_area_id' => 0,
			'birthplace_area_id' => 0,
			'gender_id' => 0,
			'date_of_birth' => '0000-00-00',
			'date_of_death' => NULL,
			'super_admin' => 1,
			'status' => 1,
			'last_login' => '',
			'photo_name' => '',
			'photo_content' => NULL,
			'modified_user_id' => 1,
			'modified' => '2014-01-01 00:00:00',
			'created_user_id' => 1,
			'created' => '2014-01-01 00:00:00'
		],

	];
}
