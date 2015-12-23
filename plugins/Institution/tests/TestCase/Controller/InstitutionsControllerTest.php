<?php

namespace Institution\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

class InstitutionsControllerTest extends IntegrationTestCase {
	public $fixtures = [
		'plugin.institution.institutions',
		// 'plugin.institution.institution_activities',
		// 'plugin.institution.security_users',
		// 'plugin.FieldOption.field_option_values',
		// 'plugin.FieldOption.field_options',
	];
	private $prefix = '/Institutions';

	public function setUp() {
		parent::setUp();
		$this->Institutions = TableRegistry::get('institutions');
		$this->Institutions->InstitutionActivities = TableRegistry::get('institution_activities');
		// $this->SecurityUsers = TableRegistry::get('security_users');
		// $this->Institutions = TableRegistry::get('Institutions');
	}

	public function setAuthSession() {
		$this->session([
	        'Auth' => [
	            'User' => [
	                'id' => 1,
	                'username' => 'admin',
	                'password' => '$2y$10$2su1R6ZNGay4j9iBQ.lSbeV8A0j1PHcUPZR3gMgSR89WTeZlBTF4e'
	                // other keys.
	            ]
	        ]
	    ]);
	}

	// public function testBeforePaginate() {
	// 	$session = $this->request->session();

	// 	if (array_key_exists('institution_id', $model->fields)) {
	// 		if (!$session->check('Institutions.id')) {
	// 			$this->Alert->error('general.notExists');
	// 		}
	// 		$options['conditions'][] = ['Institutions.id' => $session->read('Institutions.id')];
	// 	}
		
	// 	return $options;
	// }

	// public function testIndex() {
 //        $this->setAuthSession();
 //        $this->get($this->prefix);
 //        $this->assertResponseOk();
        
 //        // Example: Check if we have at least 10 records out from the pagination
 //        $this->assertEquals(true, (count($this->viewVariable('data')) <= 10));
	// }

	// public function testSearch() {
 //        $this->setAuthSession();
	// 	$this->post($this->prefix . '/index', ['Search' => ['searchField' => 'NUS']]);
	// 	$this->assertEquals(1, count($this->viewVariable('data')));

	// 	$data = $this->viewVariable('data');
		
	// 	foreach ($data as $obj) {
	// 		$recordData = $obj->code . '|' . $obj->name;
	// 		$this->assertTextNotContains('-1', strpos($recordData, 'National Univerysity of Singapore'));
	// 	}
	// }

	// public function testAdd() {
 //        $this->setAuthSession();
	// 	$data = [
	// 		'name' => 'Singapore Management University',
	// 		'alternative_name' => 'SMU',
	// 		'code' => 'SMU',
	// 		'address' => 'test address 3',
	// 		'postal_code' => '1234560',
	// 		'contact_person' => 'Karl Turnbull',
	// 		'telephone' => '123456781',
	// 		'fax' => '187654321',
	// 		'email' => 'kturnbull@kordit.com',
	// 		'website' => 'http://www.smu.edu.sg',
	// 		'date_opened' => '01-01-1985',
	// 		'year_opened' => 1985,
	// 		'date_closed' => '01-01-1990',
	// 		'year_closed' => 1990,
	// 		'longitude' => '100',
	// 		'latitude' => '1.32',
	// 		'institution_area_id' => null,
	// 		'area_id' => 1,
	// 		'area_administrative_id' => 1,
	// 		'institution_locality_id' => 83,
	// 		'institution_type_id' => 65,
	// 		'institution_ownership_id' => 72,
	// 		'institution_status_id' => 89,
	// 		'institution_sector_id' => 2,
	// 		'institution_provider_id' => 1,
	// 		'institution_gender_id' => 6,
	// 		'security_group_id' => 0
	// 	];

	// 	$this->post($this->prefix . '/add', $data);
	// 	$this->assertResponseSuccess();

	// 	$query = $this->Institutions->find()->where(['code' => 'SMU']);
	// 	$this->assertEquals(1, $query->count());
	// }

	public function testHistory() {
        $this->setAuthSession();
        $this->session(['Institutions'=>['id'=>1]]);
		$this->get($this->prefix . '/History');
        $this->assertResponseOk();
	
		$this->assertResponseContains('SMU');
    }

	public function testEdit() {
        $this->setAuthSession();
		// Test edit the first row of the Institutions fixture
		$data = [
			'id' => 1,
			'name' => 'Singapore Management University',
			'alternative_name' => 'SMU',
			'code' => 'SMU',
			'address' => 'test address 3',
			'postal_code' => '1234560',
			'contact_person' => 'Karl Turnbull',
			'telephone' => '123456781',
			'fax' => '187654321',
			'email' => 'kturnbull@kordit.com',
			'website' => 'http://www.smu.edu.sg',
			'date_opened' => '01-01-1985',
			'year_opened' => 1985,
			'date_closed' => '01-01-1990',
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
			'security_group_id' => 0
		];

		$this->post($this->prefix . '/edit/1', $data);
		$this->assertResponseSuccess();

        $fData = $this->Institutions->findById(1)->hydrate(false)->toArray();

		$this->assertEquals($data['name'], $fData[0]['name']);
	}

	/*public function testDelete() {
		$this->delete($this->prefix . '/remove?id=1');

		$query = $this->Institutions->find('all');
		$result = $query->hydrate(false)->toArray();

		//$this->assertResponseSuccess();
		$this->assertEquals(1, count($result));
	}*/

	public function testView() {
        $this->setAuthSession();
		// Test view the first row of the Institutions fixture
		$this->get($this->prefix . '/view/1');
		$this->assertResponseSuccess();

		// As an example, check whether the output has the words "Nanyang Technological University" and "NTU"
		$this->assertResponseContains('Nanyang Technological University');
		$this->assertResponseContains('NTU');
	}

}
