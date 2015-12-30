<?php

namespace Institution\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class InstitutionsFixture extends TestFixture {

	public $import = ['table' => 'institutions'];
	
	public $records = [
		[
			'id' => 1,
			'name' => 'Nanyang Technological University',
			'alternative_name' => 'NTU',
			'code' => 'NTU',
			'address' => 'test address',
			'postal_code' => '123456',
			'contact_person' => 'Benson Liang',
			'telephone' => '12345678',
			'fax' => '87654321',
			'email' => 'bliang@kordit.com',
			'website' => 'http://www.ntu.edu.sg',
			'date_opened' => '1985-01-01',
			'year_opened' => 1985,
			'date_closed' => '1990-01-01',
			'year_closed' => 1990,
			'longitude' => '100',
			'latitude' => '1.32',
			'institution_area_id' => null,
			'area_id' => 1,
			'area_administrative_id' => 1,
			'institution_locality_id' => 83,
			'institution_type_id' => 65,
			'institution_ownership_id' => 72,
			'institution_status_id' => 89,
			'institution_sector_id' => 2,
			'institution_provider_id' => 1,
			'institution_gender_id' => 6,
			'security_group_id' => 0,
			'modified_user_id' => 1,
			'modified' => '2014-01-01 00:00:00',
			'created_user_id' => 1,
			'created' => '2014-01-01 00:00:00'
		],

		[
			'id' => 2,
			'name' => 'National Univerysity of Singapore',
			'alternative_name' => 'NUS',
			'code' => 'NUS',
			'address' => 'test address 2',
			'postal_code' => '654321',
			'contact_person' => 'Jeff Zheng',
			'telephone' => '123456789',
			'fax' => '987654321',
			'email' => 'jzheng@kordit.com',
			'website' => 'http://www.nus.edu.sg',
			'date_opened' => '1985-01-01',
			'year_opened' => 1985,
			'date_closed' => '1990-01-01',
			'year_closed' => 1990,
			'longitude' => '100',
			'latitude' => '1.32',
			'institution_area_id' => null,
			'area_id' => 1,
			'area_administrative_id' => 1,
			'institution_locality_id' => 83,
			'institution_type_id' => 65,
			'institution_ownership_id' => 72,
			'institution_status_id' => 89,
			'institution_sector_id' => 2,
			'institution_provider_id' => 1,
			'institution_gender_id' => 6,
			'security_group_id' => 0,
			'modified_user_id' => 1,
			'modified' => '2014-01-01 00:00:00',
			'created_user_id' => 1,
			'created' => '2014-01-01 00:00:00'
		]
	];
}
